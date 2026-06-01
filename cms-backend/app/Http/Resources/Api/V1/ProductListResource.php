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
            'game'         => $this->game?->slug,
            'account_type' => $this->accountType?->slug,
            'section'      => $this->section ? [
                'slug'  => $this->section->slug,
                'label' => $this->section->label,
                'order' => $this->section->sort_order,
            ] : null,
            'title'           => $this->name,
            'price'           => (float) $this->price,
            'regular_price'   => $this->regular_price ? (float) $this->regular_price : null,
            'discount_percent' => $this->resolveDiscountPercent(),
            'country'         => $this->resolveCountry(),
            'has_skin_types'  => !empty($this->skin_ids),
            'categories'      => collect($this->feature_badges ?? [])->values()->map(
                fn ($badge, $i) => array_merge(['id' => ($badge['id'] ?? ($i + 1))], $badge)
            )->all(),
            'images'          => $this->resolveImages(),
            'has_gallery'     => (bool) $this->has_gallery,
            'badge_icon'      => $this->resolveStoredImageUrl($this->badge_icon),
            'region'          => $this->regions->first()?->slug,
            'rank'            => $this->rank,
            'agents'          => $this->agents ?? [],
            'skins'           => $this->skins ?? [],
            'buddies'         => $this->buddies ?? [],
            'description'  => $this->description,
            'highlights'   => collect($this->highlights ?? [])->map(fn ($h) => [
                'icon'  => $this->resolveStoredImageUrl($h['icon'] ?? null),
                'label' => $h['label'] ?? '',
            ])->all(),
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
        $region = $this->regions->first();
        if (! $region) {
            return ['code' => '', 'flag' => null, 'class_modifier' => null];
        }

        return [
            'code'           => $region->code ?? '',
            'flag'           => $this->resolveFlagUrl($region->flag),
            'class_modifier' => $region->class_modifier,
        ];
    }

    protected function resolveDiscountPercent(): ?int
    {
        $regular = $this->regular_price ? (float) $this->regular_price : null;
        $sale    = (float) $this->price;
        if (! $regular || $regular <= $sale) return null;
        return (int) round((($regular - $sale) / $regular) * 100);
    }

    protected function resolveFlagUrl(?string $flag): ?string
    {
        return $this->resolveStoredImageUrl($flag);
    }

    protected function resolveStoredImageUrl(mixed $path): ?string
    {
        if (! $path) return null;
        if (is_int($path) || (is_string($path) && ctype_digit($path))) {
            $media = \App\Models\CuratorMedia::find((int) $path);
            return $media ? $media->url : null;
        }
        if (str_starts_with($path, '/') || str_starts_with($path, 'http')) return $path;
        /** @var \Illuminate\Contracts\Filesystem\Cloud $disk */
        $disk = Storage::disk('public');
        return $disk->url($path);
    }

    protected function resolveImages(): array
    {
        // 1. Prefer Curator-managed images (featured_image_id + gallery_ids)
        $curator = [];

        if ($this->featured_image_id) {
            $url = $this->resolveStoredImageUrl($this->featured_image_id);
            if ($url) {
                $curator[] = $url;
            }
        }

        foreach (($this->gallery_ids ?? []) as $id) {
            $url = $this->resolveStoredImageUrl($id);
            if ($url) {
                $curator[] = $url;
            }
        }

        if (! empty($curator)) {
            return $curator;
        }

        // 2. Fall back to Spatie media (legacy records not yet migrated to Curator)
        $spatie = collect([$this->featured_image_url])
            ->merge($this->images_urls)
            ->filter()
            ->values()
            ->toArray();

        if (! empty($spatie)) {
            return $spatie;
        }

        // 3. Fall back to JSON images column (seeded / game-API-imported products)
        return $this->images ?? [];
    }
}
