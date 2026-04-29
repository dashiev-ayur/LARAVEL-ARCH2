<?php

use App\Models\Category;
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

test('guests are redirected to login from post create page', function () {
    /** @var TestCase $this */
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $org = $team->orgs()->create([
        'name' => 'Test Org',
        'slug' => 'test-org',
        'status' => 'enabled',
    ]);

    $response = $this->get(route('posts.create', [
        'current_team' => $team->slug,
        'current_org' => $org->slug,
    ]));

    $response->assertRedirect(route('login'));
});

test('authenticated users can open post create page', function () {
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
        ->get(route('posts.create', [
            'current_team' => $team->slug,
            'current_org' => $org->slug,
            'type' => 'news',
            'page' => 2,
            'per_page' => 10,
            'search' => 'draft',
            'filter_status' => 'draft',
            'sort_by' => 'updated_at',
            'sort_direction' => 'desc',
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('posts/edit')
            ->where('activeType', 'news')
            ->where('post', null)
            ->where('postTypeUi.news.filterButtonTitle', 'Новости')
            ->where('postTypeUi.news.newButtonTitle', 'Новая новость')
            ->has('postTypes', 4)
            ->where('postsListQuery.page', 2)
            ->where('postsListQuery.per_page', 10)
            ->where('postsListQuery.search', 'draft')
            ->where('postsListQuery.filter_status', 'draft')
            ->where('postsListQuery.sort_by', 'updated_at')
            ->where('postsListQuery.sort_direction', 'desc'),
        );
});

test('guests are redirected to login from post edit page', function () {
    /** @var TestCase $this */
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $org = $team->orgs()->create([
        'name' => 'Test Org',
        'slug' => 'test-org',
        'status' => 'enabled',
    ]);
    $post = Post::factory()->create([
        'org_id' => $org->id,
        'author_id' => $user->id,
        'type' => 'news',
        'title' => 'Editable News',
        'slug' => 'editable-news',
    ]);

    $response = $this->get(route('posts.edit', [
        'current_team' => $team->slug,
        'current_org' => $org->slug,
        'post' => $post,
    ]));

    $response->assertRedirect(route('login'));
});

test('authenticated users can open post edit page', function () {
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
        'type' => 'news',
        'status' => 'draft',
        'title' => 'Editable News',
        'slug' => 'editable-news',
        'excerpt' => 'Editable excerpt',
        'content' => 'Editable content',
    ]);
    $parentCategory = Category::factory()->create([
        'org_id' => $org->id,
        'type' => 'news',
        'title' => 'Parent Category',
        'slug' => 'parent-category',
    ]);
    $childCategory = Category::factory()->create([
        'org_id' => $org->id,
        'parent_id' => $parentCategory->id,
        'type' => 'news',
        'title' => 'Child Category',
        'slug' => 'child-category',
    ]);
    Category::factory()->create([
        'org_id' => $org->id,
        'type' => 'page',
        'title' => 'Page Category',
        'slug' => 'page-category',
    ]);
    $post->categories()->attach($childCategory->id);

    $this->actingAs($user)
        ->get(route('posts.edit', [
            'current_team' => $team->slug,
            'current_org' => $org->slug,
            'post' => $post,
            'page' => 3,
            'per_page' => 25,
            'search' => 'editable',
            'filter_title' => 'News',
            'filter_status' => 'draft',
            'filter_published_at' => '2026-04-29',
            'filter_updated_at' => '2026-04-30',
            'sort_by' => 'title',
            'sort_direction' => 'asc',
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('posts/edit')
            ->where('activeType', 'news')
            ->where('postTypeUi.news.filterButtonTitle', 'Новости')
            ->where('postTypeUi.news.newButtonTitle', 'Новая новость')
            ->has('postTypes', 4)
            ->where('post.id', $post->id)
            ->where('post.type', 'news')
            ->where('post.status', 'draft')
            ->where('post.title', 'Editable News')
            ->where('post.slug', 'editable-news')
            ->where('post.excerpt', 'Editable excerpt')
            ->where('post.content', 'Editable content')
            ->has('categories', 2)
            ->where('categories.0.id', $parentCategory->id)
            ->where('categories.0.depth', 0)
            ->where('categories.0.is_linked', false)
            ->where('categories.1.id', $childCategory->id)
            ->where('categories.1.depth', 1)
            ->where('categories.1.is_linked', true)
            ->where('postsListQuery.page', 3)
            ->where('postsListQuery.per_page', 25)
            ->where('postsListQuery.search', 'editable')
            ->where('postsListQuery.filter_title', 'News')
            ->where('postsListQuery.filter_status', 'draft')
            ->where('postsListQuery.filter_published_at', '2026-04-29')
            ->where('postsListQuery.filter_updated_at', '2026-04-30')
            ->where('postsListQuery.sort_by', 'title')
            ->where('postsListQuery.sort_direction', 'asc'),
        );
});

test('authenticated users can update post category links', function () {
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
        'type' => 'news',
        'title' => 'Linked News',
        'slug' => 'linked-news',
    ]);
    $oldCategory = Category::factory()->create([
        'org_id' => $org->id,
        'type' => 'news',
        'title' => 'Old Category',
        'slug' => 'old-category',
    ]);
    $newCategory = Category::factory()->create([
        'org_id' => $org->id,
        'type' => 'news',
        'title' => 'New Category',
        'slug' => 'new-category',
    ]);
    $post->categories()->attach($oldCategory->id);

    $this->actingAs($user)
        ->patch(route('posts.categories.update', [
            'current_team' => $team->slug,
            'current_org' => $org->slug,
            'post' => $post,
            'page' => 3,
            'per_page' => 25,
            'search' => 'linked',
            'sort_by' => 'title',
        ]), [
            'category_ids' => [$newCategory->id],
        ])
        ->assertRedirect(route('posts.edit', [
            'current_team' => $team->slug,
            'current_org' => $org->slug,
            'post' => $post,
            'page' => 3,
            'per_page' => 25,
            'search' => 'linked',
            'sort_by' => 'title',
        ]));

    $this->assertDatabaseMissing('category_post', [
        'post_id' => $post->id,
        'category_id' => $oldCategory->id,
    ]);
    $this->assertDatabaseHas('category_post', [
        'post_id' => $post->id,
        'category_id' => $newCategory->id,
    ]);
});

