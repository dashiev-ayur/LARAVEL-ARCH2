<?php

namespace App\Http\Controllers\Orgs;

use App\Enums\PostType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Categories\StoreCategoryRequest;
use App\Http\Requests\Categories\UpdateCategoryRequest;
use App\Models\Category;
use App\Models\Org;
use App\PostTypes\PostTypeHandlerFactory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    /**
     * Сохранить новую категорию текущей организации.
     */
    public function store(
        StoreCategoryRequest $request,
        string $current_team,
        string $current_org,
    ): RedirectResponse {
        $org = $this->resolveCurrentOrg($request, $current_org);
        $validated = $request->validated();
        $type = (string) $validated['type'];
        $title = (string) $validated['title'];
        $slug = trim((string) ($validated['slug'] ?? ''));

        Category::query()->create([
            'org_id' => $org->id,
            'parent_id' => isset($validated['parent_id']) ? (int) $validated['parent_id'] : null,
            'type' => $type,
            'slug' => $slug !== '' ? $slug : $this->buildUniqueSlug($org, $type, $title),
            'title' => $title,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Category created.')]);

        return $this->redirectToCategoriesIndex($current_team, $org, $type);
    }

    /**
     * Обновить категорию текущей организации.
     */
    public function update(
        UpdateCategoryRequest $request,
        string $current_team,
        string $current_org,
        Category $category,
    ): RedirectResponse {
        $org = $this->resolveCurrentOrg($request, $current_org);
        abort_unless((int) $category->org_id === (int) $org->id, 404);

        $validated = $request->validated();
        $type = (string) $validated['type'];
        $title = (string) $validated['title'];
        $slug = trim((string) ($validated['slug'] ?? ''));

        $category->update([
            'parent_id' => array_key_exists('parent_id', $validated)
                ? (isset($validated['parent_id']) ? (int) $validated['parent_id'] : null)
                : $category->parent_id,
            'type' => $type,
            'slug' => $slug !== '' ? $slug : $this->buildUniqueSlug($org, $type, $title, $category),
            'title' => $title,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Category updated.')]);

        return $this->redirectToCategoriesIndex($current_team, $org, $type);
    }

    /**
     * Удалить категорию текущей организации.
     */
    public function destroy(
        Request $request,
        string $current_team,
        string $current_org,
        Category $category,
    ): RedirectResponse {
        $org = $this->resolveCurrentOrg($request, $current_org);
        abort_unless((int) $category->org_id === (int) $org->id, 404);

        $type = (string) $category->type;
        $category->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Category deleted.')]);

        return $this->redirectToCategoriesIndex($current_team, $org, $type);
    }

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
            ->withCount(['posts', 'children'])
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

    private function redirectToCategoriesIndex(string $currentTeamSlug, Org $org, string $type): RedirectResponse
    {
        if ($type === PostType::Page->value) {
            return to_route('categories.index', [
                'current_team' => $currentTeamSlug,
                'current_org' => $org->slug,
            ]);
        }

        return to_route('categories.byType', [
            'current_team' => $currentTeamSlug,
            'current_org' => $org->slug,
            'type' => $type,
        ]);
    }

    private function buildUniqueSlug(Org $org, string $type, string $title, ?Category $ignoreCategory = null): string
    {
        $baseSlug = Str::slug($title) ?: 'category';
        $slug = $baseSlug;
        $index = 2;

        while (Category::query()
            ->where('org_id', $org->id)
            ->where('type', $type)
            ->where('slug', $slug)
            ->when($ignoreCategory, fn ($query) => $query->whereKeyNot($ignoreCategory->getKey()))
            ->exists()) {
            $slug = "{$baseSlug}-{$index}";
            $index++;
        }

        return $slug;
    }

    /**
     * @param  Collection<int, Category>  $categories
     * @return array<int, array{id: int, parent_id: int|null, depth: int, type: string, slug: string, title: string, posts_count: int, children_count: int, updated_at: string|null}>
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
     * @return array<int, array{id: int, parent_id: int|null, depth: int, type: string, slug: string, title: string, posts_count: int, children_count: int, updated_at: string|null}>
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
                'posts_count' => (int) $category->posts_count,
                'children_count' => (int) $category->children_count,
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
