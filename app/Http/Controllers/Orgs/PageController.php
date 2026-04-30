<?php

namespace App\Http\Controllers\Orgs;

use App\Enums\PageStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Pages\ReorderPagesRequest;
use App\Http\Requests\Pages\StorePageRequest;
use App\Http\Requests\Pages\UpdatePageRequest;
use App\Models\Org;
use App\Models\Page;
use App\Services\Pages\PageTreeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
{
    public function __construct(private readonly PageTreeService $pageTreeService) {}

    /**
     * Показать страницу управления структурой сайта.
     */
    public function index(Request $request, string $current_team, string $current_org): Response
    {
        $org = $this->resolveCurrentOrg($request, $current_org);

        /** @var Collection<int, Page> $pages */
        $pages = $org->pages()
            ->select(['id', 'parent_id', 'slug', 'path', 'depth', 'title', 'status', 'seo_title', 'meta_description', 'noindex', 'sort_order', 'updated_at'])
            ->withCount('children')
            ->orderBy('sort_order')
            ->orderBy('title')
            ->orderBy('id')
            ->get();

        return Inertia::render('structure/index', [
            'pages' => $this->pageTreeService->flattenTreeRows($pages),
            'pageStatuses' => PageStatus::values(),
        ]);
    }

    /**
     * Сохранить новую страницу текущей организации.
     */
    public function store(
        StorePageRequest $request,
        string $current_team,
        string $current_org,
    ): RedirectResponse {
        $org = $this->resolveCurrentOrg($request, $current_org);
        $validated = $request->validated();
        $title = (string) $validated['title'];
        $parentId = isset($validated['parent_id']) ? (int) $validated['parent_id'] : null;
        $slug = trim((string) ($validated['slug'] ?? ''));
        $slug = $slug !== '' ? $slug : $this->pageTreeService->buildUniqueSlug($org, $title, $parentId);

        Page::query()->create([
            'org_id' => $org->id,
            'author_id' => $request->user()?->id,
            'parent_id' => $parentId,
            'status' => (string) $validated['status'],
            'sort_order' => $this->pageTreeService->nextSiblingSortOrder($org, $parentId),
            'slug' => $slug,
            'path' => $this->pageTreeService->buildPath($org, $slug, $parentId),
            'depth' => $this->depthForParent($org, $parentId),
            'title' => $title,
            'seo_title' => $validated['seo_title'] ?? null,
            'meta_description' => $validated['meta_description'] ?? null,
            'noindex' => (bool) ($validated['noindex'] ?? false),
            'needs_generation' => true,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Page created.')]);

        return $this->redirectToPagesIndex($current_team, $org);
    }

    /**
     * Обновить страницу текущей организации.
     */
    public function update(
        UpdatePageRequest $request,
        string $current_team,
        string $current_org,
        Page $page,
    ): RedirectResponse {
        $org = $this->resolveCurrentOrg($request, $current_org);
        abort_unless((int) $page->org_id === (int) $org->id, 404);

        $validated = $request->validated();
        $title = (string) $validated['title'];
        $parentId = array_key_exists('parent_id', $validated)
            ? (isset($validated['parent_id']) ? (int) $validated['parent_id'] : null)
            : $page->parent_id;
        $slug = trim((string) ($validated['slug'] ?? ''));
        $slug = $slug !== '' ? $slug : $this->pageTreeService->buildUniqueSlug($org, $title, $parentId, $page);
        $structureChanged = $slug !== $page->slug || (int) ($page->parent_id ?? 0) !== (int) ($parentId ?? 0);

        $page->update([
            'parent_id' => $parentId,
            'status' => (string) $validated['status'],
            'sort_order' => $structureChanged
                ? $this->pageTreeService->nextSiblingSortOrder($org, $parentId, $page)
                : $page->sort_order,
            'slug' => $slug,
            'path' => $this->pageTreeService->buildPath($org, $slug, $parentId),
            'depth' => $this->depthForParent($org, $parentId),
            'title' => $title,
            'seo_title' => $validated['seo_title'] ?? null,
            'meta_description' => $validated['meta_description'] ?? null,
            'noindex' => (bool) ($validated['noindex'] ?? false),
            'needs_generation' => true,
        ]);

        if ($structureChanged) {
            $this->pageTreeService->rebuildSubtreePaths($page);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Page updated.')]);

        return $this->redirectToPagesIndex($current_team, $org);
    }

    /**
     * Изменить порядок и вложенность страниц текущей организации.
     */
    public function reorder(
        ReorderPagesRequest $request,
        string $current_team,
        string $current_org,
    ): RedirectResponse {
        $org = $this->resolveCurrentOrg($request, $current_org);

        /** @var Collection<int, array{id: int|string, parent_id?: int|string|null, sort_order: int|string}> $items */
        $items = collect($request->validated('items'));
        $this->pageTreeService->applyReorderPayload($org, $items);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Page order updated.')]);

        return $this->redirectToPagesIndex($current_team, $org);
    }

    /**
     * Удалить leaf-страницу текущей организации.
     */
    public function destroy(
        Request $request,
        string $current_team,
        string $current_org,
        Page $page,
    ): RedirectResponse {
        $org = $this->resolveCurrentOrg($request, $current_org);
        abort_unless((int) $page->org_id === (int) $org->id, 404);

        if ($page->children()->exists()) {
            throw ValidationException::withMessages([
                'page' => __('Cannot delete a page that has child pages.'),
            ]);
        }

        $page->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Page deleted.')]);

        return $this->redirectToPagesIndex($current_team, $org);
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

    private function redirectToPagesIndex(string $currentTeamSlug, Org $org): RedirectResponse
    {
        return to_route('pages.index', [
            'current_team' => $currentTeamSlug,
            'current_org' => $org->slug,
        ]);
    }

    private function depthForParent(Org $org, ?int $parentId): int
    {
        if (! $parentId) {
            return 0;
        }

        $parentDepth = Page::query()
            ->where('org_id', $org->id)
            ->whereKey($parentId)
            ->value('depth');

        return is_numeric($parentDepth) ? (int) $parentDepth + 1 : 0;
    }
}
