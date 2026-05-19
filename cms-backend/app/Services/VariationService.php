<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariation;
use Illuminate\Support\Facades\DB;

class VariationService
{
    /**
     * Create a single variation variation for a parent product.
     */
    public function createVariation(Product $parentProduct, array $variationData): ProductVariation
    {
        return DB::transaction(function () use ($parentProduct, $variationData) {
            
            /** @var ProductVariation $variation */
            $variation = $parentProduct->variations()->create([
                'sku' => $variationData['sku'] ?? null,
                'price' => $variationData['price'] ?? 0,
                'stock_qty' => $variationData['stock_qty'] ?? 0,
            ]);

            // Sync attribute values (e.g. Size=Large, Color=Red)
            if (!empty($variationData['attribute_values'])) {
                // attribute_values format: [attribute_id => attribute_value_id]
                $syncData = collect($variationData['attribute_values'])->mapWithKeys(function ($valueId, $attrId) {
                    return [$valueId => ['product_attribute_id' => $attrId]];
                });
                
                $variation->attributeValues()->sync($syncData->toArray());
            }

            return $variation;
        });
    }

    /**
     * Automatically generate missing permutations from assigned attributes
     */
    public function generateCombinations(Product $product): void
    {
        $attributes = $product->attributes()->with('values')->get();

        $combinations = [[]];

        // Cartesian product logic
        foreach ($attributes as $attribute) {
            $current = [];
            foreach ($combinations as $combination) {
                foreach ($attribute->values as $value) {
                    $newCombo = $combination;
                    $newCombo[$attribute->id] = $value->id;
                    $current[] = $newCombo;
                }
            }
            $combinations = $current;
        }

        foreach ($combinations as $combination) {
            // Find existing
            // Create dummy base template if it doesn't already exist
            $this->createVariation($product, [
                'sku' => uniqid('VAR-'),
                'price' => $product->price,
                'stock_qty' => 0,
                'attribute_values' => $combination
            ]);
        }
    }
}