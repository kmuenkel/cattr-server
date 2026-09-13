<?php

namespace Tests\Feature\Users;

use App\Models\User;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class RemoveTest extends TestCase
{
    /** @var User $admin */
    private User $admin;
    /** @var User $manager */
    private User $manager;
    /** @var User $auditor */
    private User $auditor;
    /** @var User $user */
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::refresh()->asAdmin()->withTokens()->create();
        $this->manager = UserFactory::refresh()->asManager()->withTokens()->create();
        $this->auditor = UserFactory::refresh()->asAuditor()->withTokens()->create();
        $this->user = UserFactory::refresh()->asUser()->withTokens()->create();
    }

    public function test_remove_as_admin(): void
    {
        $user = $this->user->makeHidden('online')->toArray();
        unset($user['online']);
        unset($user['can_view_team_tab']);
        unset($user['can_create_task']);

        $this->assertDatabaseHas('users', $user);

        $response = $this->actingAs($this->admin)->postJson(route('users.destroy'), $this->user->only('id'));

        $response->assertNoContent();
        $this->assertSoftDeleted('users', $this->user->only('id'));
    }

    public function test_remove_as_manager(): void
    {
        $user = $this->user->toArray();
        unset($user['can_view_team_tab'], $user['can_create_task'], $user['online']);
        $this->assertDatabaseHas('users', $user);

        $response = $this->actingAs($this->manager)->postJson(route('users.destroy'), $this->user->only('id'));

        $response->assertForbidden();
    }

    public function test_remove_as_auditor(): void
    {
        $user = $this->user->toArray();
        unset($user['can_view_team_tab'], $user['can_create_task'], $user['online']);
        $this->assertDatabaseHas('users', $user);

        $response = $this->actingAs($this->auditor)->postJson(route('users.destroy'), $this->user->only('id'));

        $response->assertForbidden();
    }

    public function test_remove_as_user(): void
    {
        $user = $this->user->toArray();
        unset($user['can_view_team_tab'], $user['can_create_task'], $user['online']);
        $this->assertDatabaseHas('users', $user);

        $response = $this->actingAs($this->user)->postJson(route('users.destroy'), $this->user->only('id'));

        $response->assertForbidden();
    }

    public function test_unauthorized(): void
    {
        $response = $this->postJson(route('users.destroy'));

        $response->assertUnauthorized();
    }

    public function test_without_params(): void
    {
        $response = $this->actingAs($this->admin)->postJson(route('users.destroy'));

        $response->assertValidationError();
    }
}
