<?php

namespace Tests\Feature\Users;

use App\Models\User;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class CountTest extends TestCase
{
    private const USERS_AMOUNT = 10;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::asAdmin()->withTokens()->create();

        UserFactory::createMany(self::USERS_AMOUNT);
    }

    public function test_count(): void
    {
        $response = $this->actingAs($this->admin)->getJson(route('users.count'));

        $response->assertOk();
        $response->assertJson(['data' => ['total' => User::count()]]);
    }

    public function test_unauthorized(): void
    {
        $response = $this->getJson(route('users.count'));

        $response->assertUnauthorized();
    }
}
