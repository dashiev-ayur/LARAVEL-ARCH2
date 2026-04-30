<?php

namespace App\Services\Pages;

use App\Models\Org;
use App\Models\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PageTreeService
{
    /**
     * Подготовить плоские строки дерева страниц для Inertia.
     *
     * @param  Collection<int, Page>  $pages
     * @return array<int, array{id: int, parent_id: int|null, depth: int, slug: string, path: string, title: string, status: string, seo_title: string|null, meta_description: string|null, noindex: bool, sort_order: int, children_count: int, updated_at: string|null}>
     */
    public function flattenTreeRows(Collection $pages): array
    {
        $pageIds = $pages->pluck('id')->map(fn (int|string $id): int => (int) $id)->all();

        /** @var Collection<int, Collection<int, Page>> $pagesByParent */
        $pagesByParent = $pages->groupBy(
            fn (Page $page): int => in_array((int) $page->parent_id, $pageIds, true)
                ? (int) $page->parent_id
                : 0,
        );

        return $this->flattenTree($pagesByParent);
    }

    public function buildUniqueSlug(Org $org, string $title, ?int $parentId = null, ?Page $ignorePage = null): string
    {
        $baseSlug = Str::slug($title) ?: 'page';
        $slug = $baseSlug;
        $index = 2;

        while ($this->pathExists($org, $this->buildPath($org, $slug, $parentId), $ignorePage)) {
            $slug = "{$baseSlug}-{$index}";
            $index++;
        }

        return $slug;
    }

    public function buildPath(Org $org, string $slug, ?int $parentId = null): string
    {
        if (! $parentId) {
            return $slug;
        }

        $parentPath = Page::query()
            ->where('org_id', $org->id)
            ->whereKey($parentId)
            ->value('path');

        return $parentPath ? "{$parentPath}/{$slug}" : $slug;
    }

    public function nextSiblingSortOrder(Org $org, ?int $parentId = null, ?Page $ignorePage = null): int
    {
        $maxSortOrder = Page::query()
            ->where('org_id', $org->id)
            ->where('parent_id', $parentId)
            ->when($ignorePage, fn ($query) => $query->whereKeyNot($ignorePage->getKey()))
            ->max('sort_order');

        return is_numeric($maxSortOrder) ? (int) $maxSortOrder + 1 : 0;
    }

    public function rebuildSubtreePaths(Page $page): void
    {
        $page->loadMissing('org');

        DB::transaction(function () use ($page): void {
            $this->rebuildPageAndChildren($page->fresh() ?? $page);
        });
    }

    /**
     * Применить полный payload дерева и пересчитать сохранённые поля структуры.
     *
     * @param  Collection<int, array{id: int|string, parent_id?: int|string|null, sort_order: int|string}>  $items
     */
    public function applyReorderPayload(Org $org, Collection $items): void
    {
        DB::transaction(function () use ($org, $items): void {
            $items
                ->groupBy(fn (array $item): int => isset($item['parent_id']) ? (int) $item['parent_id'] : 0)
                ->each(function (Collection $siblingItems) use ($org): void {
                    $siblingItems
                        ->sortBy([
                            ['sort_order', 'asc'],
                            ['id', 'asc'],
                        ])
                        ->values()
                        ->each(function (array $item, int $sortOrder) use ($org): void {
                            Page::query()
                                ->where('org_id', $org->id)
                                ->whereKey((int) $item['id'])
                                ->update([
                                    'parent_id' => isset($item['parent_id']) ? (int) $item['parent_id'] : null,
                                    'sort_order' => $sortOrder,
                                    'needs_generation' => true,
                                ]);
                        });
                });

            /** @var Collection<int, Page> $rootPages */
            $rootPages = Page::query()
                ->where('org_id', $org->id)
                ->whereNull('parent_id')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();

            foreach ($rootPages as $rootPage) {
                $this->rebuildPageAndChildren($rootPage);
            }
        });
    }

    public function pathExists(Org $org, string $path, ?Page $ignorePage = null): bool
    {
        return Page::query()
            ->where('org_id', $org->id)
            ->where('path', $path)
            ->when($ignorePage, fn ($query) => $query->whereKeyNot($ignorePage->getKey()))
            ->exists();
    }

    /**
     * @param  Collection<int, Collection<int, Page>>  $pagesByParent
     * @param  array<int, true>  $visited
     * @return array<int, array{id: int, parent_id: int|null, depth: int, slug: string, path: string, title: string, status: string, seo_title: string|null, meta_description: string|null, noindex: bool, sort_order: int, children_count: int, updated_at: string|null}>
     */
    private function flattenTree(
        Collection $pagesByParent,
        int $parentId = 0,
        int $depth = 0,
        array &$visited = [],
    ): array {
        $rows = [];

        foreach ($pagesByParent->get($parentId, collect()) as $page) {
            if (isset($visited[$page->id])) {
                continue;
            }

            $visited[$page->id] = true;
            $rows[] = [
                'id' => $page->id,
                'parent_id' => $page->parent_id,
                'depth' => $depth,
                'slug' => $page->slug,
                'path' => $page->path,
                'title' => $page->title,
                'status' => $page->status->value,
                'seo_title' => $page->seo_title,
                'meta_description' => $page->meta_description,
                'noindex' => (bool) $page->noindex,
                'sort_order' => (int) $page->sort_order,
                'children_count' => (int) $page->children_count,
                'updated_at' => $page->updated_at?->toISOString(),
            ];

            array_push(
                $rows,
                ...$this->flattenTree($pagesByParent, $page->id, $depth + 1, $visited),
            );
        }

        return $rows;
    }

    private function rebuildPageAndChildren(Page $page, ?string $parentPath = null, int $depth = 0): void
    {
        $path = $parentPath ? "{$parentPath}/{$page->slug}" : $page->slug;

        $page->forceFill([
            'path' => $path,
            'depth' => $depth,
            'needs_generation' => true,
        ])->save();

        /** @var Collection<int, Page> $children */
        $children = $page->children()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        foreach ($children as $child) {
            $this->rebuildPageAndChildren($child, $path, $depth + 1);
        }
    }
}
