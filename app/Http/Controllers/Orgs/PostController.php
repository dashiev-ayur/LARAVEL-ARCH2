<?php

namespace App\Http\Controllers\Orgs;

use App\Enums\PostType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Posts\StorePostRequest;
use App\Http\Requests\Posts\UpdatePostCategoriesRequest;
use App\Http\Requests\Posts\UpdatePostRequest;
use App\Models\Category;
use App\Models\Org;
use App\Models\Post;
use App\PostTypes\PostTypeHandlerFactory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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
     * Показать страницу создания записи текущей организации.
     */
    public function create(
        Request $request,
        PostTypeHandlerFactory $postTypeHandlerFactory,
        string $current_team,
        string $current_org,
    ): Response {
        $org = $this->resolveCurrentOrg($request, $current_org);
        $activeType = $this->resolvePostTypeFromRequest($request);

        return Inertia::render('posts/edit', [
            'activeType' => $activeType,
            'categories' => $this->categoryRowsToInertiaArray($org, $activeType),
            'post' => null,
            'postsListQuery' => $this->postsListQueryToInertiaArray($request),
            'postTypeUi' => $this->postTypeUiToInertiaArray($postTypeHandlerFactory),
            'postTypes' => PostType::values(),
        ]);
    }

    /**
     * Показать страницу редактирования записи текущей организации.
     */
    public function edit(
        Request $request,
        PostTypeHandlerFactory $postTypeHandlerFactory,
        string $current_team,
        string $current_org,
        Post $post,
    ): Response {
        $org = $this->resolveCurrentOrg($request, $current_org);
        abort_unless((int) $post->org_id === (int) $org->id, 404);

        return Inertia::render('posts/edit', [
            'activeType' => $post->type instanceof PostType ? $post->type->value : (string) $post->type,
            'categories' => $this->categoryRowsToInertiaArray(
                $org,
                $post->type instanceof PostType ? $post->type->value : (string) $post->type,
                $post,
            ),
            'post' => $this->postToInertiaArray($post),
            'postsListQuery' => $this->postsListQueryToInertiaArray($request),
            'postTypeUi' => $this->postTypeUiToInertiaArray($postTypeHandlerFactory),
            'postTypes' => PostType::values(),
        ]);
    }

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

        $post = Post::query()->create([
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

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Запись успешно сохранена.']);

        return to_route('posts.edit', [
            'current_team' => $current_team,
            'current_org' => $org->slug,
            'post' => $post,
            ...$this->postsListQueryToRouteParameters($request),
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

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Запись успешно сохранена.']);

        return to_route('posts.edit', [
            'current_team' => $current_team,
            'current_org' => $org->slug,
            'post' => $post,
            ...$this->postsListQueryToRouteParameters($request),
        ]);
    }

    /**
     * Обновить связи записи с категориями текущей организации.
     */
    public function updateCategories(
        UpdatePostCategoriesRequest $request,
        string $current_team,
        string $current_org,
        Post $post,
    ): RedirectResponse {
        $org = $this->resolveCurrentOrg($request, $current_org);
        abort_unless((int) $post->org_id === (int) $org->id, 404);

        $categoryIds = collect($request->validated('category_ids'))
            ->map(fn (int|string $categoryId): int => (int) $categoryId)
            ->unique()
            ->values()
            ->all();

        $post->categories()->sync($categoryIds);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Связи с категориями сохранены.']);

        return to_route('posts.edit', [
            'current_team' => $current_team,
            'current_org' => $org->slug,
            'post' => $post,
            ...$this->postsListQueryToRouteParameters($request),
        ]);
    }

    /**
     * Удалить черновик текущей организации.
     */
    public function destroy(
        Request $request,
        string $current_team,
        string $current_org,
        Post $post,
    ): RedirectResponse {
        $org = $this->resolveCurrentOrg($request, $current_org);
        abort_unless((int) $post->org_id === (int) $org->id, 404);
        abort_unless($post->status === 'draft', 403);

        $type = $post->type instanceof PostType ? $post->type->value : (string) $post->type;
        $post->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Post deleted.')]);

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
            : PostType::News->value;

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
            ->through(fn ($post) => $this->postToInertiaArray($post));

        $postTypeUi = $this->postTypeUiToInertiaArray($postTypeHandlerFactory);

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

    /**
     * @return array{id: int, type: string, status: string, slug: string, title: string, excerpt: string|null, content: string|null, published_at: string|null, updated_at: string|null}
     */
    private function postToInertiaArray(Post $post): array
    {
        return [
            'id' => $post->id,
            'type' => $post->type instanceof PostType ? $post->type->value : (string) $post->type,
            'status' => $post->status,
            'slug' => $post->slug,
            'title' => $post->title,
            'excerpt' => $post->excerpt,
            'content' => $post->content,
            'published_at' => $post->published_at?->toISOString(),
            'updated_at' => $post->updated_at?->toISOString(),
        ];
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function postTypeUiToInertiaArray(PostTypeHandlerFactory $postTypeHandlerFactory): array
    {
        return collect(PostType::cases())
            ->mapWithKeys(function (PostType $postType) use ($postTypeHandlerFactory) {
                return [
                    $postType->value => $postTypeHandlerFactory
                        ->make($postType)
                        ->toData()
                        ->toInertiaArray(),
                ];
            })
            ->all();
    }

    /**
     * @return array<int, array{id: int, parent_id: int|null, depth: int, title: string, is_linked: bool}>
     */
    private function categoryRowsToInertiaArray(Org $org, string $type, ?Post $post = null): array
    {
        /** @var Collection<int, Category> $categories */
        $categories = $org->categories()
            ->select(['id', 'parent_id', 'type', 'title', 'sort_order'])
            ->where('type', $type)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $linkedCategoryIds = $post
            ? $post->categories()->pluck('categories.id')->map(fn (int|string $id): int => (int) $id)->all()
            : [];

        $categoryIds = $categories->pluck('id')->all();

        /** @var Collection<int, Collection<int, Category>> $categoriesByParent */
        $categoriesByParent = $categories->groupBy(
            fn (Category $category): int => in_array($category->parent_id, $categoryIds, true)
                ? (int) $category->parent_id
                : 0,
        );

        return $this->flattenCategoryRows($categoriesByParent, $linkedCategoryIds);
    }

    /**
     * @param  Collection<int, Collection<int, Category>>  $categoriesByParent
     * @param  array<int, int>  $linkedCategoryIds
     * @param  array<int, true>  $visited
     * @return array<int, array{id: int, parent_id: int|null, depth: int, title: string, is_linked: bool}>
     */
    private function flattenCategoryRows(
        Collection $categoriesByParent,
        array $linkedCategoryIds,
        int $parentId = 0,
        int $depth = 0,
        array &$visited = [],
    ): array {
        $rows = [];

        foreach ($categoriesByParent->get($parentId, collect()) as $category) {
            if (isset($visited[$category->id])) {
                continue;
            }

            $visited[$category->id] = true;
            $rows[] = [
                'id' => $category->id,
                'parent_id' => $category->parent_id,
                'depth' => $depth,
                'title' => $category->title,
                'is_linked' => in_array((int) $category->id, $linkedCategoryIds, true),
            ];

            array_push(
                $rows,
                ...$this->flattenCategoryRows($categoriesByParent, $linkedCategoryIds, $category->id, $depth + 1, $visited),
            );
        }

        return $rows;
    }

    /**
     * @return array{page: int, per_page: int, search: string, filter_title: string, filter_status: string, filter_published_at: string, filter_updated_at: string, sort_by: string, sort_direction: string}
     */
    private function postsListQueryToInertiaArray(Request $request): array
    {
        $perPage = $request->integer('per_page', self::DEFAULT_PER_PAGE);
        if (! in_array($perPage, self::ALLOWED_PER_PAGE, true)) {
            $perPage = self::DEFAULT_PER_PAGE;
        }

        $sortBy = (string) $request->query('sort_by', self::DEFAULT_SORT_BY);
        if (! in_array($sortBy, self::ALLOWED_SORT_FIELDS, true)) {
            $sortBy = self::DEFAULT_SORT_BY;
        }

        $sortDirection = (string) $request->query('sort_direction', self::DEFAULT_SORT_DIRECTION);
        if (! in_array($sortDirection, self::ALLOWED_SORT_DIRECTIONS, true)) {
            $sortDirection = self::DEFAULT_SORT_DIRECTION;
        }

        $filterPublishedAt = trim((string) $request->query('filter_published_at', ''));
        $filterUpdatedAt = trim((string) $request->query('filter_updated_at', ''));

        return [
            'page' => max($request->integer('page', 1), 1),
            'per_page' => $perPage,
            'search' => trim((string) $request->query('search', '')),
            'filter_title' => trim((string) $request->query('filter_title', '')),
            'filter_status' => trim((string) $request->query('filter_status', '')),
            'filter_published_at' => $this->isValidDateFilter($filterPublishedAt) ? $filterPublishedAt : '',
            'filter_updated_at' => $this->isValidDateFilter($filterUpdatedAt) ? $filterUpdatedAt : '',
            'sort_by' => $sortBy,
            'sort_direction' => $sortDirection,
        ];
    }

    /**
     * @return array<string, int|string>
     */
    private function postsListQueryToRouteParameters(Request $request): array
    {
        $postsListQuery = $this->postsListQueryToInertiaArray($request);
        $routeParameters = [];

        if ($postsListQuery['page'] > 1) {
            $routeParameters['page'] = $postsListQuery['page'];
        }

        if ($postsListQuery['per_page'] !== self::DEFAULT_PER_PAGE) {
            $routeParameters['per_page'] = $postsListQuery['per_page'];
        }

        foreach (['search', 'filter_title', 'filter_status', 'filter_published_at', 'filter_updated_at'] as $key) {
            if ($postsListQuery[$key] !== '') {
                $routeParameters[$key] = $postsListQuery[$key];
            }
        }

        if ($postsListQuery['sort_by'] !== self::DEFAULT_SORT_BY) {
            $routeParameters['sort_by'] = $postsListQuery['sort_by'];
        }

        if ($postsListQuery['sort_direction'] !== self::DEFAULT_SORT_DIRECTION) {
            $routeParameters['sort_direction'] = $postsListQuery['sort_direction'];
        }

        return $routeParameters;
    }

    private function resolvePostTypeFromRequest(Request $request): string
    {
        $type = (string) $request->query('type', PostType::News->value);

        return in_array($type, PostType::values(), true)
            ? $type
            : PostType::News->value;
    }
}
