<?php

use App\Models\Category;
use App\Models\Post;
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
    $rootCategory->posts()->attach(
        Post::factory()->count(2)->create([
            'org_id' => $org->id,
            'type' => 'page',
        ])->pluck('id')->all(),
    );
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
            ->where('categories.0.posts_count', 2)
            ->where('categories.0.children_count', 1)
            ->where('categories.1.title', 'Child Category')
            ->where('categories.1.depth', 1)
            ->where('categories.1.parent_id', $rootCategory->id)
            ->where('categories.1.type', 'page')
            ->where('categories.1.slug', 'child-category')
            ->where('categories.1.posts_count', 0)
            ->where('categories.1.children_count', 0)
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

test('authenticated users can create category with generated slug', function () {
    /** @var TestCase $this */
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $org = $team->orgs()->create([
        'name' => 'Test Org',
        'slug' => 'test-org',
        'status' => 'enabled',
    ]);
    $user->update(['current_org_id' => $org->id]);

    Category::factory()->create([
        'org_id' => $org->id,
        'type' => 'news',
        'title' => 'Brand News',
        'slug' => 'brand-news',
    ]);

    $this->actingAs($user)
        ->post(route('categories.store', [
            'current_team' => $team->slug,
            'current_org' => $org->slug,
        ]), [
            'type' => 'news',
            'title' => 'Brand News',
        ])
        ->assertRedirect(route('categories.byType', [
            'current_team' => $team->slug,
            'current_org' => $org->slug,
            'type' => 'news',
        ]));

    $this->assertDatabaseHas('categories', [
        'org_id' => $org->id,
        'parent_id' => null,
        'type' => 'news',
        'slug' => 'brand-news-2',
        'title' => 'Brand News',
    ]);
});

test('authenticated users can create category with parent', function () {
    /** @var TestCase $this */
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $org = $team->orgs()->create([
        'name' => 'Test Org',
        'slug' => 'test-org',
        'status' => 'enabled',
    ]);
    $user->update(['current_org_id' => $org->id]);

    $parentCategory = Category::factory()->create([
        'org_id' => $org->id,
        'type' => 'news',
    ]);

    $this->actingAs($user)
        ->post(route('categories.store', [
            'current_team' => $team->slug,
            'current_org' => $org->slug,
        ]), [
            'type' => 'news',
            'parent_id' => $parentCategory->id,
            'title' => 'Child News',
            'slug' => 'child-news',
        ])
        ->assertRedirect(route('categories.byType', [
            'current_team' => $team->slug,
            'current_org' => $org->slug,
            'type' => 'news',
        ]));

    $this->assertDatabaseHas('categories', [
        'org_id' => $org->id,
        'parent_id' => $parentCategory->id,
        'type' => 'news',
        'slug' => 'child-news',
        'title' => 'Child News',
    ]);
});

test('category creation rejects duplicate slug in the same org and type', function () {
    /** @var TestCase $this */
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $org = $team->orgs()->create([
        'name' => 'Test Org',
        'slug' => 'test-org',
        'status' => 'enabled',
    ]);
    $user->update(['current_org_id' => $org->id]);

    Category::factory()->create([
        'org_id' => $org->id,
        'type' => 'page',
        'title' => 'Existing Category',
        'slug' => 'existing-category',
    ]);

    $this->actingAs($user)
        ->from(route('categories.index', [
            'current_team' => $team->slug,
            'current_org' => $org->slug,
        ]))
        ->post(route('categories.store', [
            'current_team' => $team->slug,
            'current_org' => $org->slug,
        ]), [
            'type' => 'page',
            'title' => 'Another Category',
            'slug' => 'existing-category',
        ])
        ->assertRedirect(route('categories.index', [
            'current_team' => $team->slug,
            'current_org' => $org->slug,
        ]))
        ->assertSessionHasErrors('slug');
});

test('category creation rejects parent from another org or type', function () {
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
    $user->update(['current_org_id' => $org->id]);

    $otherTypeParent = Category::factory()->create([
        'org_id' => $org->id,
        'type' => 'news',
    ]);
    $otherOrgParent = Category::factory()->create([
        'org_id' => $otherOrg->id,
        'type' => 'page',
    ]);

    foreach ([$otherTypeParent, $otherOrgParent] as $parentCategory) {
        $this->actingAs($user)
            ->from(route('categories.index', [
                'current_team' => $team->slug,
                'current_org' => $org->slug,
            ]))
            ->post(route('categories.store', [
                'current_team' => $team->slug,
                'current_org' => $org->slug,
            ]), [
                'type' => 'page',
                'parent_id' => $parentCategory->id,
                'title' => 'Invalid Parent',
                'slug' => 'invalid-parent-'.$parentCategory->id,
            ])
            ->assertRedirect(route('categories.index', [
                'current_team' => $team->slug,
                'current_org' => $org->slug,
            ]))
            ->assertSessionHasErrors('parent_id');
    }
});

