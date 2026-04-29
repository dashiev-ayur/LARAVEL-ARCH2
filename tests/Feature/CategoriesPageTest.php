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
        'type' => 'news',
        'title' => 'Root Category',
        'slug' => 'root-category',
    ]);
    Category::factory()->create([
        'org_id' => $org->id,
        'type' => 'news',
        'title' => 'Second Root',
        'slug' => 'second-root',
    ]);
    Category::factory()->create([
        'org_id' => $org->id,
        'parent_id' => $rootCategory->id,
        'type' => 'news',
        'title' => 'Child Category',
        'slug' => 'child-category',
    ]);
    $rootCategory->posts()->attach(
        Post::factory()->count(2)->create([
            'org_id' => $org->id,
            'type' => 'news',
        ])->pluck('id')->all(),
    );
    Category::factory()->create([
        'org_id' => $org->id,
        'type' => 'article',
        'title' => 'Article Category',
        'slug' => 'article-category',
    ]);

    $this->actingAs($user)
        ->get(route('categories.index', [
            'current_team' => $team->slug,
            'current_org' => $org->slug,
        ]))
        ->assertInertia(fn (Assert $page) => $page
            ->component('categories/index')
            ->where('activeType', 'news')
            ->has('postTypes', 3)
            ->has('postTypeUi', 3)
            ->where('postTypeUi.news.filterButtonTitle', 'Новости')
            ->has('categories', 3)
            ->where('categories.0.title', 'Root Category')
            ->where('categories.0.depth', 0)
            ->where('categories.0.parent_id', null)
            ->where('categories.0.posts_count', 2)
            ->where('categories.0.children_count', 1)
            ->where('categories.1.title', 'Child Category')
            ->where('categories.1.depth', 1)
            ->where('categories.1.parent_id', $rootCategory->id)
            ->where('categories.1.type', 'news')
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

    $articleRootCategory = Category::factory()->create([
        'org_id' => $org->id,
        'type' => 'article',
        'title' => 'Article Root',
        'slug' => 'article-root',
    ]);
    Category::factory()->create([
        'org_id' => $org->id,
        'parent_id' => $articleRootCategory->id,
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
            ->has('postTypes', 3)
            ->has('categories', 2)
            ->where('categories.0.title', 'News Child')
            ->where('categories.0.depth', 0)
            ->where('categories.0.type', 'news')
            ->where('categories.1.title', 'News Root')
            ->where('categories.1.depth', 0)
            ->where('categories.1.type', 'news'),
        );
});

