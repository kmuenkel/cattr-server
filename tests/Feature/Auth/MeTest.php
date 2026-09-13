<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class MeTest extends TestCase
{

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = UserFactory::withTokens()->create();
    }

    public function test_me(): void
    {
        $response = $this->actingAs($this->user)->getJson(route('auth.me'));

        $response->assertOk();
        $response->assertJson(['data' => ['id' => $this->user->getKey()]]);
    }

    public function test_without_auth(): void
    {
        $response = $this->getJson(route('auth.me'));

        $response->assertUnauthorized();
    }
}
