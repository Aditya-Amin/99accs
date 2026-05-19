<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ProductDetailResource;
use App\Http\Resources\Api\V1\ProductListResource;
use App\Models\Product;
use App\Models\Skin;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    // region_id and section_id are simple FKs — resolved via eager loads.
    // skin_ids stays denormalized JSON for fast multi-value filtering.
    private const LIST_COLUMNS = [
        'id', 'slug', 'game_id', 'account_type_id', 'section_id', 'region_id',
        'name', 'price', 'price_max', 'compare_at_price',
        'feature_badges', 'images', 'has_gallery',
        'discount_percent', 'badge_icon',
        'rank',
        'agents', 'skins', 'buddies',
        'description', 'specs',
        'stock_qty', 'min_quantity', 'created_at',
        'skin_ids',
    ];

    // Skin slug→id lookup cached for the lifetime of the request
    private ?array $skinMap = null;

    public function index(Request $request)
    {
        $query = Product::query()
            ->select(self::LIST_COLUMNS)
            ->with(['game', 'accountType', 'section', 'region'])
            ->where('is_visible', true);

        if ($request->filled('game')) {
            $query->whereHas('game', fn ($q) => $q->where('slug', $request->string('game')));
        }

        if ($request->filled('account_type')) {
            $query->whereHas('accountType', fn ($q) => $q->where('slug', $request->string('account_type')));
        }

        if ($request->filled('section')) {
            $query->whereHas('section', fn ($q) => $q->where('slug', $request->string('section')));
        }

        // Region filter — single FK, fast index lookup
        if ($request->filled('region')) {
            $query->whereHas('region', fn ($q) => $q->where('slug', $request->string('region')));
        }

        // Skin filter — single-table JSON_CONTAINS, uses multi-value index
        if ($request->filled('skin')) {
            $id = $this->skinId($request->string('skin')->toString());
            if ($id) $query->whereJsonContains('skin_ids', $id);
        }

        // Country badge filter — matches region.code (NA, EU, AP, EUW, LAS, TR…)
        if ($request->filled('country')) {
            $query->whereHas('region', fn ($q) =>
                $q->where('code', strtoupper($request->string('country')->toString()))
            );
        }

        if ($request->filled('rank')) {
            $query->whereRaw('LOWER(rank) = ?', [strtolower($request->string('rank'))]);
        }

        if ($request->filled('min_price')) {
            $query->where('price', '>=', $request->float('min_price'));
        }

        if ($request->filled('max_price')) {
            $query->where('price', '<=', $request->float('max_price'));
        }

        if ($request->filled('search')) {
            $term = '%' . $request->string('search') . '%';
            $query->where(fn ($q) => $q->where('name', 'like', $term)->orWhere('description', 'like', $term));
        }

        $query = match ($request->string('sort')->toString()) {
            'price_asc'  => $query->orderBy('price'),
            'price_desc' => $query->orderByDesc('price'),
            'oldest'     => $query->orderBy('created_at'),
            default      => $query->orderByDesc('created_at'),
        };

        return ProductListResource::collection(
            $query->paginate($request->integer('per_page', 24))
        );
    }

    public function show(string $slug)
    {
        $product = Product::where('slug', $slug)
            ->where('is_visible', true)
            ->with(['game', 'accountType', 'section', 'region'])
            ->firstOrFail();

        $related = Product::query()
            ->select(self::LIST_COLUMNS)
            ->with(['game', 'accountType', 'section', 'region'])
            ->where('is_visible', true)
            ->where('game_id', $product->game_id)
            ->where('id', '!=', $product->id)
            ->whereHas('accountType', fn ($q) => $q->whereNotIn('slug', [
                Product::ACCOUNT_INACTIVE_EXCLUSIVE,
                Product::ACCOUNT_NFA_INACTIVE,
            ]))
            ->inRandomOrder()
            ->limit(6)
            ->get();

        $product->setRelation('related', $related);

        return new ProductDetailResource($product);
    }

    // ─── Taxonomy slug → id helper (request-scoped cache) ────────────────────

    private function skinId(string $slug): ?int
    {
        if ($this->skinMap === null) {
            $this->skinMap = Skin::pluck('id', 'slug')->all();
        }
        return $this->skinMap[$slug] ?? null;
    }
}