test('categories page no longer renders page type route', function () {
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
        ->get("/{$team->slug}/{$org->slug}/categories/page")
        ->assertMethodNotAllowed();
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
        ->assertRedirect(route('categories.index', [
            'current_team' => $team->slug,
            'current_org' => $org->slug,
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
        ->assertRedirect(route('categories.index', [
            'current_team' => $team->slug,
            'current_org' => $org->slug,
        ]));

    $this->assertDatabaseHas('categories', [
        'org_id' => $org->id,
        'parent_id' => $parentCategory->id,
        'type' => 'news',
        'slug' => 'child-news',
        'title' => 'Child News',
    ]);
});

test('created category receives the last sibling sort order', function () {
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
    Category::factory()->create([
        'org_id' => $org->id,
        'parent_id' => $parentCategory->id,
        'type' => 'news',
        'sort_order' => 4,
    ]);

    $this->actingAs($user)
        ->post(route('categories.store', [
            'current_team' => $team->slug,
            'current_org' => $org->slug,
        ]), [
            'type' => 'news',
            'parent_id' => $parentCategory->id,
            'title' => 'Last Child',
            'slug' => 'last-child',
        ])
        ->assertRedirect(route('categories.index', [
            'current_team' => $team->slug,
            'current_org' => $org->slug,
        ]));

    $this->assertDatabaseHas('categories', [
        'org_id' => $org->id,
        'parent_id' => $parentCategory->id,
        'type' => 'news',
        'slug' => 'last-child',
        'sort_order' => 5,
    ]);
});

test('categories page orders sibling categories by sort order', function () {
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
        'title' => 'Alphabetically First',
        'slug' => 'alphabetically-first',
        'sort_order' => 10,
    ]);
    Category::factory()->create([
        'org_id' => $org->id,
        'type' => 'news',
        'title' => 'Alphabetically Last',
        'slug' => 'alphabetically-last',
        'sort_order' => 0,
    ]);

    $this->actingAs($user)
        ->get(route('categories.index', [
            'current_team' => $team->slug,
            'current_org' => $org->slug,
        ]))
        ->assertInertia(fn (Assert $page) => $page
            ->component('categories/index')
            ->where('categories.0.title', 'Alphabetically Last')
            ->where('categories.0.sort_order', 0)
            ->where('categories.1.title', 'Alphabetically First')
            ->where('categories.1.sort_order', 10),
        );
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
        'type' => 'news',
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
            'type' => 'news',
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
        'type' => 'article',
    ]);
    $otherOrgParent = Category::factory()->create([
        'org_id' => $otherOrg->id,
        'type' => 'news',
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
                'type' => 'news',
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
        'type' => 'news',
    ]);
    $category = Category::factory()->create([
        'org_id' => $org->id,
        'parent_id' => $parentCategory->id,
        'type' => 'news',
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
        'type' => 'news',
    ]);
    $category = Category::factory()->create([
        'org_id' => $org->id,
        'type' => 'news',
        'title' => 'Child Category',
        'slug' => 'child-category',
    ]);

    $this->actingAs($user)
        ->patch(route('categories.update', [
            'current_team' => $team->slug,
            'current_org' => $org->slug,
            'category' => $category,
        ]), [
            'type' => 'news',
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
        'type' => 'news',
        'title' => 'Root Category',
        'slug' => 'root-category',
    ]);
    $childCategory = Category::factory()->create([
        'org_id' => $org->id,
        'parent_id' => $category->id,
        'type' => 'news',
    ]);
    $grandchildCategory = Category::factory()->create([
        'org_id' => $org->id,
        'parent_id' => $childCategory->id,
        'type' => 'news',
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
            'type' => 'news',
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

test('authenticated users can reorder root categories', function () {
    /** @var TestCase $this */
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $org = $team->orgs()->create([
        'name' => 'Test Org',
        'slug' => 'test-org',
        'status' => 'enabled',
    ]);
    $user->update(['current_org_id' => $org->id]);

    $firstCategory = Category::factory()->create([
        'org_id' => $org->id,
        'type' => 'news',
        'sort_order' => 0,
    ]);
    $secondCategory = Category::factory()->create([
        'org_id' => $org->id,
        'type' => 'news',
        'sort_order' => 1,
    ]);

    $this->actingAs($user)
        ->patch(route('categories.reorder', [
            'current_team' => $team->slug,
            'current_org' => $org->slug,
        ]), [
            'type' => 'news',
            'items' => [
                ['id' => $secondCategory->id, 'parent_id' => null, 'sort_order' => 0],
                ['id' => $firstCategory->id, 'parent_id' => null, 'sort_order' => 1],
            ],
        ])
        ->assertRedirect(route('categories.index', [
            'current_team' => $team->slug,
            'current_org' => $org->slug,
        ]));

    $this->assertDatabaseHas('categories', [
        'id' => $secondCategory->id,
        'parent_id' => null,
        'sort_order' => 0,
    ]);
    $this->assertDatabaseHas('categories', [
        'id' => $firstCategory->id,
        'parent_id' => null,
        'sort_order' => 1,
    ]);
});

test('authenticated users can reorder categories and change parent', function () {
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
        'sort_order' => 0,
    ]);
    $firstChild = Category::factory()->create([
        'org_id' => $org->id,
        'parent_id' => $parentCategory->id,
        'type' => 'news',
        'sort_order' => 0,
    ]);
    $movedCategory = Category::factory()->create([
        'org_id' => $org->id,
        'type' => 'news',
        'sort_order' => 1,
    ]);

    $this->actingAs($user)
        ->patch(route('categories.reorder', [
            'current_team' => $team->slug,
            'current_org' => $org->slug,
        ]), [
            'type' => 'news',
            'items' => [
                ['id' => $parentCategory->id, 'parent_id' => null, 'sort_order' => 0],
                ['id' => $movedCategory->id, 'parent_id' => $parentCategory->id, 'sort_order' => 0],
                ['id' => $firstChild->id, 'parent_id' => $parentCategory->id, 'sort_order' => 1],
            ],
        ])
        ->assertRedirect(route('categories.index', [
            'current_team' => $team->slug,
            'current_org' => $org->slug,
        ]));

    $this->assertDatabaseHas('categories', [
        'id' => $movedCategory->id,
        'parent_id' => $parentCategory->id,
        'sort_order' => 0,
    ]);
    $this->assertDatabaseHas('categories', [
        'id' => $firstChild->id,
        'parent_id' => $parentCategory->id,
        'sort_order' => 1,
    ]);
});

test('category reorder rejects descendant parent', function () {
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
        'type' => 'news',
    ]);
    $childCategory = Category::factory()->create([
        'org_id' => $org->id,
        'parent_id' => $category->id,
        'type' => 'news',
    ]);

    $this->actingAs($user)
        ->from(route('categories.index', [
            'current_team' => $team->slug,
            'current_org' => $org->slug,
        ]))
        ->patch(route('categories.reorder', [
            'current_team' => $team->slug,
            'current_org' => $org->slug,
        ]), [
            'type' => 'news',
            'items' => [
                ['id' => $category->id, 'parent_id' => $childCategory->id, 'sort_order' => 0],
                ['id' => $childCategory->id, 'parent_id' => $category->id, 'sort_order' => 0],
            ],
        ])
        ->assertRedirect(route('categories.index', [
            'current_team' => $team->slug,
            'current_org' => $org->slug,
        ]))
        ->assertSessionHasErrors('items.0.parent_id');
});

