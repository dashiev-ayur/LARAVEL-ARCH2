<?php

use App\Models\Category;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

test('guests are redirected to login from categories page', function () {
    /** @var TestCase $this */
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $org = $team->orgs()->create([
        'name' => 'Test Org',
        'slug' => 'test-org',
        'status' => 'enabled',
    ]);

    $response = $this->get(route('categories.index', [
        'current_team' => $team->slug,
        'current_org' => $org->slug,
    ]));

    $response->assertRedirect(route('login'));
});

test('authenticated users can open categories page', function () {
    /** @var TestCase $this */
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $org = $team->orgs()->create([
        'name' => 'Test Org',
        'slug' => 'test-org',
        'status' => 'enabled',
    ]);
    $user->update(['current_org_id' => $org->id]);

    $response = $this
        ->actingAs($user)
        ->get(route('categories.index', [
            'current_team' => $team->slug,
            'current_org' => $org->slug,
        ]));

    $response->assertOk();
});

test('categories page exposes categories in tree order', function () {
    /** @var TestCase $this */
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $org = $team->orgs()->create([
        'name' => 'Test Org',
        'slug' => 'test-org',
        'status' => 'enabled',
    ]);
    $user->update(['current_org_id' => $org->id]);

    $rootCategory = Category::factory()->create([
        'org_id' => $org->id,
        'type' => 'page',
        'title' => 'Root Category',
        'slug' => 'root-category',
    ]);
    Category::factory()->create([
        'org_id' => $org->id,
        'type' => 'page',
        'title' => 'Second Root',
        'slug' => 'second-root',
    ]);
    Category::factory()->create([
        'org_id' => $org->id,
        'parent_id' => $rootCategory->id,
        'type' => 'page',
        'title' => 'Child Category',
        'slug' => 'child-category',
    ]);
    Category::factory()->create([
        'org_id' => $org->id,
        'type' => 'news',
        'title' => 'News Category',
        'slug' => 'news-category',
    ]);

    $this->actingAs($user)
        ->get(route('categories.index', [
            'current_team' => $team->slug,
            'current_org' => $org->slug,
        ]))
        ->assertInertia(fn (Assert $page) => $page
            ->component('categories/index')
            ->where('activeType', 'page')
            ->has('postTypes', 4)
            ->has('postTypeUi', 4)
            ->where('postTypeUi.page.filterButtonTitle', 'Страницы')
            ->has('categories', 3)
            ->where('categories.0.title', 'Root Category')
            ->where('categories.0.depth', 0)
            ->where('categories.0.parent_id', null)
            ->where('categories.1.title', 'Child Category')
            ->where('categories.1.depth', 1)
            ->where('categories.1.parent_id', $rootCategory->id)
            ->where('categories.1.type', 'page')
            ->where('categories.1.slug', 'child-category')
            ->where('categories.2.title', 'Second Root')
            ->where('categories.2.depth', 0),
        );
});

test('categories page filters categories by type from url', function () {
    /** @var TestCase $this */
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $org = $team->orgs()->create([
        'name' => 'Test Org',
        'slug' => 'test-org',
        'status' => 'enabled',
    ]);
    $user->update(['current_org_id' => $org->id]);

    $pageRootCategory = Category::factory()->create([
        'org_id' => $org->id,
        'type' => 'page',
        'title' => 'Page Root',
        'slug' => 'page-root',
    ]);
    Category::factory()->create([
        'org_id' => $org->id,
        'parent_id' => $pageRootCategory->id,
        'type' => 'news',
        'title' => 'News Child',
        'slug' => 'news-child',
    ]);
    Category::factory()->create([
        'org_id' => $org->id,
        'type' => 'news',
        'title' => 'News Root',
        'slug' => 'news-root',
    ]);

    $this->actingAs($user)
        ->get(route('categories.byType', [
            'current_team' => $team->slug,
            'current_org' => $org->slug,
            'type' => 'news',
        ]))
        ->assertInertia(fn (Assert $page) => $page
            ->component('categories/index')
            ->where('activeType', 'news')
            ->where('postTypeUi.news.filterButtonTitle', 'Новости')
            ->has('postTypes', 4)
            ->has('categories', 2)
            ->where('categories.0.title', 'News Child')
            ->where('categories.0.depth', 0)
            ->where('categories.0.type', 'news')
            ->where('categories.1.title', 'News Root')
            ->where('categories.1.depth', 0)
            ->where('categories.1.type', 'news'),
        );
});
