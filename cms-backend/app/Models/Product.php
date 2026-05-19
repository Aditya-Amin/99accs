<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Product extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;

    // ─── Enum constants ───────────────────────────────────────────────────────

    public const GAME_VALORANT = 'valorant';
    public const GAME_FORTNITE = 'fortnite';
    public const GAME_LEGENDS  = 'legends';

    // account_type drives the detail-page layout
    public const ACCOUNT_VERIFIED          = 'verified';
    public const ACCOUNT_INACTIVE_EXCLUSIVE = 'inactive_exclusive';
    public const ACCOUNT_NFA               = 'nfa';
    public const ACCOUNT_NFA_INACTIVE      = 'nfa_inactive';
    public const ACCOUNT_STANDARD          = 'standard';

    // Feature badge icon enum — must match frontend CATEGORY_ICONS keys
    public const BADGE_ICONS = [
        'skins_count'     => 'Skins Count',
        'champions'       => 'Champions',
        'mail_access'     => 'Mail Access',
        'exclusive_skins' => 'Exclusive Skins',
        'random_skins'    => 'Random Skins',
    ];

    // ─── Fillable ─────────────────────────────────────────────────────────────

    protected $fillable = [
        // Core
        'name', 'slug', 'sku', 'description', 'short_description', 'is_visible',
        // Pricing
        'price', 'price_max', 'compare_at_price', 'discount_percent', 'badge_icon',
        // Inventory
        'stock_qty',
        // Media
        'images',
        // Taxonomy FKs (region drives country code / flag for the card)
        'game_id', 'account_type_id', 'section_id', 'region_id',
        // Denormalized skin M:N column (synced from pivot)
        'skin_ids',
        'rank', 'has_gallery',
        'agents', 'skins', 'buddies', 'specs', 'feature_badges',
        // Detail-only (populated by game API import)
        'agents_detailed', 'agents_count',
        'profile_info', 'skin_inventory', 'skin_filters', 'buddy_inventory',
        'account_level', 'account_stats', 'locker', 'seasons',
        'description_sections', 'min_quantity', 'last_match_label', 'guarantee',
        // Sync metadata
        'source_provider', 'external_id', 'synced_at',
    ];

    protected $casts = [
        'images'             => 'array',
        'agents'             => 'array',
        'skins'              => 'array',
        'buddies'            => 'array',
        'specs'              => 'array',
        'feature_badges'     => 'array',
        'agents_detailed'    => 'array',
        'profile_info'       => 'array',
        'skin_inventory'     => 'array',
        'skin_filters'       => 'array',
        'buddy_inventory'    => 'array',
        'account_level'      => 'array',
        'account_stats'      => 'array',
        'locker'             => 'array',
        'seasons'            => 'array',
        'description_sections' => 'array',
        'guarantee'          => 'array',
        'is_visible'         => 'boolean',
        'has_gallery'        => 'boolean',
        'price'              => 'decimal:2',
        'price_max'          => 'decimal:2',
        'compare_at_price'   => 'decimal:2',
        'synced_at'          => 'datetime',
        'skin_ids'           => 'array',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function accountType(): BelongsTo
    {
        return $this->belongsTo(AccountType::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    // Single-region FK — drives country badge + flag on the product card.
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    // M:N — source of truth for skin taxonomy assignments (write path)
    public function skinTags(): BelongsToMany
    {
        return $this->belongsToMany(Skin::class, 'product_skin');
    }

    // Keeps skin_ids JSON column in sync with the product_skin pivot.
    // Call inside a transaction whenever the pivot changes.
    public function syncDenormalized(): void
    {
        $this->updateQuietly([
            'skin_ids' => $this->skinTags()->pluck('skins.id')->all(),
        ]);
    }

    public function variations()
    {
        return $this->hasMany(ProductVariation::class, 'product_id');
    }

    public function groupedItems()
    {
        return $this->belongsToMany(Product::class, 'product_group_items', 'parent_id', 'child_id')
                    ->withPivot('quantity');
    }

    public function groupedItemPivots()
    {
        return $this->hasMany(ProductGroupItem::class, 'parent_id');
    }

    public function downloads()
    {
        return $this->hasMany(ProductDownload::class);
    }

    public function attributes()
    {
        return $this->hasMany(ProductAttribute::class);
    }

    // ─── Media accessors ─────────────────────────────────────────────────────

    public function getFeaturedImageUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl('product_featured_image') ?: null;
    }

    public function getImagesUrlsAttribute(): array
    {
        return $this->getMedia('product_gallery')
                    ->map(fn ($m) => $m->getUrl())
                    ->toArray();
    }
}
