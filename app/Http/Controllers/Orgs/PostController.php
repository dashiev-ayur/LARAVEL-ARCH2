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
    private const DEFAULT_PER_PAGE = 8;

    private const ALLOWED_PER_PAGE = [8, 10, 25, 50];

    private const DEFAULT_SORT_BY = 'id';

    private const DEFAULT_SORT_DIRECTION = 'desc';

    private const ALLOWED_SORT_FIELDS = ['id', 'title', 'status', 'published_at', 'updated_at'];

    private const ALLOWED_SORT_DIRECTIONS = ['asc', 'desc'];

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
            ->paginate($perPage, ['id', 'type', 'status', 'slug', 'title', 'excerpt', 'published_at', 'updated_at'])
            ->withQueryString()
            ->through(fn ($post) => [
                'id' => $post->id,
                'type' => $post->type,
                'status' => $post->status,
                'slug' => $post->slug,
                'title' => $post->title,
                'excerpt' => $post->excerpt,
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

    private function isValidDateFilter(string $value): bool
    {
        if ($value === '') {
            return false;
        }

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1;
    }
}
