<?php
namespace Tests\Feature\Roles;

use App\Models\Role;
use App\Models\User;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class ListTest extends TestCase
{
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::withTokens()->asAdmin()->create();
    }

    public function test_list(): void
    {
        $this->markTestSkipped('Deprecated Model');
        $response = $this->actingAs($this->admin)->getJson(route('roles.list'));

        $response->assertOk();
        $response->assertJson(Role::all()->toArray());
    }

    public function test_unauthorized(): void
    {
        $this->markTestSkipped('Deprecated Authorization Expectations');
        $response = $this->getJson(route('roles.list'));

        $response->assertUnauthorized();
    }
}
