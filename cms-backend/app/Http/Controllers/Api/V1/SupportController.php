<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use App\Models\SupportArticle;
use Illuminate\Http\Request;

class SupportController extends Controller
{
    public function articles(Request $request)
    {
        $query = SupportArticle::published()->ordered();

        if ($request->filled('category')) {
            $query->where('category', $request->string('category'));
        }

        $articles = $query->paginate($request->integer('per_page', 20));

        return response()->json([
            'data'  => $articles->items(),
            'meta'  => [
                'current_page' => $articles->currentPage(),
                'last_page'    => $articles->lastPage(),
                'per_page'     => $articles->perPage(),
                'total'        => $articles->total(),
            ],
            'links' => [
                'first' => $articles->url(1),
                'last'  => $articles->url($articles->lastPage()),
                'next'  => $articles->nextPageUrl(),
                'prev'  => $articles->previousPageUrl(),
            ],
        ]);
    }

    public function article(string $slug)
    {
        $article = SupportArticle::published()->where('slug', $slug)->firstOrFail();

        return response()->json(['data' => $article]);
    }

    public function faqs(Request $request): \Illuminate\Http\JsonResponse
    {
        $group = $request->string('group', 'support');
        $faqs  = Faq::active()->forGroup($group)->ordered()->get();

        return response()->json(['data' => $faqs->map(fn ($f) => [
            'id'       => $f->id,
            'question' => $f->question,
            'answer'   => $f->answer,
        ])]);
    }

    public function contact(Request $request)
    {
        $data = $request->validate([
            'name'    => 'required|string|max:255',
            'email'   => 'required|email',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:5000',
        ]);

        // No support-ticket model exists yet, so this is the closest thing to a
        // "ticket created" event — alert the admins so the message isn't missed.
        \App\Support\AdminNotifier::notify(
            title: 'New Support Message',
            body: "{$data['subject']} — from {$data['name']} ({$data['email']})",
            icon: 'heroicon-o-lifebuoy',
            iconColor: 'info',
        );

        return response()->json([
            'message' => 'Your message has been received. We will get back to you shortly.',
        ]);
    }
}
