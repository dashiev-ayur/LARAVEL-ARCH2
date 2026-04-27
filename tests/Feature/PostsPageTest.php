<?php

use App\Models\Post;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

test('guests are redirected to login from posts page', function () {
    /** @var TestCase $this */
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $org = $team->orgs()->create([
        'name' => 'Test Org',
        'slug' => 'test-org',
        'status' => 'enabled',
    ]);

    $response = $this->get(route('posts.index', [
        'current_team' => $team->slug,
        'current_org' => $org->slug,
    ]));

    $response->assertRedirect(route('login'));
});

test('authenticated users can open posts page', function () {
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
        ->get(route('posts.index', [
            'current_team' => $team->slug,
            'current_org' => $org->slug,
        ]));

    $response->assertOk();
});

test('posts page filters records by type from url', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $org = $team->orgs()->create([
        'name' => 'Test Org',
        'slug' => 'test-org',
        'status' => 'enabled',
    ]);
    $user->update(['current_org_id' => $org->id]);

    Post::factory()->create([
        'org_id' => $org->id,
        'author_id' => $user->id,
        'type' => 'news',
        'title' => 'News Post',
        'excerpt' => 'News Post Excerpt',
        'slug' => 'news-post',
    ]);
    Post::factory()->create([
        'org_id' => $org->id,
        'author_id' => $user->id,
        'type' => 'page',
        'title' => 'Page Post',
        'slug' => 'page-post',
    ]);

    $this->actingAs($user)
        ->get(route('posts.byType', [
            'current_team' => $team->slug,
            'current_org' => $org->slug,
            'type' => 'news',
        ]))
        ->assertInertia(fn (Assert $page) => $page
            ->component('posts/index')
            ->where('activeType', 'news')
            ->has('postTypeUi', 4)
            ->where('postTypeUi.news.filterButtonTitle', 'Новости')
            ->where('postTypeUi.news.newButtonTitle', 'Новая новость')
            ->has('posts', 1)
            ->where('posts.0.title', 'News Post')
            ->where('posts.0.excerpt', 'News Post Excerpt')
            ->where('posts.0.type', 'news')
            ->has('postTypes', 4),
        );
});

test('posts page defaults to page type when type is omitted', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $org = $team->orgs()->create([
        'name' => 'Test Org',
        'slug' => 'test-org',
        'status' => 'enabled',
    ]);
    $user->update(['current_org_id' => $org->id]);

    Post::factory()->create([
        'org_id' => $org->id,
        'author_id' => $user->id,
        'type' => 'page',
        'title' => 'Default Page Post',
        'slug' => 'default-page-post',
    ]);
    Post::factory()->create([
        'org_id' => $org->id,
        'author_id' => $user->id,
        'type' => 'article',
        'title' => 'Other Type Post',
        'slug' => 'other-type-post',
    ]);

    $this->actingAs($user)
        ->get(route('posts.index', [
            'current_team' => $team->slug,
            'current_org' => $org->slug,
        ]))
        ->assertInertia(fn (Assert $page) => $page
            ->component('posts/index')
            ->where('activeType', 'page')
            ->where('postTypeUi.page.filterButtonTitle', 'Страницы')
            ->where('postTypeUi.page.newButtonTitle', 'Новая страница')
            ->has('posts', 1)
            ->where('posts.0.title', 'Default Page Post')
            ->where('posts.0.type', 'page')
            ->where('postsPagination.currentPage', 1)
            ->where('postsPagination.lastPage', 1)
            ->where('postsPagination.perPage', 8)
            ->where('postsPagination.total', 1)
            ->where('postsSorting.sortBy', 'id')
            ->where('postsSorting.sortDirection', 'desc'),
        );
});

test('posts page uses page query for server pagination', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $org = $team->orgs()->create([
        'name' => 'Test Org',
        'slug' => 'test-org',
        'status' => 'enabled',
    ]);
    $user->update(['current_org_id' => $org->id]);

    foreach (range(1, 12) as $index) {
        Post::factory()->create([
            'org_id' => $org->id,
            'author_id' => $user->id,
            'type' => 'page',
            'title' => "Post {$index}",
            'slug' => "post-{$index}",
        ]);
    }

    $this->actingAs($user)
        ->get(route('posts.index', [
            'current_team' => $team->slug,
            'current_org' => $org->slug,
            'page' => 2,
        ]))
        ->assertInertia(fn (Assert $page) => $page
            ->component('posts/index')
            ->where('postsPagination.currentPage', 2)
            ->where('postsPagination.lastPage', 2)
            ->where('postsPagination.perPage', 8)
            ->where('postsPagination.total', 12)
            ->where('postsSorting.sortBy', 'id')
            ->where('postsSorting.sortDirection', 'desc')
            ->has('posts', 4)
            ->where('posts.0.title', 'Post 4')
            ->where('posts.1.title', 'Post 3'),
        );
});

