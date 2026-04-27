<?php

namespace App\Http\Controllers\Orgs;

use App\Enums\PostType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Posts\StorePostRequest;
use App\Http\Requests\Posts\UpdatePostRequest;
use App\Models\Org;
use App\Models\Post;
use App\PostTypes\PostTypeHandlerFactory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PostController extends Controller
{
    private const DEFAULT_PER_PAGE = 8;

    private const ALLOWED_PER_PAGE = [8, 10, 25, 50];

    private const DEFAULT_SORT_BY = 'id';

    private const DEFAULT_SORT_DIRECTION = 'desc';

    private const ALLOWED_SORT_FIELDS = ['id', 'title', 'status', 'published_at', 'updated_at'];

    private const ALLOWED_SORT_DIRECTIONS = ['asc', 'desc'];

    /**
     * Сохранить новую запись текущей организации.
     */
    public function store(
        StorePostRequest $request,
        string $current_team,
        string $current_org,
    ): RedirectResponse {
        $org = $this->resolveCurrentOrg($request, $current_org);
        $validated = $request->validated();
        $type = (string) $validated['type'];
        $title = (string) $validated['title'];
        $slug = trim((string) ($validated['slug'] ?? ''));

        Post::query()->create([
            'org_id' => $org->id,
            'author_id' => $request->user()->id,
            'type' => $type,
            'status' => $validated['status'],
            'slug' => $slug !== '' ? $slug : $this->buildUniqueSlug($org, $type, $title),
            'title' => $title,
            'excerpt' => $validated['excerpt'] ?? null,
            'content' => $validated['content'] ?? null,
            'published_at' => $validated['published_at'] ?? null,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Post created.')]);

        return to_route('posts.byType', [
            'current_team' => $current_team,
            'current_org' => $org->slug,
            'type' => $type,
        ]);
    }

    /**
     * Обновить запись текущей организации.
     */
    public function update(
        UpdatePostRequest $request,
        string $current_team,
        string $current_org,
        Post $post,
    ): RedirectResponse {
        $org = $this->resolveCurrentOrg($request, $current_org);
        abort_unless((int) $post->org_id === (int) $org->id, 404);

        $validated = $request->validated();
        $type = (string) $validated['type'];
        $title = (string) $validated['title'];
        $slug = trim((string) ($validated['slug'] ?? ''));

        $post->update([
            'type' => $type,
            'status' => $validated['status'],
            'slug' => $slug !== '' ? $slug : $this->buildUniqueSlug($org, $type, $title, $post),
            'title' => $title,
            'excerpt' => $validated['excerpt'] ?? null,
            'content' => $validated['content'] ?? null,
            'published_at' => $validated['published_at'] ?? null,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Post updated.')]);

        return to_route('posts.byType', [
            'current_team' => $current_team,
            'current_org' => $org->slug,
            'type' => $type,
        ]);
    }

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
        $org = $this->resolveCurrentOrg($request, $current_org);

        $activeType = in_array($type, PostType::values(), true)
            ? $type
            : PostType::Page->value;

        $perPage = $request->integer('per_page', self::DEFAULT_PER_PAGE);
        if (! in_array($perPage, self::ALLOWED_PER_PAGE, true)) {
            $perPage = self::DEFAULT_PER_PAGE;
        }

        $filterTitle = trim((string) $request->query('filter_title', ''));
        $filterStatus = trim((string) $request->query('filter_status', ''));
        $filterPublishedAt = trim((string) $request->query('filter_published_at', ''));
        $filterUpdatedAt = trim((string) $request->query('filter_updated_at', ''));
        $search = trim((string) $request->query('search', ''));
        $sortBy = (string) $request->query('sort_by', self::DEFAULT_SORT_BY);
        $sortDirection = (string) $request->query('sort_direction', self::DEFAULT_SORT_DIRECTION);

        if (! in_array($sortBy, self::ALLOWED_SORT_FIELDS, true)) {
            $sortBy = self::DEFAULT_SORT_BY;
        }
        if (! in_array($sortDirection, self::ALLOWED_SORT_DIRECTIONS, true)) {
            $sortDirection = self::DEFAULT_SORT_DIRECTION;
        }

        $posts = $org->posts()
            ->where('type', $activeType)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('title', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%");
                });
            })
            ->when($filterTitle !== '', function ($query) use ($filterTitle) {
                $query->where('title', 'like', "%{$filterTitle}%");
            })
            ->when($filterStatus !== '', function ($query) use ($filterStatus) {
                $query->where('status', 'like', "%{$filterStatus}%");
            })
            ->when($this->isValidDateFilter($filterPublishedAt), function ($query) use ($filterPublishedAt) {
                $query->whereDate('published_at', $filterPublishedAt);
            })
            ->when($this->isValidDateFilter($filterUpdatedAt), function ($query) use ($filterUpdatedAt) {
                $query->whereDate('updated_at', $filterUpdatedAt);
            })
            ->orderBy($sortBy, $sortDirection)
            ->orderByDesc('id')
            ->paginate($perPage, ['id', 'type', 'status', 'slug', 'title', 'excerpt', 'content', 'published_at', 'updated_at'])
            ->withQueryString()
            ->through(fn ($post) => [
                'id' => $post->id,
                'type' => $post->type,
                'status' => $post->status,
                'slug' => $post->slug,
                'title' => $post->title,
                'excerpt' => $post->excerpt,
                'content' => $post->content,
                'published_at' => $post->published_at?->toISOString(),
                'updated_at' => $post->updated_at?->toISOString(),
            ]);

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
            'posts' => $posts->items(),
            'postsPagination' => [
                'currentPage' => $posts->currentPage(),
                'lastPage' => $posts->lastPage(),
                'perPage' => $posts->perPage(),
                'total' => $posts->total(),
            ],
            'postsFilters' => [
                'search' => $search,
                'title' => $filterTitle,
                'status' => $filterStatus,
                'publishedAt' => $this->isValidDateFilter($filterPublishedAt) ? $filterPublishedAt : '',
                'updatedAt' => $this->isValidDateFilter($filterUpdatedAt) ? $filterUpdatedAt : '',
            ],
            'postsSorting' => [
                'sortBy' => $sortBy,
                'sortDirection' => $sortDirection,
            ],
        ]);
    }

    private function resolveCurrentOrg(Request $request, string $currentOrgSlug): Org
    {
        $user = $request->user();

        abort_unless($user, 403);

        $org = $user->currentOrg;

        if (! $org || $org->slug !== $currentOrgSlug) {
            $org = Org::query()->where('slug', $currentOrgSlug)->first();
        }

        abort_unless($org, 404);
        abort_unless($user->isCurrentOrg($org) || $user->switchOrg($org), 403);

        return $org;
    }

    private function buildUniqueSlug(Org $org, string $type, string $title, ?Post $ignorePost = null): string
    {
        $baseSlug = Str::slug($title) ?: 'post';
        $slug = $baseSlug;
        $index = 2;

        while (Post::withTrashed()
            ->where('org_id', $org->id)
            ->where('type', $type)
            ->where('slug', $slug)
            ->when($ignorePost, fn ($query) => $query->whereKeyNot($ignorePost->getKey()))
            ->exists()) {
            $slug = "{$baseSlug}-{$index}";
            $index++;
        }

        return $slug;
    }

    private function isValidDateFilter(string $value): bool
    {
        if ($value === '') {
            return false;
        }

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1;
    }
}
