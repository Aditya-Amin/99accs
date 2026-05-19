<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\Request;

class PageController extends Controller
{
    public function show(string $slug)
    {
        $page = Page::published()->where('slug', $slug)->firstOrFail();

        return response()->json([
            'data' => [
                'slug'   => $page->slug,
                'title'  => $page->title,
                'blocks' => $page->blocks ?? [],
                'seo'    => [
                    'meta_title'       => $page->meta_title,
                    'meta_description' => $page->meta_description,
                    'og_image'         => $page->og_image,
                ],
            ],
        ]);
    }
}
