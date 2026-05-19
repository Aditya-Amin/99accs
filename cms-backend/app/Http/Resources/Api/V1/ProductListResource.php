<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ProductListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'slug'         => $this->slug,
            'game'         => $this->game->slug,
            'account_type' => $this->accountType->slug,
            'section'      => [
                'slug'  => $this->section->slug,
                'label' => $this->section->label,
                'order' => $this->section->sort_order,
            ],
            'title'           => $this->name,
            'price'           => (float) $this->price,
            'price_max'       => $this->price_max ? (float) $this->price_max : null,
            'old_price'       => $this->compare_at_price ? (float) $this->compare_at_price : null,
            'country'         => $this->resolveCountry(),
            'categories'      => collect($this->feature_badges ?? [])->values()->map(
                fn ($badge, $i) => array_merge(['id' => ($badge['id'] ?? ($i + 1))], $badge)
            )->all(),
            'images'          => $this->resolveImages(),
            'has_gallery'     => (bool) $this->has_gallery,
            'discount_percent' => $this->discount_percent,
            'badge_icon'      => $this->badge_icon,
            'region'          => $this->region?->slug,
            'rank'            => $this->rank,
            'agents'          => $this->agents ?? [],
            'skins'           => $this->skins ?? [],
            'buddies'         => $this->buddies ?? [],
            'description'       => $this->description,
            'short_description' => $this->short_description,
            'specs'           => $this->specs ?? (object) [],
            'stock'            => $this->stock_qty,
            'min_quantity'     => $this->min_quantity,
            'created_at'       => $this->created_at?->toISOString(),
        ];
    }

    // Card badge + flag are now driven by the product's Region.
    // Flag paths come in two shapes:
    //   • Legacy seeded values like "/img/icons/us.png" — return as-is
    //   • Admin uploads like "country-flags/abc.png"   — resolve to /storage/...
    protected function resolveCountry(): array
    {
        $region = $this->region;
        if (! $region) {
            return ['code' => '', 'flag' => null, 'class_modifier' => null];
        }

        return [
            'code'           => $region->code ?? '',
            'flag'           => $this->resolveFlagUrl($region->flag),
            'class_modifier' => $region->class_modifier,
        ];
    }

    protected function resolveFlagUrl(?string $flag): ?string
    {
        if (! $flag) return null;
        // Already a rooted URL or path that the frontend can use directly
        if (str_starts_with($flag, '/') || str_starts_with($flag, 'http')) {
            return $flag;
        }
        // Filament FileUpload stores a disk-relative path
        return Storage::disk('public')->url($flag);
    }

    protected function resolveImages(): array
    {
        // Prefer admin-uploaded Spatie media when present
        $spatie = collect([$this->featured_image_url])
            ->merge($this->images_urls)
            ->filter()
            ->values()
            ->toArray();

        if (! empty($spatie)) {
            return $spatie;
        }

        // Fall back to JSON images column (seeded / game-API-imported products)
        return $this->images ?? [];
    }
}
