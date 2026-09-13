<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = UserFactory::withTokens()->create();
    }

    public function test_logout(): void
    {
        $token = cache("testing:{$this->user->id}:tokens");

        $this->assertNotEmpty($token);
        $this->assertNotEmpty($token[0]);
        $this->assertNotEmpty($token[0]['token']);

        $response = $this->actingAs($token[0]['token'])->postJson(route('auth.logout'));

        $response->assertNoContent();

        app('auth')->forgetGuards();

        $response = $this->actingAs($token[0]['token'])->get(route('auth.me'));

        $response->assertUnauthorized();
    }

    public function test_unauthorized(): void
    {
        $response = $this->postJson(route('auth.logout'));

        $response->assertUnauthorized();
    }
}