test('posts page applies per_page and column filters from query', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $org = $team->orgs()->create([
        'name' => 'Test Org',
        'slug' => 'test-org',
        'status' => 'enabled',
    ]);
    $user->update(['current_org_id' => $org->id]);

    foreach (range(1, 30) as $index) {
        Post::factory()->create([
            'org_id' => $org->id,
            'author_id' => $user->id,
            'type' => 'page',
            'status' => $index % 2 === 0 ? 'published' : 'draft',
            'title' => $index === 30 ? 'Filtered Title' : "Post {$index}",
            'slug' => "post-{$index}",
        ]);
    }

    $this->actingAs($user)
        ->get(route('posts.index', [
            'current_team' => $team->slug,
            'current_org' => $org->slug,
            'per_page' => 25,
            'filter_title' => 'Filtered',
            'filter_status' => 'published',
        ]))
        ->assertInertia(fn (Assert $page) => $page
            ->component('posts/index')
            ->where('postsPagination.currentPage', 1)
            ->where('postsPagination.lastPage', 1)
            ->where('postsPagination.perPage', 25)
            ->where('postsPagination.total', 1)
            ->where('postsFilters.title', 'Filtered')
            ->where('postsFilters.status', 'published')
            ->where('postsFilters.search', '')
            ->where('postsSorting.sortBy', 'id')
            ->where('postsSorting.sortDirection', 'desc')
            ->has('posts', 1)
            ->where('posts.0.title', 'Filtered Title')
            ->where('posts.0.status', 'published'),
        );
});

test('posts page applies sorting from query', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $org = $team->orgs()->create([
        'name' => 'Test Org',
        'slug' => 'test-org',
        'status' => 'enabled',
    ]);
    $user->update(['current_org_id' => $org->id]);

    Post::factory()->create([
        'org_id' => $org->id,
        'author_id' => $user->id,
        'type' => 'page',
        'title' => 'Beta Title',
        'slug' => 'beta-title',
    ]);
    Post::factory()->create([
        'org_id' => $org->id,
        'author_id' => $user->id,
        'type' => 'page',
        'title' => 'Alpha Title',
        'slug' => 'alpha-title',
    ]);

    $this->actingAs($user)
        ->get(route('posts.index', [
            'current_team' => $team->slug,
            'current_org' => $org->slug,
            'sort_by' => 'title',
            'sort_direction' => 'asc',
        ]))
        ->assertInertia(fn (Assert $page) => $page
            ->component('posts/index')
            ->where('postsSorting.sortBy', 'title')
            ->where('postsSorting.sortDirection', 'asc')
            ->has('posts', 2)
            ->where('posts.0.title', 'Alpha Title')
            ->where('posts.1.title', 'Beta Title'),
        );
});

test('posts page applies search query', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $org = $team->orgs()->create([
        'name' => 'Test Org',
        'slug' => 'test-org',
        'status' => 'enabled',
    ]);
    $user->update(['current_org_id' => $org->id]);

    Post::factory()->create([
        'org_id' => $org->id,
        'author_id' => $user->id,
        'type' => 'page',
        'title' => 'Laravel Searchable Post',
        'slug' => 'laravel-searchable-post',
    ]);
    Post::factory()->create([
        'org_id' => $org->id,
        'author_id' => $user->id,
        'type' => 'page',
        'title' => 'Unrelated Title',
        'slug' => 'unrelated-title',
    ]);

    $this->actingAs($user)
        ->get(route('posts.index', [
            'current_team' => $team->slug,
            'current_org' => $org->slug,
            'search' => 'searchable',
        ]))
        ->assertInertia(fn (Assert $page) => $page
            ->component('posts/index')
            ->where('postsFilters.search', 'searchable')
            ->has('posts', 1)
            ->where('posts.0.title', 'Laravel Searchable Post'),
        );
});
