<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class RefreshTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = UserFactory::withTokens()->create();
    }

    public function test_refresh(): void
    {
        $this->markTestSkipped('Deprecated route');

        $token = cache("testing:{$this->user->id}:tokens");

        $this->assertNotEmpty($token);
        $this->assertNotEmpty($token[0]);
        $this->assertNotEmpty($token[0]['token']);

        $this->actingAs($token[0]['token'])->get(route('auth.me'))->assertOk();

        $response = $this->actingAs($token[0]['token'])->postJson(route('auth.refresh'));

        $response->assertOk();

        $this->actingAs($token[0]['token'])->get(route('auth.me'))->assertUnauthorized();
    }
}
