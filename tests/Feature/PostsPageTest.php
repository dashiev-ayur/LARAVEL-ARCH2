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

test('authenticated users can create post with generated slug', function () {
    /** @var TestCase $this */
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
        'title' => 'Brand News',
        'slug' => 'brand-news',
    ]);

    $this->actingAs($user)
        ->post(route('posts.store', [
            'current_team' => $team->slug,
            'current_org' => $org->slug,
        ]), [
            'type' => 'news',
            'title' => 'Brand News',
            'status' => 'draft',
            'excerpt' => 'Short intro',
            'content' => 'Full content',
        ])
        ->assertRedirect(route('posts.byType', [
            'current_team' => $team->slug,
            'current_org' => $org->slug,
            'type' => 'news',
        ]));

    $this->assertDatabaseHas('posts', [
        'org_id' => $org->id,
        'author_id' => $user->id,
        'type' => 'news',
        'status' => 'draft',
        'slug' => 'brand-news-2',
        'title' => 'Brand News',
        'excerpt' => 'Short intro',
        'content' => 'Full content',
    ]);
});

test('post creation rejects duplicate slug in the same org and type', function () {
    /** @var TestCase $this */
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
        'title' => 'Existing Page',
        'slug' => 'existing-page',
    ]);

    $this->actingAs($user)
        ->from(route('posts.index', [
            'current_team' => $team->slug,
            'current_org' => $org->slug,
        ]))
        ->post(route('posts.store', [
            'current_team' => $team->slug,
            'current_org' => $org->slug,
        ]), [
            'type' => 'page',
            'title' => 'Another Page',
            'slug' => 'existing-page',
            'status' => 'draft',
        ])
        ->assertRedirect(route('posts.index', [
            'current_team' => $team->slug,
            'current_org' => $org->slug,
        ]))
        ->assertSessionHasErrors('slug');
});

test('authenticated users can update post', function () {
    /** @var TestCase $this */
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $org = $team->orgs()->create([
        'name' => 'Test Org',
        'slug' => 'test-org',
        'status' => 'enabled',
    ]);
    $user->update(['current_org_id' => $org->id]);

    $post = Post::factory()->create([
        'org_id' => $org->id,
        'author_id' => $user->id,
        'type' => 'page',
        'status' => 'draft',
        'title' => 'Old Title',
        'slug' => 'old-title',
        'excerpt' => 'Old excerpt',
        'content' => 'Old content',
    ]);

    $this->actingAs($user)
        ->patch(route('posts.update', [
            'current_team' => $team->slug,
            'current_org' => $org->slug,
            'post' => $post,
        ]), [
            'type' => 'news',
            'title' => 'Updated Title',
            'slug' => 'updated-title',
            'status' => 'published',
            'excerpt' => 'Updated excerpt',
            'content' => 'Updated content',
        ])
        ->assertRedirect(route('posts.byType', [
            'current_team' => $team->slug,
            'current_org' => $org->slug,
            'type' => 'news',
        ]));

    $this->assertDatabaseHas('posts', [
        'id' => $post->id,
        'org_id' => $org->id,
        'author_id' => $user->id,
        'type' => 'news',
        'status' => 'published',
        'slug' => 'updated-title',
        'title' => 'Updated Title',
        'excerpt' => 'Updated excerpt',
        'content' => 'Updated content',
    ]);
});

test('post update rejects duplicate slug in the same org and type', function () {
    /** @var TestCase $this */
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
        'title' => 'Existing Page',
        'slug' => 'existing-page',
    ]);

    $post = Post::factory()->create([
        'org_id' => $org->id,
        'author_id' => $user->id,
        'type' => 'page',
        'title' => 'Edited Page',
        'slug' => 'edited-page',
    ]);

    $this->actingAs($user)
        ->from(route('posts.index', [
            'current_team' => $team->slug,
            'current_org' => $org->slug,
        ]))
        ->patch(route('posts.update', [
            'current_team' => $team->slug,
            'current_org' => $org->slug,
            'post' => $post,
        ]), [
            'type' => 'page',
            'title' => 'Edited Page',
            'slug' => 'existing-page',
            'status' => 'draft',
        ])
        ->assertRedirect(route('posts.index', [
            'current_team' => $team->slug,
            'current_org' => $org->slug,
        ]))
        ->assertSessionHasErrors('slug');
});

test('authenticated users can delete draft post', function () {
    /** @var TestCase $this */
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $org = $team->orgs()->create([
        'name' => 'Test Org',
        'slug' => 'test-org',
        'status' => 'enabled',
    ]);
    $user->update(['current_org_id' => $org->id]);

    $post = Post::factory()->create([
        'org_id' => $org->id,
        'author_id' => $user->id,
        'type' => 'page',
        'status' => 'draft',
        'title' => 'Draft Page',
        'slug' => 'draft-page',
    ]);

    $this->actingAs($user)
        ->delete(route('posts.destroy', [
            'current_team' => $team->slug,
            'current_org' => $org->slug,
            'post' => $post,
        ]))
        ->assertRedirect(route('posts.byType', [
            'current_team' => $team->slug,
            'current_org' => $org->slug,
            'type' => 'page',
        ]));

    $this->assertSoftDeleted('posts', [
        'id' => $post->id,
    ]);
});

test('post deletion rejects non draft post', function () {
    /** @var TestCase $this */
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $org = $team->orgs()->create([
        'name' => 'Test Org',
        'slug' => 'test-org',
        'status' => 'enabled',
    ]);
    $user->update(['current_org_id' => $org->id]);

    $post = Post::factory()->create([
        'org_id' => $org->id,
        'author_id' => $user->id,
        'type' => 'page',
        'status' => 'published',
        'title' => 'Published Page',
        'slug' => 'published-page',
    ]);

    $this->actingAs($user)
        ->delete(route('posts.destroy', [
            'current_team' => $team->slug,
            'current_org' => $org->slug,
            'post' => $post,
        ]))
        ->assertForbidden();

    $this->assertNotSoftDeleted('posts', [
        'id' => $post->id,
    ]);
});

test('post creation validates required title', function () {
    /** @var TestCase $this */
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $org = $team->orgs()->create([
        'name' => 'Test Org',
        'slug' => 'test-org',
        'status' => 'enabled',
    ]);
    $user->update(['current_org_id' => $org->id]);

    $this->actingAs($user)
        ->from(route('posts.index', [
            'current_team' => $team->slug,
            'current_org' => $org->slug,
        ]))
        ->post(route('posts.store', [
            'current_team' => $team->slug,
            'current_org' => $org->slug,
        ]), [
            'type' => 'page',
            'title' => '',
            'status' => 'draft',
        ])
        ->assertRedirect(route('posts.index', [
            'current_team' => $team->slug,
            'current_org' => $org->slug,
        ]))
        ->assertSessionHasErrors('title');
});
