<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Auth;

class PostFolloweeControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_get_followee_posts_ordered_by_date()
    {
        $user = User::factory()->create();
        $following1 = User::factory()->create();
        $following2 = User::factory()->create();

        $user->following()->attach([$following1->id, $following2->id]);

        $post1 = Post::factory()->create(['users_id' => $following1->id, 'created_at' => now()->subDays(2)]);
        $post2 = Post::factory()->create(['users_id' => $following2->id, 'created_at' => now()->subDays(1)]);
        $ownPost = Post::factory()->create(['users_id' => $user->id, 'created_at' => now()]);
        $otherPost = Post::factory()->create(['users_id' => User::factory()->create()->id, 'created_at' => now()->subDays(3)]);

        $this->actingAs($user);

        $response = $this->getJson('/api/v1/feed');

        $response->assertOk();
        $response->assertJsonCount(3, 'data');
        $response->assertJsonPath('data.0.id', $ownPost->id);
        $response->assertJsonPath('data.1.id', $post2->id);
        $response->assertJsonPath('data.2.id', $post1->id);
        $response->assertJsonMissing(['id' => $otherPost->id]);
    }
}