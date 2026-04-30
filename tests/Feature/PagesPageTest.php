<?php

use App\Enums\PageStatus;
use App\Models\Page;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

function createPagesTestContext(): array
{
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $org = $team->orgs()->create([
        'name' => 'Test Org',
        'slug' => 'test-org',
        'status' => 'enabled',
    ]);
    $user->update(['current_org_id' => $org->id]);

    return [$user, $team, $org];
}

test('guests are redirected to login from pages page', function () {
    /** @var TestCase $this */
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $org = $team->orgs()->create([
        'name' => 'Test Org',
        'slug' => 'test-org',
        'status' => 'enabled',
    ]);

    $this->get(route('pages.index', [
        'current_team' => $team->slug,
        'current_org' => $org->slug,
    ]))->assertRedirect(route('login'));
});

test('pages page uses structure URL', function () {
    /** @var TestCase $this */
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $org = $team->orgs()->create([
        'name' => 'Test Org',
        'slug' => 'test-org',
        'status' => 'enabled',
    ]);

    expect(route('pages.index', [
        'current_team' => $team->slug,
        'current_org' => $org->slug,
    ], false))->toBe('/'.$team->slug.'/'.$org->slug.'/structure');
});

test('authenticated users can open pages page', function () {
    /** @var TestCase $this */
    [$user, $team, $org] = createPagesTestContext();

    $rootPage = Page::factory()->create([
        'org_id' => $org->id,
        'title' => 'About',
        'slug' => 'about',
        'path' => 'about',
        'status' => PageStatus::Draft,
        'sort_order' => 0,
    ]);
    Page::factory()->create([
        'org_id' => $org->id,
        'parent_id' => $rootPage->id,
        'title' => 'Team',
        'slug' => 'team',
        'path' => 'about/team',
        'depth' => 1,
        'status' => PageStatus::Published,
    ]);

    $this->actingAs($user)
        ->get(route('pages.index', [
            'current_team' => $team->slug,
            'current_org' => $org->slug,
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('structure/index')
            ->has('pages', 2)
            ->where('pages.0.title', 'About')
            ->where('pages.0.depth', 0)
            ->where('pages.0.path', 'about')
            ->where('pages.0.children_count', 1)
            ->where('pages.1.title', 'Team')
            ->where('pages.1.parent_id', $rootPage->id)
            ->where('pages.1.depth', 1)
            ->where('pages.1.path', 'about/team')
            ->where('pages.1.status', 'published')
            ->has('pageStatuses', 3),
        );
});

test('authenticated users can create root page with generated slug', function () {
    /** @var TestCase $this */
    [$user, $team, $org] = createPagesTestContext();

    Page::factory()->create([
        'org_id' => $org->id,
        'title' => 'About',
        'slug' => 'about',
        'path' => 'about',
    ]);

    $this->actingAs($user)
        ->post(route('pages.store', [
            'current_team' => $team->slug,
            'current_org' => $org->slug,
        ]), [
            'title' => 'About',
            'status' => 'draft',
            'seo_title' => 'About SEO',
            'meta_description' => 'Short description',
            'noindex' => true,
        ])
        ->assertRedirect(route('pages.index', [
            'current_team' => $team->slug,
            'current_org' => $org->slug,
        ]));

    $this->assertDatabaseHas('pages', [
        'org_id' => $org->id,
        'parent_id' => null,
        'slug' => 'about-2',
        'path' => 'about-2',
        'depth' => 0,
        'title' => 'About',
        'status' => 'draft',
        'seo_title' => 'About SEO',
        'meta_description' => 'Short description',
        'noindex' => true,
        'needs_generation' => true,
    ]);
});

test('authenticated users can create child page with structure fields', function () {
    /** @var TestCase $this */
    [$user, $team, $org] = createPagesTestContext();

    $parentPage = Page::factory()->create([
        'org_id' => $org->id,
        'title' => 'About',
        'slug' => 'about',
        'path' => 'about',
        'depth' => 0,
    ]);
    Page::factory()->create([
        'org_id' => $org->id,
        'parent_id' => $parentPage->id,
        'slug' => 'history',
        'path' => 'about/history',
        'depth' => 1,
        'sort_order' => 4,
    ]);

    $this->actingAs($user)
        ->post(route('pages.store', [
            'current_team' => $team->slug,
            'current_org' => $org->slug,
        ]), [
            'parent_id' => $parentPage->id,
            'title' => 'Team',
            'slug' => 'team',
            'status' => 'review',
        ])
        ->assertRedirect(route('pages.index', [
            'current_team' => $team->slug,
            'current_org' => $org->slug,
        ]));

    $this->assertDatabaseHas('pages', [
        'org_id' => $org->id,
        'parent_id' => $parentPage->id,
        'slug' => 'team',
        'path' => 'about/team',
        'depth' => 1,
        'sort_order' => 5,
        'status' => 'review',
    ]);
});

test('updating parent slug rebuilds child paths', function () {
    /** @var TestCase $this */
    [$user, $team, $org] = createPagesTestContext();

    $parentPage = Page::factory()->create([
        'org_id' => $org->id,
        'title' => 'About',
        'slug' => 'about',
        'path' => 'about',
        'depth' => 0,
    ]);
    $childPage = Page::factory()->create([
        'org_id' => $org->id,
        'parent_id' => $parentPage->id,
        'title' => 'Team',
        'slug' => 'team',
        'path' => 'about/team',
        'depth' => 1,
    ]);

    $this->actingAs($user)
        ->patch(route('pages.update', [
            'current_team' => $team->slug,
            'current_org' => $org->slug,
            'page' => $parentPage->id,
        ]), [
            'title' => 'Company',
            'slug' => 'company',
            'status' => 'published',
        ])
        ->assertRedirect(route('pages.index', [
            'current_team' => $team->slug,
            'current_org' => $org->slug,
        ]));

    $this->assertDatabaseHas('pages', [
        'id' => $parentPage->id,
        'slug' => 'company',
        'path' => 'company',
        'depth' => 0,
        'status' => 'published',
        'needs_generation' => true,
    ]);
    $this->assertDatabaseHas('pages', [
        'id' => $childPage->id,
        'path' => 'company/team',
        'depth' => 1,
        'needs_generation' => true,
    ]);
});

test('reorder saves page order nesting and paths', function () {
    /** @var TestCase $this */
    [$user, $team, $org] = createPagesTestContext();

    $aboutPage = Page::factory()->create([
        'org_id' => $org->id,
        'title' => 'About',
        'slug' => 'about',
        'path' => 'about',
        'sort_order' => 0,
    ]);
    $servicesPage = Page::factory()->create([
        'org_id' => $org->id,
        'title' => 'Services',
        'slug' => 'services',
        'path' => 'services',
        'sort_order' => 1,
    ]);
    $consultingPage = Page::factory()->create([
        'org_id' => $org->id,
        'parent_id' => $servicesPage->id,
        'title' => 'Consulting',
        'slug' => 'consulting',
        'path' => 'services/consulting',
        'depth' => 1,
    ]);

    $this->actingAs($user)
        ->patch(route('pages.reorder', [
            'current_team' => $team->slug,
            'current_org' => $org->slug,
        ]), [
            'items' => [
                ['id' => $aboutPage->id, 'parent_id' => null, 'sort_order' => 0],
                ['id' => $servicesPage->id, 'parent_id' => $aboutPage->id, 'sort_order' => 0],
                ['id' => $consultingPage->id, 'parent_id' => $servicesPage->id, 'sort_order' => 0],
            ],
        ])
        ->assertRedirect(route('pages.index', [
            'current_team' => $team->slug,
            'current_org' => $org->slug,
        ]));

    $this->assertDatabaseHas('pages', [
        'id' => $servicesPage->id,
        'parent_id' => $aboutPage->id,
        'sort_order' => 0,
        'path' => 'about/services',
        'depth' => 1,
    ]);
    $this->assertDatabaseHas('pages', [
        'id' => $consultingPage->id,
        'parent_id' => $servicesPage->id,
        'path' => 'about/services/consulting',
        'depth' => 2,
    ]);
});

test('reorder validation rejects incomplete duplicate cyclic and foreign payloads', function () {
    /** @var TestCase $this */
    [$user, $team, $org] = createPagesTestContext();
    $otherOrg = $team->orgs()->create([
        'name' => 'Other Org',
        'slug' => 'other-org',
        'status' => 'enabled',
    ]);

    $firstPage = Page::factory()->create(['org_id' => $org->id]);
    $secondPage = Page::factory()->create(['org_id' => $org->id]);
    $foreignPage = Page::factory()->create(['org_id' => $otherOrg->id]);

    $route = route('pages.reorder', [
        'current_team' => $team->slug,
        'current_org' => $org->slug,
    ]);

    $this->actingAs($user)
        ->patch($route, [
            'items' => [
                ['id' => $firstPage->id, 'parent_id' => null, 'sort_order' => 0],
            ],
        ])
        ->assertSessionHasErrors('items');

    $this->actingAs($user)
        ->patch($route, [
            'items' => [
                ['id' => $firstPage->id, 'parent_id' => null, 'sort_order' => 0],
                ['id' => $firstPage->id, 'parent_id' => null, 'sort_order' => 1],
            ],
        ])
        ->assertSessionHasErrors('items');

    $this->actingAs($user)
        ->patch($route, [
            'items' => [
                ['id' => $firstPage->id, 'parent_id' => $secondPage->id, 'sort_order' => 0],
                ['id' => $secondPage->id, 'parent_id' => $firstPage->id, 'sort_order' => 0],
            ],
        ])
        ->assertSessionHasErrors('items.0.parent_id');

    $this->actingAs($user)
        ->patch($route, [
            'items' => [
                ['id' => $firstPage->id, 'parent_id' => null, 'sort_order' => 0],
                ['id' => $foreignPage->id, 'parent_id' => null, 'sort_order' => 1],
            ],
        ])
        ->assertSessionHasErrors('items.1.id');
});

test('authenticated users can delete leaf page', function () {
    /** @var TestCase $this */
    [$user, $team, $org] = createPagesTestContext();

    $page = Page::factory()->create([
        'org_id' => $org->id,
        'title' => 'Leaf Page',
    ]);

    $this->actingAs($user)
        ->delete(route('pages.destroy', [
            'current_team' => $team->slug,
            'current_org' => $org->slug,
            'page' => $page->id,
        ]))
        ->assertRedirect(route('pages.index', [
            'current_team' => $team->slug,
            'current_org' => $org->slug,
        ]));

    $this->assertSoftDeleted('pages', ['id' => $page->id]);
});

test('users cannot delete page with children', function () {
    /** @var TestCase $this */
    [$user, $team, $org] = createPagesTestContext();

    $parentPage = Page::factory()->create(['org_id' => $org->id]);
    Page::factory()->create([
        'org_id' => $org->id,
        'parent_id' => $parentPage->id,
        'path' => "{$parentPage->path}/child",
        'depth' => 1,
    ]);

    $this->actingAs($user)
        ->delete(route('pages.destroy', [
            'current_team' => $team->slug,
            'current_org' => $org->slug,
            'page' => $parentPage->id,
        ]))
        ->assertSessionHasErrors('page');

    $this->assertNotSoftDeleted('pages', ['id' => $parentPage->id]);
});
