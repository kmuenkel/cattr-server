<?php

namespace Tests\Feature\Projects;

use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Tests\Facades\ProjectFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class RemoveTest extends TestCase
{
    private const URI = 'projects/remove';

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

        Event::fake();

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

    public function test_remove_as_admin(): void
    {
        $this->assertDatabaseHas('projects', $this->project->toArray());

        $response = $this->actingAs($this->admin)->postJson(route('projects.destroy'), $this->project->only('id'));

        $response->assertNoContent();
        $this->assertSoftDeleted('projects', $this->project->only('id'));
    }
    public function test_remove_as_manager(): void
    {
        $this->assertDatabaseHas('projects', $this->project->toArray());

        $response = $this->actingAs($this->manager)->postJson(route('projects.destroy'), $this->project->only('id'));

        $response->assertNoContent();
        $this->assertSoftDeleted('projects', $this->project->only('id'));
    }

    public function test_remove_as_auditor(): void
    {
        $this->assertDatabaseHas('projects', $this->project->toArray());

        $response = $this->actingAs($this->auditor)->postJson(route('projects.destroy'), $this->project->only('id'));

        $response->assertForbidden();
    }

    public function test_remove_as_user(): void
    {
        $this->assertDatabaseHas('projects', $this->project->toArray());

        $response = $this->actingAs($this->user)->postJson(route('projects.destroy'), $this->project->only('id'));

        $response->assertForbidden();
    }

    public function test_remove_as_project_manager(): void
    {
        $this->assertDatabaseHas('projects', $this->project->toArray());

        $response = $this->actingAs($this->projectManager)->postJson(route('projects.destroy'), $this->project->only('id'));

        $response->assertNoContent();
    }

    public function test_remove_as_project_auditor(): void
    {
        $this->assertDatabaseHas('projects', $this->project->toArray());

        $response = $this->actingAs($this->projectAuditor)->postJson(route('projects.destroy'), $this->project->only('id'));

        $response->assertForbidden();
    }

    public function test_remove_as_project_user(): void
    {
        $this->assertDatabaseHas('projects', $this->project->toArray());

        $response = $this->actingAs($this->projectUser)->postJson(route('projects.destroy'), $this->project->only('id'));

        $response->assertForbidden();
    }

    public function test_unauthorized(): void
    {
        $response = $this->postJson(route('projects.destroy'));

        $response->assertUnauthorized();
    }

    public function test_not_existing_project(): void
    {
        $response = $this->actingAs($this->admin)->postJson(route('projects.destroy'));

        $response->assertValidationError();
    }

    public function test_without_params(): void
    {
        $response = $this->actingAs($this->admin)->postJson(route('projects.destroy'));

        $response->assertValidationError();
    }
}
