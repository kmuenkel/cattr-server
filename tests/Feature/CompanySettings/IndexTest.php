<?php

namespace Tests\Feature\CompanySettings;

use App\Models\User;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class IndexTest extends TestCase
{
    private User $admin;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::refresh()->withTokens()->asAdmin()->create();
        $this->user = UserFactory::refresh()->withTokens()->asUser()->create();
    }

    public function test_index_as_admin(): void
    {
        $response = $this->actingAs($this->admin)->getJson(route('settings.list'));

        $response->assertOk();
    }

    public function test_index_as_user(): void
    {
        $response = $this->actingAs($this->user)->getJson(route('settings.list'));

        $response->assertOk();
    }
}
