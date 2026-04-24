<?php

namespace App\Http\Controllers\Orgs;

use App\Enums\PostType;
use App\Http\Controllers\Controller;
use App\Models\Org;
use App\PostTypes\PostTypeHandlerFactory;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PostController extends Controller
{
    /**
     * Показать страницу управления записями.
     */
    public function index(
        Request $request,
        PostTypeHandlerFactory $postTypeHandlerFactory,
        string $current_team,
        string $current_org,
        ?string $type = null,
    ): Response {
        $user = $request->user();
        $org = $user?->currentOrg;

        if (! $org || $org->slug !== $current_org) {
            $org = Org::query()->where('slug', $current_org)->first();
        }

        abort_unless($org, 404);

        if (! $user->isCurrentOrg($org)) {
            $user->switchOrg($org);
        }

        $activeType = in_array($type, PostType::values(), true)
            ? $type
            : PostType::Page->value;

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

        $postTypeUi = collect(PostType::cases())
            ->mapWithKeys(function (PostType $postType) use ($postTypeHandlerFactory) {
                return [
                    $postType->value => $postTypeHandlerFactory
                        ->make($postType)
                        ->toData()
                        ->toInertiaArray(),
                ];
            })
            ->all();

        return Inertia::render('posts/index', [
            'activeType' => $activeType,
            'postTypeUi' => $postTypeUi,
            'postTypes' => PostType::values(),
            'posts' => array_values($posts),
        ]);
    }
}