test('authenticated users can update category without changing parent', function () {
    /** @var TestCase $this */
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $org = $team->orgs()->create([
        'name' => 'Test Org',
        'slug' => 'test-org',
        'status' => 'enabled',
    ]);
    $user->update(['current_org_id' => $org->id]);

    $parentCategory = Category::factory()->create([
        'org_id' => $org->id,
        'type' => 'page',
    ]);
    $category = Category::factory()->create([
        'org_id' => $org->id,
        'parent_id' => $parentCategory->id,
        'type' => 'page',
        'title' => 'Old Category',
        'slug' => 'old-category',
    ]);

    $this->actingAs($user)
        ->patch(route('categories.update', [
            'current_team' => $team->slug,
            'current_org' => $org->slug,
            'category' => $category,
        ]), [
            'type' => 'article',
            'title' => 'Updated Category',
            'slug' => 'updated-category',
        ])
        ->assertRedirect(route('categories.byType', [
            'current_team' => $team->slug,
            'current_org' => $org->slug,
            'type' => 'article',
        ]));

    $this->assertDatabaseHas('categories', [
        'id' => $category->id,
        'org_id' => $org->id,
        'parent_id' => $parentCategory->id,
        'type' => 'article',
        'slug' => 'updated-category',
        'title' => 'Updated Category',
    ]);
});

test('authenticated users can update category parent', function () {
    /** @var TestCase $this */
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $org = $team->orgs()->create([
        'name' => 'Test Org',
        'slug' => 'test-org',
        'status' => 'enabled',
    ]);
    $user->update(['current_org_id' => $org->id]);

    $parentCategory = Category::factory()->create([
        'org_id' => $org->id,
        'type' => 'page',
    ]);
    $category = Category::factory()->create([
        'org_id' => $org->id,
        'type' => 'page',
        'title' => 'Child Category',
        'slug' => 'child-category',
    ]);

    $this->actingAs($user)
        ->patch(route('categories.update', [
            'current_team' => $team->slug,
            'current_org' => $org->slug,
            'category' => $category,
        ]), [
            'type' => 'page',
            'parent_id' => $parentCategory->id,
            'title' => 'Child Category',
            'slug' => 'child-category',
        ])
        ->assertRedirect(route('categories.index', [
            'current_team' => $team->slug,
            'current_org' => $org->slug,
        ]));

    $this->assertDatabaseHas('categories', [
        'id' => $category->id,
        'parent_id' => $parentCategory->id,
    ]);
});

test('category update rejects descendant parent', function () {
    /** @var TestCase $this */
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $org = $team->orgs()->create([
        'name' => 'Test Org',
        'slug' => 'test-org',
        'status' => 'enabled',
    ]);
    $user->update(['current_org_id' => $org->id]);

    $category = Category::factory()->create([
        'org_id' => $org->id,
        'type' => 'page',
        'title' => 'Root Category',
        'slug' => 'root-category',
    ]);
    $childCategory = Category::factory()->create([
        'org_id' => $org->id,
        'parent_id' => $category->id,
        'type' => 'page',
    ]);
    $grandchildCategory = Category::factory()->create([
        'org_id' => $org->id,
        'parent_id' => $childCategory->id,
        'type' => 'page',
    ]);

    $this->actingAs($user)
        ->from(route('categories.index', [
            'current_team' => $team->slug,
            'current_org' => $org->slug,
        ]))
        ->patch(route('categories.update', [
            'current_team' => $team->slug,
            'current_org' => $org->slug,
            'category' => $category,
        ]), [
            'type' => 'page',
            'parent_id' => $grandchildCategory->id,
            'title' => 'Root Category',
            'slug' => 'root-category',
        ])
        ->assertRedirect(route('categories.index', [
            'current_team' => $team->slug,
            'current_org' => $org->slug,
        ]))
        ->assertSessionHasErrors('parent_id');
});

test('authenticated users can delete category', function () {
    /** @var TestCase $this */
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $org = $team->orgs()->create([
        'name' => 'Test Org',
        'slug' => 'test-org',
        'status' => 'enabled',
    ]);
    $user->update(['current_org_id' => $org->id]);

    $category = Category::factory()->create([
        'org_id' => $org->id,
        'type' => 'product',
        'title' => 'Product Category',
        'slug' => 'product-category',
    ]);

    $this->actingAs($user)
        ->delete(route('categories.destroy', [
            'current_team' => $team->slug,
            'current_org' => $org->slug,
            'category' => $category,
        ]))
        ->assertRedirect(route('categories.byType', [
            'current_team' => $team->slug,
            'current_org' => $org->slug,
            'type' => 'product',
        ]));

    $this->assertDatabaseMissing('categories', [
        'id' => $category->id,
    ]);
});
