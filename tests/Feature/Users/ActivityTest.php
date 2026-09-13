<?php

namespace Tests\Feature\Users;

use App\Models\User;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class ActivityTest extends TestCase
{
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::refresh()->asAdmin()->withTokens()->create();
    }

    public function test_update(): void
    {
        $lastActivity = $this->admin->last_activity;

        $response = $this->actingAs($this->admin)->patchJson(route('users.ping'));

        $user = User::find($this->admin->id);

        $response->assertNoContent();
        $this->assertNotEquals($lastActivity->toString(), $user->last_activity->toString());
        $this->assertTrue($user->online);
    }

    public function test_unauthorized(): void
    {
        $response = $this->patchJson(route('users.ping'));

        $response->assertUnauthorized();
    }
}
