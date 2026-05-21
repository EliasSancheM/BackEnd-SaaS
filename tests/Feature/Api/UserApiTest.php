<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserApiTest extends TestCase
{
    public function test_index_returns_users(): void
    {
        $this->seedDemoData();
        $user = User::where('email', 'test@example.com')->firstOrFail();

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/users');

        $response->assertOk()->assertJsonStructure(['data']);
    }
}
