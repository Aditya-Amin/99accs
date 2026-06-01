<?php

namespace App\Jobs;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImportWooCommerceOrders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Give the job plenty of time for large exports
    public int $timeout = 7200;
    public int $tries   = 1;

    // WooCommerce order status → our enum
    private const STATUS_MAP = [
        'processing'      => Order::STATUS_PROCESSING,
        'completed'       => Order::STATUS_COMPLETED,
        'paid-with-funds' => Order::STATUS_COMPLETED,  // WooCommerce Wallet
        'wc-paid-with-funds' => Order::STATUS_COMPLETED,
        'cancelled'       => Order::STATUS_CANCELLED,
        'refunded'        => Order::STATUS_CANCELLED,
        'failed'          => Order::STATUS_CANCELLED,
        'pending'         => Order::STATUS_PENDING,
        'pending payment' => Order::STATUS_PENDING,
        'on-hold'         => Order::STATUS_PENDING,
    ];

    // WC payment method slugs → our labels
    private const PAYMENT_MAP = [
        'ppcp-card-button-gateway' => 'card',
        'ppcp-paypal'              => 'paypal',
        'paypal'                   => 'paypal',
        'fsww'                     => 'wallet',   // WooCommerce Wallet / store credit
        'stripe'                   => 'stripe',
        'stripe_cc'                => 'stripe',
        'bacs'                     => 'bank_transfer',
        'cheque'                   => 'cheque',
        'cod'                      => 'cod',
    ];

    // These WC statuses indicate the customer's payment was received
    private const PAID_STATUSES = [
        'processing', 'completed', 'paid-with-funds', 'wc-paid-with-funds',
    ];

    public function __construct(
        private readonly string $storagePath,
        private readonly bool   $dryRun = false,
    ) {}

    public function handle(): void
    {
        $fullPath = Storage::path($this->storagePath);

        if (!file_exists($fullPath)) {
            Log::error("ImportWooCommerceOrders: file not found at {$this->storagePath}");
            return;
        }

        $handle = fopen($fullPath, 'r');

        if ($handle === false) {
            Log::error("ImportWooCommerceOrders: cannot open {$this->storagePath}");
            return;
        }

        $headers   = null;
        $chunk     = [];
        $chunkSize = 200;
        $totals    = ['imported' => 0, 'skipped' => 0, 'failed' => 0];

        while (($row = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
            if ($headers === null) {
                // Strip UTF-8 BOM if present (some WP exports include it)
                $headers = array_map(static fn($h) => ltrim(trim($h), "\xEF\xBB\xBF"), $row);
                continue;
            }

            // Pad short rows so array_combine doesn't error on sparse trailing columns
            if (count($row) < count($headers)) {
                $row = array_pad($row, count($headers), '');
            }

            $chunk[] = array_combine($headers, $row);

            if (count($chunk) >= $chunkSize) {
                $this->mergeCounters($totals, $this->processChunk($chunk));
                $chunk = [];
            }
        }

        if (!empty($chunk)) {
            $this->mergeCounters($totals, $this->processChunk($chunk));
        }

        fclose($handle);
        Storage::delete($this->storagePath);

        Log::info(sprintf(
            'ImportWooCommerceOrders complete — imported=%d, skipped=%d, failed=%d',
            $totals['imported'], $totals['skipped'], $totals['failed']
        ));
    }

    // ─────────────────────────────────────────────────────────────────────────

    private function processChunk(array $rows): array
    {
        $counts = ['imported' => 0, 'skipped' => 0, 'failed' => 0];

        DB::transaction(function () use ($rows, &$counts) {
            foreach ($rows as $data) {
                $result = $this->processRow($data);
                $counts[$result]++;
            }
        });

        return $counts;
    }

    private function processRow(array $data): string
    {
        $legacyId = trim($data['order_id'] ?? '');
        if ($legacyId === '') {
            return 'skipped';
        }

        // Idempotent: skip rows we've already imported
        if (Order::where('legacy_id', $legacyId)->exists()) {
            return 'skipped';
        }

        try {
            // ── Customer linkage ──────────────────────────────────────────────
            $customerId  = null;
            $wcUserId    = trim($data['customer_id'] ?? $data['customer_user'] ?? '');
            $email       = strtolower(trim($data['customer_email'] ?? ''));

            if ($wcUserId && $wcUserId !== '0') {
                $customer   = Customer::where('legacy_id', $wcUserId)->first()
                           ?? ($email ? Customer::where('email', $email)->first() : null);
                $customerId = $customer?->id;
            }

            // ── Status & payment ──────────────────────────────────────────────
            $wcStatus     = strtolower(trim($data['status'] ?? 'pending'));
            $status       = self::STATUS_MAP[$wcStatus] ?? Order::STATUS_PROCESSING;
            $isPaid       = in_array($wcStatus, self::PAID_STATUSES, true);
            $paymentStatus = $isPaid ? 'paid' : 'pending';

            // ── Payment method ────────────────────────────────────────────────
            $wcMethod     = trim($data['payment_method'] ?? '');
            $paymentMethod = self::PAYMENT_MAP[$wcMethod] ?? ($wcMethod ?: 'other');

            // ── Coupon ────────────────────────────────────────────────────────
            $couponCode = null;
            if (!empty($data['coupon_items'])) {
                if (preg_match('/code:([^|,\s]+)/i', $data['coupon_items'], $m)) {
                    $couponCode = trim($m[1]);
                }
            }

            // ── Dates ─────────────────────────────────────────────────────────
            $orderDate = !empty($data['order_date']) ? Carbon::parse($data['order_date']) : now();
            $paidDate  = ($isPaid && !empty($data['paid_date'])) ? Carbon::parse($data['paid_date']) : null;

            // ── Financials ────────────────────────────────────────────────────
            $orderTotal    = (float) ($data['order_total']    ?? 0);
            $shippingTotal = (float) ($data['shipping_total'] ?? 0);
            $taxTotal      = (float) ($data['tax_total']      ?? 0);
            $discountTotal = (float) ($data['discount_total'] ?? 0);

            $transactionId = trim($data['transaction_id'] ?? '') ?: null;
            $orderNumber   = trim($data['order_number']   ?? '') ?: $legacyId;

            if ($this->dryRun) {
                return 'imported';
            }

            // ── Persist order ─────────────────────────────────────────────────
            $order = Order::create([
                'legacy_id'           => $legacyId,
                'customer_id'         => $customerId,
                'number'              => $orderNumber,
                'checkout_token'      => Str::uuid()->toString(),
                'status'              => $status,
                'total_price'         => $orderTotal,
                'payment_status'      => $paymentStatus,
                'payment_method'      => $paymentMethod,
                'payment_provider_id' => $transactionId,
                'shipping_cost'       => $shippingTotal,
                'vat_tax'             => $taxTotal,
                'discount_amount'     => $discountTotal,
                'coupon_code'         => $couponCode,
                'paid_at'             => $paidDate,
                'created_at'          => $orderDate,
                'updated_at'          => $orderDate,
            ]);

            // ── Persist order items ───────────────────────────────────────────
            // The CSV exposes up to 148 "Product Item N" column groups.
            // We stop at the first empty Name (items are contiguous).
            //
            // We store the WC product/variation ID as legacy_id and try to resolve
            // product_id via either legacy_id or sku. Products imported later via
            // WordPressProductImporter populate products.legacy_id, so the SQL
            // backfill in OrderResource ("Backfill product links") will pick up
            // any rows still null after this resolution.
            for ($i = 1; $i <= 148; $i++) {
                $name = trim($data["Product Item {$i} Name"] ?? '');
                if ($name === '') {
                    break;
                }

                $qty       = max(1, (int) ($data["Product Item {$i} Quantity"] ?? 1));
                $total     = (float) ($data["Product Item {$i} Total"]    ?? 0);
                $wcItemId  = trim($data["Product Item {$i} id"]   ?? '') ?: null;
                $sku       = trim($data["Product Item {$i} SKU"]  ?? '') ?: null;

                $product = $wcItemId
                    ? \App\Models\Product::where('legacy_id', $wcItemId)->first()
                    : null;
                $product ??= $sku ? \App\Models\Product::where('sku', $sku)->first() : null;

                OrderItem::create([
                    'order_id'               => $order->id,
                    'product_id'             => $product?->id,
                    'vendor_id'              => null, // products table has no vendor_id column
                    'legacy_id'              => $wcItemId,
                    'product_name_snapshot'  => $name,
                    'price_snapshot'         => $qty > 0 ? round($total / $qty, 4) : 0,
                    'quantity'               => $qty,
                    'product_image_snapshot' => $product?->getFirstMediaUrl('product_featured_image') ?: null,
                ]);
            }

            return 'imported';
        } catch (\Throwable $e) {
            Log::error("ImportWooCommerceOrders: order {$legacyId} failed — {$e->getMessage()}");
            return 'failed';
        }
    }

    private function mergeCounters(array &$totals, array $chunk): void
    {
        foreach ($chunk as $key => $val) {
            $totals[$key] = ($totals[$key] ?? 0) + $val;
        }
    }
}
