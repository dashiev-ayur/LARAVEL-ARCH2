<?php

use App\Models\Category;
use App\Models\Org;
use App\Models\Post;
use App\Models\User;

test('post has expected belongs-to and tree relations', function () {
    $org = Org::factory()->create();
    $author = User::factory()->create();
    $parentPost = Post::factory()->create([
        'org_id' => $org->id,
        'author_id' => $author->id,
    ]);
    $childPost = Post::factory()->create([
        'org_id' => $org->id,
        'author_id' => $author->id,
        'parent_id' => $parentPost->id,
    ]);

    expect($childPost->org)->id->toBe($org->id)
        ->and($childPost->author)->id->toBe($author->id)
        ->and($childPost->parent)->id->toBe($parentPost->id)
        ->and($parentPost->children->pluck('id')->all())->toContain($childPost->id);
});

test('category has expected tree relations', function () {
    $org = Org::factory()->create();
    $parentCategory = Category::factory()->create([
        'org_id' => $org->id,
    ]);
    $childCategory = Category::factory()->create([
        'org_id' => $org->id,
        'parent_id' => $parentCategory->id,
    ]);

    expect($childCategory->org)->id->toBe($org->id)
        ->and($childCategory->parent)->id->toBe($parentCategory->id)
        ->and($parentCategory->children->pluck('id')->all())->toContain($childCategory->id);
});

test('posts and categories are linked via pivot with position', function () {
    $org = Org::factory()->create();
    $post = Post::factory()->create([
        'org_id' => $org->id,
    ]);
    $category = Category::factory()->create([
        'org_id' => $org->id,
    ]);

    $post->categories()->attach($category->id, [
        'position' => 7,
    ]);

    $post->load('categories');
    $category->load('posts');

    expect($post->categories->first()?->id)->toBe($category->id)
        ->and($post->categories->first()?->pivot->position)->toBe(7)
        ->and($category->posts->first()?->id)->toBe($post->id)
        ->and($category->posts->first()?->pivot->position)->toBe(7);
});

test('org and author expose reverse post and category relations', function () {
    $org = Org::factory()->create();
    $author = User::factory()->create();
    $post = Post::factory()->create([
        'org_id' => $org->id,
        'author_id' => $author->id,
    ]);
    $category = Category::factory()->create([
        'org_id' => $org->id,
    ]);

    expect($org->posts->pluck('id')->all())->toContain($post->id)
        ->and($org->categories->pluck('id')->all())->toContain($category->id)
        ->and($author->posts->pluck('id')->all())->toContain($post->id);
});
