<?php

namespace Tests\Feature\Tasks;

use App\Models\Task;
use App\Models\User;
use Tests\Facades\TaskFactory;
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

    /** @var User $assignedUser */
    private User $assignedUser;
    /** @var Task $assignedTask */
    private Task $assignedTask;

    /** @var User $assignedProjectUser */
    private User $assignedProjectUser;
    /** @var Task $assignedProjectTask */
    private Task $assignedProjectTask;

    /** @var Task $task */
    private Task $task;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::refresh()->asAdmin()->withTokens()->create();
        $this->manager = UserFactory::refresh()->asManager()->withTokens()->create();
        $this->auditor = UserFactory::refresh()->asAuditor()->withTokens()->create();
        $this->user = UserFactory::refresh()->asUser()->withTokens()->create();

        $this->task = TaskFactory::create();

        $this->projectManager = UserFactory::refresh()->asUser()->withTokens()->create();
        $this->projectManager->projects()->attach($this->task->project_id, ['role_id' => 1]);

        $this->projectAuditor = UserFactory::refresh()->asUser()->withTokens()->create();
        $this->projectAuditor->projects()->attach($this->task->project_id, ['role_id' => 3]);

        $this->projectUser = UserFactory::refresh()->asUser()->withTokens()->create();
        $this->projectUser->projects()->attach($this->task->project_id, ['role_id' => 2]);

        $this->assignedUser = UserFactory::refresh()->asUser()->withTokens()->create();
        $this->assignedTask = TaskFactory::refresh()->forUser($this->assignedUser)->create();

        $this->assignedProjectUser = UserFactory::refresh()->asUser()->withTokens()->create();
        $this->assignedProjectTask = TaskFactory::refresh()->forUser($this->assignedProjectUser)->create();
        $this->assignedProjectUser->projects()->attach($this->assignedProjectTask->project_id, ['role_id' => 2]);
    }

    public function test_show_as_admin(): void
    {
        $response = $this->actingAs($this->admin)->postJson(
            route('tasks.show'), $this->task->only('id'),
            headers: ['X-Paginate' => 'false'],
        );

        $response->assertOk();
        $response->assertJson(['data' => $this->task->toArray()]);
    }

    public function test_show_as_manager(): void
    {
        $response = $this->actingAs($this->manager)->postJson(
            route('tasks.show'), $this->task->only('id'),
            headers: ['X-Paginate' => 'false'],
        );

        $response->assertOk();
        $response->assertJson(['data' => $this->task->toArray()]);
    }

    public function test_show_as_auditor(): void
    {
        $response = $this->actingAs($this->auditor)->postJson(
            route('tasks.show'), $this->task->only('id'),
            headers: ['X-Paginate' => 'false'],
        );

        $response->assertOk();
        $response->assertJson(['data' => $this->task->toArray()]);
    }

    public function test_show_as_user(): void
    {
        $response = $this->actingAs($this->user)->postJson(route('tasks.show'), $this->task->only('id'));

        $response->assertOk();
    }

    public function test_show_as_assigned_user(): void
    {
        $response = $this
            ->actingAs($this->assignedUser)
            ->postJson(
                route('tasks.show'), $this->assignedTask->only('id'),
                headers: ['X-Paginate' => 'false'],
            );

        $response->assertOk();
        $response->assertJson(['data' => $this->assignedTask->toArray()]);
    }

    public function test_show_as_project_manager(): void
    {
        $response = $this->actingAs($this->projectManager)->postJson(
            route('tasks.show'), $this->task->only('id'),
            headers: ['X-Paginate' => 'false'],
        );

        $response->assertOk();
        $response->assertJson(['data' => $this->task->toArray()]);
    }

    public function test_show_as_project_auditor(): void
    {
        $response = $this->actingAs($this->projectAuditor)->postJson(
            route('tasks.show'), $this->task->only('id'),
            headers: ['X-Paginate' => 'false'],
        );

        $response->assertOk();
        $response->assertJson(['data' => $this->task->toArray()]);
    }

    public function test_show_as_project_user(): void
    {
        $response = $this->actingAs($this->projectUser)->postJson(
            route('tasks.show'),
            $this->task->only('id'),
            ['X-Paginate' => 'false'],
        );

        $response->assertOk();
        $response->assertJson(['data' => $this->task->toArray()]);
    }

    public function test_show_as_assigned_project_user(): void
    {
        $response = $this
            ->actingAs($this->assignedProjectUser)
            ->postJson(
                route('tasks.show'),
                $this->assignedProjectTask->only('id'),
                headers: ['X-Paginate' => 'false'],
            );

        $response->assertOk();
        $response->assertJson(['data' => $this->assignedProjectTask->toArray()]);
    }

    public function test_unauthorized(): void
    {
        $response = $this->postJson(route('tasks.show'));

        $response->assertUnauthorized();
    }

    public function test_without_params(): void
    {
        $response = $this->actingAs($this->admin)->postJson(route('tasks.show'));

        $response->assertValidationError();
    }
}
