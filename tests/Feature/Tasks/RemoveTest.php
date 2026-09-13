<?php

namespace Tests\Feature\Tasks;

use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Tests\Facades\TaskFactory;
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

    /** @var User $projectManager */
    private User $projectManager;
    /** @var User $projectAuditor */
    private User $projectAuditor;
    /** @var User $projectUser */
    private User $projectUser;

    /** @var Task $task */
    private Task $task;

    protected function setUp(): void
    {
        parent::setUp();

        Event::fake();

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
    }

    public function test_remove_as_admin(): void
    {
        $this->assertDatabaseHas('tasks', ['id' => $this->task->id]);

        $response = $this->actingAs($this->admin)->postJson(route('tasks.destroy'), $this->task->only('id'));

        $response->assertNoContent();
        $this->assertSoftDeleted('tasks', $this->task->only('id'));
    }

    public function test_remove_as_manager(): void
    {
        $this->assertDatabaseHas('tasks', ['id' => $this->task->id]);

        $response = $this->actingAs($this->manager)->postJson(route('tasks.destroy'), $this->task->only('id'));

        $response->assertNoContent();
        $this->assertSoftDeleted('tasks', $this->task->only('id'));
    }

    public function test_remove_as_auditor(): void
    {
        $this->assertDatabaseHas('tasks', ['id' => $this->task->id]);

        $response = $this->actingAs($this->auditor)->postJson(route('tasks.destroy'), $this->task->only('id'));

        $response->assertForbidden();
    }

    public function test_remove_as_project_manager(): void
    {
        $this->assertDatabaseHas('tasks', ['id' => $this->task->id]);

        $response = $this->actingAs($this->projectManager)->postJson(route('tasks.destroy'), $this->task->only('id'));

        $response->assertNoContent();
        $this->assertSoftDeleted('tasks', $this->task->only('id'));
    }

    public function test_remove_as_project_auditor(): void
    {
        $this->assertDatabaseHas('tasks', ['id' => $this->task->id]);

        $response = $this->actingAs($this->projectAuditor)->postJson(route('tasks.destroy'), $this->task->only('id'));

        $response->assertForbidden();
    }

    public function test_remove_as_project_user(): void
    {
        $this->assertDatabaseHas('tasks', ['id' => $this->task->id]);

        $response = $this->actingAs($this->projectUser)->postJson(route('tasks.destroy'), $this->task->only('id'));

        $response->assertForbidden();
    }

    public function test_remove_not_existing(): void
    {
        $response = $this->actingAs($this->admin)->postJson(route('tasks.destroy'));

        $response->assertValidationError();
    }

    public function test_unauthorized(): void
    {
        $response = $this->postJson(route('tasks.destroy'));

        $response->assertUnauthorized();
    }

    public function test_without_params(): void
    {
        $response = $this->actingAs($this->admin)->postJson(route('tasks.destroy'));

        $response->assertValidationError();
    }
}
