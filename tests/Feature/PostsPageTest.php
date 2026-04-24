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
            ->where('posts.0.type', 'page'),
        );
});
