<?php

namespace App\Http\Controllers\Orgs;

use App\Http\Controllers\Controller;
use App\Models\Org;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PostController extends Controller
{
    /**
     * Поддерживаемые типы записей в UI.
     *
     * @var list<string>
     */
    private const TYPES = ['page', 'news', 'article', 'product'];

    /**
     * Показать страницу управления записями.
     */
    public function index(Request $request, string $current_team, string $current_org, ?string $type = null): Response
    {
        $user = $request->user();
        $org = $user?->currentOrg;

        if (! $org || $org->slug !== $current_org) {
            $org = Org::query()->where('slug', $current_org)->first();
        }

        abort_unless($org, 404);

        if (! $user->isCurrentOrg($org)) {
            $user->switchOrg($org);
        }

        $activeType = in_array($type, self::TYPES, true) ? $type : self::TYPES[0];

        $posts = $org->posts()
            ->where('type', $activeType)
            ->orderByDesc('id')
            ->get(['id', 'type', 'status', 'slug', 'title', 'published_at', 'updated_at'])
            ->map(fn ($post) => [
                'id' => $post->id,
                'type' => $post->type,
                'status' => $post->status,
                'slug' => $post->slug,
                'title' => $post->title,
                'published_at' => $post->published_at?->toISOString(),
                'updated_at' => $post->updated_at?->toISOString(),
            ])
            ->all();

        return Inertia::render('posts/index', [
            'activeType' => $activeType,
            'postTypes' => self::TYPES,
            'posts' => array_values($posts),
        ]);
    }
}
