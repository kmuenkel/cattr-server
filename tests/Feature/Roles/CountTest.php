<?php
namespace Tests\Feature\Roles;

use App\Models\Role;
use App\Models\User;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class CountTest extends TestCase
{
    private const URI = 'api/roles/count';

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->markTestSkipped('"api/roles/count" route has been removed');

        $this->admin = UserFactory::withTokens()->asAdmin()->create();
    }

    public function test_count(): void
    {
        $response = $this->actingAs($this->admin)->getJson(self::URI);

        $response->assertOk();
        $response->assertJson(['total' => Role::count()]);
    }

    public function test_unauthorized(): void
    {
        $response = $this->getJson(self::URI);

        $response->assertUnauthorized();
    }
}
