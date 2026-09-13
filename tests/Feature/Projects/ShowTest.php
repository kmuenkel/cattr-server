<?php

namespace Tests\Feature\Projects;

use App\Models\Project;
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

    /** @var Project $project */
    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::refresh()->asAdmin()->withTokens()->create();
        $this->manager = UserFactory::refresh()->asManager()->withTokens()->create();
        $this->auditor = UserFactory::refresh()->asAuditor()->withTokens()->create();
        $this->user = UserFactory::refresh()->asUser()->withTokens()->create();

        $this->project = ProjectFactory::create();
        $this->project->update(['created_by' => $this->manager->getKey()]);

        $this->projectManager = UserFactory::refresh()->asUser()->withTokens()->create();
        $this->projectManager->projects()->attach($this->project->id, ['role_id' => 1]);

        $this->projectAuditor = UserFactory::refresh()->asUser()->withTokens()->create();
        $this->projectAuditor->projects()->attach($this->project->id, ['role_id' => 3]);

        $this->projectUser = UserFactory::refresh()->asUser()->withTokens()->create();
        $this->projectUser->projects()->attach($this->project->id, ['role_id' => 2]);
    }

    public function test_show_as_admin(): void
    {
        $response = $this->actingAs($this->admin)->postJson(route('projects.show'), $this->project->only('id'));

        $response->assertOk();
        $response->assertJson(['data' => $this->project->toArray()]);
    }

    public function test_show_as_manager(): void
    {
        $response = $this->actingAs($this->manager)->postJson(route('projects.show'), $this->project->only('id'));

        $response->assertOk();
        $response->assertJson(['data' => $this->project->toArray()]);
    }

    public function test_show_as_auditor(): void
    {
        $this->project->update(['created_by' => $this->auditor->getKey()]);
        $response = $this->actingAs($this->auditor)->postJson(route('projects.show'), $this->project->only('id'));

        $response->assertOk();
        $response->assertJson(['data' => $this->project->toArray()]);
    }

    public function test_show_as_user(): void
    {
        $response = $this->actingAs($this->user)->postJson(route('projects.show'), $this->project->only('id'));

        $response->assertForbidden();
    }

    public function test_show_as_project_manager(): void
    {
        $response = $this->actingAs($this->projectManager)->postJson(route('projects.show'), $this->project->only('id'));

        $response->assertOk();
        $response->assertJson(['data' => $this->project->toArray()]);
    }

    public function test_show_as_project_auditor(): void
    {
        $this->project->update(['created_by' => $this->projectAuditor->getKey()]);
        $response = $this->actingAs($this->projectAuditor)->postJson(route('projects.show'), $this->project->only('id'));

        $response->assertOk();
        $response->assertJson(['data' => $this->project->toArray()]);
    }

    public function test_show_as_project_user(): void
    {
        $this->project->update(['created_by' => $this->projectUser->getKey()]);
        $response = $this->actingAs($this->projectUser)->postJson(route('projects.show'), $this->project->only('id'));

        $response->assertOk();
        $response->assertJson(['data' => $this->project->toArray()]);
    }

    public function test_unauthorized(): void
    {
        $response = $this->postJson(route('projects.show'));

        $response->assertUnauthorized();
    }

    public function test_without_params(): void
    {
        $response = $this->actingAs($this->admin)->postJson(route('projects.show'));

        $response->assertValidationError();
    }
}
