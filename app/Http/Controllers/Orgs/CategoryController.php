<?php

namespace App\Http\Controllers\Orgs;

use App\Enums\PostType;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Org;
use App\PostTypes\PostTypeHandlerFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    /**
     * Показать страницу управления категориями.
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

        /** @var Collection<int, Category> $categories */
        $categories = $org->categories()
            ->select(['id', 'parent_id', 'type', 'slug', 'title', 'updated_at'])
            ->where('type', $activeType)
            ->orderBy('title')
            ->orderBy('id')
            ->get();

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

        return Inertia::render('categories/index', [
            'activeType' => $activeType,
            'categories' => $this->toTreeRows($categories),
            'postTypes' => PostType::values(),
            'postTypeUi' => $postTypeUi,
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

    /**
     * @param  Collection<int, Category>  $categories
     * @return array<int, array{id: int, parent_id: int|null, depth: int, type: string, slug: string, title: string, updated_at: string|null}>
     */
    private function toTreeRows(Collection $categories): array
    {
        $categoryIds = $categories->pluck('id')->all();

        /** @var Collection<int, Collection<int, Category>> $categoriesByParent */
        $categoriesByParent = $categories->groupBy(
            fn (Category $category): int => in_array($category->parent_id, $categoryIds, true)
                ? (int) $category->parent_id
                : 0,
        );

        return $this->flattenTree($categoriesByParent);
    }

    /**
     * @param  Collection<int, Collection<int, Category>>  $categoriesByParent
     * @param  array<int, true>  $visited
     * @return array<int, array{id: int, parent_id: int|null, depth: int, type: string, slug: string, title: string, updated_at: string|null}>
     */
    private function flattenTree(
        Collection $categoriesByParent,
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
                'type' => $category->type,
                'slug' => $category->slug,
                'title' => $category->title,
                'updated_at' => $category->updated_at?->toISOString(),
            ];

            array_push(
                $rows,
                ...$this->flattenTree($categoriesByParent, $category->id, $depth + 1, $visited),
            );
        }

        return $rows;
    }
}
