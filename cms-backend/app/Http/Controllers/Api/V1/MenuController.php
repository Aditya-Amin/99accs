<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Menu;

class MenuController extends Controller
{
    public function show(string $slug)
    {
        $menu = Menu::where('slug', $slug)->firstOrFail();
        $menu->load(['items.children']);

        return response()->json([
            'data' => [
                'slug'  => $menu->slug,
                'name'  => $menu->name,
                'items' => $menu->items->map(fn ($item) => [
                    'id'       => $item->id,
                    'label'    => $item->label,
                    'url'      => $item->url,
                    'target'   => $item->target,
                    'children' => $item->children->map(fn ($child) => [
                        'id'     => $child->id,
                        'label'  => $child->label,
                        'url'    => $child->url,
                        'target' => $child->target,
                    ])->toArray(),
                ])->toArray(),
            ],
        ]);
    }
}
