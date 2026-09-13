<?php

namespace Tests\Feature\Users;

use App\Enums\Role;
use App\Models\User;
use Tests\Facades\ProjectFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class ShowTest extends TestCase
{
    /** @var User $admin */
    private User $admin;
    /** @var User $manager */
    private User $manager;
    /** @var User $auditor */
    private User $auditor;
    /** @var User $user */
    private User $user;

    /** @var User $projectManager */
    private User $projectManager;
    /** @var User $projectAuditor */
    private User $projectAuditor;
    /** @var User $projectUser */
    private User $projectUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::refresh()->asAdmin()->withTokens()->create();
        $this->manager = UserFactory::refresh()->asManager()->withTokens()->create();
        $this->auditor = UserFactory::refresh()->asAuditor()->withTokens()->create();
        $this->user = UserFactory::refresh()->asUser()->withTokens()->create();

        $project = ProjectFactory::create();

        $this->projectManager = UserFactory::refresh()->asUser()->withTokens()->create();
        $this->projectManager->projects()->attach($project->id, ['role_id' => Role::MANAGER]);

        $this->projectAuditor = UserFactory::refresh()->asUser()->withTokens()->create();
        $this->projectAuditor->projects()->attach($project->id, ['role_id' => Role::AUDITOR]);

        $this->projectUser = UserFactory::refresh()->asUser()->withTokens()->create();
        $this->projectUser->projects()->attach($project->id, ['role_id' => Role::USER]);
    }

    public function test_show_as_admin(): void
    {
        $response = $this->actingAs($this->admin)->postJson(route('users.show'), $this->user->only('id'));

        $response->assertOk();
        $response->assertJson(['data' => $this->user->toArray()]);
    }

    public function test_show_as_manager(): void
    {
        $response = $this->actingAs($this->manager)->postJson(route('users.show'), $this->user->only('id'));

        $response->assertOk();
        $response->assertJson(['data' => $this->user->toArray()]);
    }

    public function test_show_as_auditor(): void
    {
        $response = $this->actingAs($this->auditor)->postJson(route('users.show'), $this->user->only('id'));

        $response->assertOk();
        $response->assertJson(['data' => $this->user->toArray()]);
    }

    public function test_show_as_user(): void
    {
        $response = $this->actingAs($this->user)->postJson(route('users.show'), $this->admin->only('id'));

        $response->assertForbidden();
    }

    public function test_show_as_your_own_user(): void
    {
        $response = $this->actingAs($this->user)->postJson(route('users.show'), $this->user->only('id'));

        $this->user->makeHidden('role');

        $response->assertOk();
        $response->assertJson(['data' => $this->user->toArray()]);
    }

    public function test_show_as_project_manager(): void
    {
        $response = $this->actingAs($this->projectManager)->postJson(route('users.show'), $this->projectUser->only('id'));

        $user = User::where('id', $this->projectUser->id)
            ->setEagerLoads([])
            ->first()
            ->toArray();
        $user['screenshots_state'] = $user['screenshots_state']->value;

        $response->assertOk();
        $response->assertJson(['data' => $user]);
    }

    public function test_show_as_project_auditor(): void
    {
        $response = $this
            ->actingAs($this->projectAuditor)
            ->postJson(route('users.show'), $this->projectUser->only('id'));

        $user = User::where('id', $this->projectUser->id)
            ->setEagerLoads([])
            ->first()
            ->toArray();
        $user['screenshots_state'] = $user['screenshots_state']->value;

        $response->assertOk();
        $response->assertJson(['data' => $user]);
    }

    public function test_show_as_project_user(): void
    {
        $response = $this->actingAs($this->projectUser)->postJson(route('users.show'), $this->projectAuditor->only('id'));

        $response->assertForbidden();
    }

    public function test_unauthorized(): void
    {
        $response = $this->postJson(route('users.show'));

        $response->assertUnauthorized();
    }

    public function test_without_params(): void
    {
        $response = $this->actingAs($this->admin)->postJson(route('users.show'));

        $response->assertValidationError();
    }
}
