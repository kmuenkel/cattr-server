<?php

namespace Tests\Feature\Projects;

use App\Enums\Role;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Event;
use Tests\Facades\ProjectFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class EditTest extends TestCase
{
    use WithFaker;

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
        $this->projectManager->projects()->attach($this->project->id, ['role_id' => Role::MANAGER]);

        $this->projectAuditor = UserFactory::refresh()->asUser()->withTokens()->create();
        $this->projectAuditor->projects()->attach($this->project->id, ['role_id' => Role::AUDITOR]);

        $this->projectUser = UserFactory::refresh()->asUser()->withTokens()->create();
        $this->projectUser->projects()->attach($this->project->id, ['role_id' => Role::USER]);
    }

    public function test_edit_as_admin(): void
    {
        $this->project->name = $this->faker->text;
        $this->project->description = $this->faker->text;

        $response = $this->actingAs($this->admin)->postJson(route('projects.edit'), $this->project->toArray());

        $response->assertOk();
        $response->assertJson(['data' => $this->project->toArray()]);
        $this->assertDatabaseHas('projects', $this->project->toArray());
    }

    public function test_edit_as_manager(): void
    {
        $this->project->name = $this->faker->text;
        $this->project->description = $this->faker->text;

        $response = $this->actingAs($this->manager)->postJson(route('projects.edit'), $this->project->toArray());

        $response->assertOk();
        $response->assertJson(['data' => $this->project->toArray()]);
        $this->assertDatabaseHas('projects', $this->project->toArray());
    }

    public function test_edit_as_auditor(): void
    {
        $this->project->name = $this->faker->text;
        $this->project->description = $this->faker->text;

        $response = $this->actingAs($this->auditor)->postJson(route('projects.edit'), $this->project->toArray());

        $response->assertForbidden();
    }

    public function test_edit_as_detached_user(): void
    {
        $this->project->name = $this->faker->text;
        $this->project->description = $this->faker->text;

        $response = $this->actingAs($this->user)->postJson(route('projects.edit'), $this->project->toArray());

        $response->assertForbidden();
    }

    public function test_edit_as_project_manager(): void
    {
        $this->project->name = $this->faker->text;
        $this->project->description = $this->faker->text;

        $response = $this->actingAs($this->projectManager)->postJson(route('projects.edit'), $this->project->toArray());

        $response->assertOk();
        $response->assertJson(['data' => $this->project->toArray()]);
        $this->assertDatabaseHas('projects', $this->project->toArray());
    }

    public function test_edit_as_project_auditor(): void
    {
        $this->project->name = $this->faker->text;
        $this->project->description = $this->faker->text;

        $response = $this->actingAs($this->projectAuditor)->postJson(route('projects.edit'), $this->project->toArray());

        $response->assertForbidden();
    }

    public function test_edit_as_project_user(): void
    {
        $this->project->name = $this->faker->text;
        $this->project->description = $this->faker->text;

        $response = $this->actingAs($this->projectUser)->postJson(route('projects.edit'), $this->project->toArray());

        $response->assertForbidden();
    }

    public function test_not_existing_project(): void
    {
        $this->project->id = $this->faker->randomNumber();

        $response = $this->actingAs($this->admin)->postJson(route('projects.edit'), $this->project->toArray());

        $response->assertValidationError();
    }

    public function test_unauthorized(): void
    {
        $response = $this->postJson(route('projects.edit'));

        $response->assertUnauthorized();
    }

    public function test_without_params(): void
    {
        $response = $this->actingAs($this->admin)->postJson(route('projects.edit'));

        $response->assertValidationError();
    }
}