test('category reorder rejects categories from another org', function () {
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

    $category = Category::factory()->create([
        'org_id' => $org->id,
        'type' => 'news',
    ]);
    $otherOrgCategory = Category::factory()->create([
        'org_id' => $otherOrg->id,
        'type' => 'news',
    ]);

    $this->actingAs($user)
        ->from(route('categories.index', [
            'current_team' => $team->slug,
            'current_org' => $org->slug,
        ]))
        ->patch(route('categories.reorder', [
            'current_team' => $team->slug,
            'current_org' => $org->slug,
        ]), [
            'type' => 'news',
            'items' => [
                ['id' => $category->id, 'parent_id' => null, 'sort_order' => 0],
                ['id' => $otherOrgCategory->id, 'parent_id' => null, 'sort_order' => 1],
            ],
        ])
        ->assertRedirect(route('categories.index', [
            'current_team' => $team->slug,
            'current_org' => $org->slug,
        ]))
        ->assertSessionHasErrors('items.1.id');
});

test('category reorder rejects parent from another type', function () {
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
        'type' => 'article',
    ]);
    $newsCategory = Category::factory()->create([
        'org_id' => $org->id,
        'type' => 'news',
    ]);

    $this->actingAs($user)
        ->from(route('categories.index', [
            'current_team' => $team->slug,
            'current_org' => $org->slug,
        ]))
        ->patch(route('categories.reorder', [
            'current_team' => $team->slug,
            'current_org' => $org->slug,
        ]), [
            'type' => 'article',
            'items' => [
                ['id' => $category->id, 'parent_id' => $newsCategory->id, 'sort_order' => 0],
            ],
        ])
        ->assertRedirect(route('categories.index', [
            'current_team' => $team->slug,
            'current_org' => $org->slug,
        ]))
        ->assertSessionHasErrors('items.0.parent_id');
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
