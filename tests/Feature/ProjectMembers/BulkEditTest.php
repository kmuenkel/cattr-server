<?php

namespace Tests\Feature\ProjectMembers;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Event;
use Tests\Facades\UserFactory;
use Tests\Facades\ProjectFactory;
use Tests\TestCase;

class BulkEditTest extends TestCase
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
        $this->admin->update(['email' => 'admin_' . $this->admin->email]);

        $this->manager = UserFactory::refresh()->asManager()->withTokens()->create();
        $this->manager->update(['email' => 'manager_' . $this->manager->email]);

        $this->auditor = UserFactory::refresh()->asAuditor()->withTokens()->create();
        $this->auditor->update(['email' => 'auditor_' . $this->auditor->email]);

        $this->user = UserFactory::refresh()->asUser()->withTokens()->create();
        $this->user->update(['email' => 'user_' . $this->user->email]);

        $this->project = ProjectFactory::create();
        $this->project->update(['created_by' => $this->manager->getKey()]);

        $this->projectManager = UserFactory::refresh()->asUser()->withTokens()->create();
        $this->projectManager->projects()->attach($this->project->id, ['role_id' => 1]);

        $this->projectAuditor = UserFactory::refresh()->asUser()->withTokens()->create();
        $this->projectAuditor->projects()->attach($this->project->id, ['role_id' => 3]);

        $this->projectUser = UserFactory::refresh()->asUser()->withTokens()->create();
        $this->projectUser->projects()->attach($this->project->id, ['role_id' => 2]);
    }

    public function test_bulk_edit_as_admin(): void
    {
        $response = $this->actingAs($this->admin)->postJson(route('projects_members.edit'), $this->generateRequest());

        $response->assertNoContent();
    }

    public function test_bulk_edit_as_manager(): void
    {
        $response = $this->actingAs($this->manager)->postJson(route('projects_members.edit'), $this->generateRequest());

        $response->assertNoContent();
    }

    public function test_bulk_edit_as_auditor(): void
    {
        $response = $this->actingAs($this->auditor)->postJson(route('projects_members.edit'), $this->generateRequest());

        $response->assertForbidden();
    }

    public function test_bulk_edit_as_user(): void
    {
        $response = $this->actingAs($this->user)->postJson(route('projects_members.edit'), $this->generateRequest());

        $response->assertForbidden();
    }

    public function test_bulk_edit_as_project_manager(): void
    {
        $response = $this->actingAs($this->projectManager)->postJson(route('projects_members.edit'), $this->generateRequest());

        $response->assertNoContent();
    }

    public function test_bulk_edit_as_project_auditor(): void
    {
        $response = $this->actingAs($this->projectAuditor)->postJson(route('projects_members.edit'), $this->generateRequest());

        $response->assertForbidden();
    }

    public function test_bulk_edit_as_project_user(): void
    {
        $response = $this->actingAs($this->projectUser)->postJson(route('projects_members.edit'), $this->generateRequest());

        $response->assertForbidden();
    }

    public function test_not_existing_project(): void
    {
        $this->project->id = $this->faker->randomNumber();

        $response = $this->actingAs($this->admin)->postJson(route('projects_members.edit'), $this->generateRequest());

        $response->assertValidationError();
    }

    public function test_unauthorized(): void
    {
        $response = $this->postJson(route('projects_members.edit'));

        $response->assertUnauthorized();
    }

    public function test_without_params(): void
    {
        $response = $this->actingAs($this->admin)->postJson(route('projects_members.edit'));

        $response->assertValidationError();
    }

    private function generateRequest(): array
    {
        return [
            'project_id' => $this->project->id,
            'user_roles' => [
                [
                    'user_id' => UserFactory::refresh()->asUser()->create()->id,
                    'role_id' => $this->faker->numberBetween(1, 3),
                ]
            ],
        ];
    }
}