test('post category links reject categories from another type', function () {
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
        'type' => 'news',
        'title' => 'Typed News',
        'slug' => 'typed-news',
    ]);
    $pageCategory = Category::factory()->create([
        'org_id' => $org->id,
        'type' => 'page',
        'title' => 'Page Category',
        'slug' => 'page-category',
    ]);

    $this->actingAs($user)
        ->from(route('posts.edit', [
            'current_team' => $team->slug,
            'current_org' => $org->slug,
            'post' => $post,
        ]))
        ->patch(route('posts.categories.update', [
            'current_team' => $team->slug,
            'current_org' => $org->slug,
            'post' => $post,
        ]), [
            'category_ids' => [$pageCategory->id],
        ])
        ->assertRedirect(route('posts.edit', [
            'current_team' => $team->slug,
            'current_org' => $org->slug,
            'post' => $post,
        ]))
        ->assertSessionHasErrors('category_ids.0');

    $this->assertDatabaseMissing('category_post', [
        'post_id' => $post->id,
        'category_id' => $pageCategory->id,
    ]);
});

test('post edit page rejects post from another org', function () {
    /** @var TestCase $this */
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $org = $team->orgs()->create([
        'name' => 'Test Org',
        'slug' => 'test-org',
        'status' => 'enabled',
    ]);
    $otherOrg = $team->orgs()->create([
        'name' => 'Other Org',
        'slug' => 'other-org',
        'status' => 'enabled',
    ]);
    $user->update(['current_org_id' => $otherOrg->id]);
    $post = Post::factory()->create([
        'org_id' => $org->id,
        'author_id' => $user->id,
        'type' => 'news',
        'title' => 'Other Org News',
        'slug' => 'other-org-news',
    ]);

    $this->actingAs($user)
        ->get(route('posts.edit', [
            'current_team' => $team->slug,
            'current_org' => $otherOrg->slug,
            'post' => $post,
        ]))
        ->assertNotFound();
});

test('posts page filters records by type from url', function () {
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
    /** @var TestCase $this */
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
    /** @var TestCase $this */
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
            'page' => 2,
            'per_page' => 25,
            'search' => 'brand',
            'filter_status' => 'draft',
            'sort_by' => 'title',
            'sort_direction' => 'asc',
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
            'page' => 2,
            'per_page' => 25,
            'search' => 'brand',
            'filter_status' => 'draft',
            'sort_by' => 'title',
            'sort_direction' => 'asc',
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
            'page' => 4,
            'per_page' => 10,
            'filter_title' => 'Old',
            'filter_published_at' => '2026-04-29',
            'sort_by' => 'updated_at',
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
            'page' => 4,
            'per_page' => 10,
            'filter_title' => 'Old',
            'filter_published_at' => '2026-04-29',
            'sort_by' => 'updated_at',
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
