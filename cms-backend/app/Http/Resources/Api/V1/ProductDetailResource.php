<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;

class ProductDetailResource extends ProductListResource
{
    public function toArray(Request $request): array
    {
        return array_merge(parent::toArray($request), [
            'agents_detailed'      => $this->agents_detailed,
            'agents_count'         => $this->agents_count,
            'profile_info'         => $this->profile_info,
            'skin_inventory'       => $this->skin_inventory,
            'skin_filters'         => $this->skin_filters,
            'buddy_inventory'      => $this->buddy_inventory,
            'account_level'        => $this->account_level,
            'account_stats'        => $this->account_stats,
            'locker'               => $this->locker,
            'seasons'              => $this->seasons,
            'description_sections' => $this->description_sections,
            'min_quantity'         => $this->min_quantity,
            'last_match_label'     => $this->last_match_label,
            'guarantee'            => $this->guarantee,
            'related'              => ProductListResource::collection(
                $this->whenLoaded('related')
            ),
        ]);
    }
}
