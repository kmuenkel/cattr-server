<?php

namespace Tests\Feature\Tasks;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Event;
use Tests\Facades\ProjectFactory;
use Tests\Facades\TaskFactory;
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

    /** @var Task $task */
    private Task $task;
    /** @var array $taskRequest */
    private array $taskRequest;
    /** @var array */
    private $taskRequestWithMultipleUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::refresh()->asAdmin()->withTokens()->create();
        $this->manager = UserFactory::refresh()->asManager()->withTokens()->create();
        $this->auditor = UserFactory::refresh()->asAuditor()->withTokens()->create();
        $this->user = UserFactory::refresh()->asUser()->withTokens()->create();

        $project = ProjectFactory::create();
        $project->update(['created_by' => $this->manager->getKey()]);

        $this->task = TaskFactory::create([
            'project_id' => $project->id,
            'priority_id' => $project->default_priority_id,
        ]);

        $this->taskRequest = array_merge($this->task->toArray(), [
            'users' => [UserFactory::create()->id],
        ]);

        $this->taskRequestWithMultipleUsers = array_merge($this->task->toArray(), [
            'users' => [
                UserFactory::create()->id,
                UserFactory::create()->id,
                UserFactory::create()->id,
            ],
        ]);

        $this->projectManager = UserFactory::refresh()->asUser()->withTokens()->create();
        $this->projectManager->projects()->attach($this->task->project_id, ['role_id' => 1]);

        $this->projectAuditor = UserFactory::refresh()->asUser()->withTokens()->create();
        $this->projectAuditor->projects()->attach($this->task->project_id, ['role_id' => 3]);

        $this->projectUser = UserFactory::refresh()->asUser()->withTokens()->create();
        $this->projectUser->projects()->attach($this->task->project_id, ['role_id' => 2]);

        Event::fake();
    }

    public function test_edit_without_user(): void
    {
        $this->task->users = $this->taskRequest['users'] = [];

        $response = $this->actingAs($this->admin)->postJson(route('tasks.edit'), $this->taskRequest);

        $response->assertSuccess();

        $task = $this->task->toArray();
        unset($task['users']);

        $response->assertJson(['data' => $task]);
        $this->assertDatabaseHas('tasks', ['id' => $this->task->id]);
    }

    public function test_edit_as_admin(): void
    {
        $this->task->description = $this->taskRequest['description'] = $this->faker->text;

        $response = $this->actingAs($this->admin)->postJson(route('tasks.edit'), $this->taskRequest);

        $response->assertOk();
        $response->assertJson(['data' => $this->task->toArray()]);
        $this->assertDatabaseHas('tasks', ['id' => $this->task->id, 'description' => $this->taskRequest['description']]);

        foreach ($this->taskRequest['users'] as $user) {
            $this->assertDatabaseHas('tasks_users', [
                'task_id' => $response->json()['data']['id'],
                'user_id' => $user,
            ]);
        }
    }

    public function test_edit_with_multiple_users_as_admin(): void
    {
        $this->task->description = $this->taskRequestWithMultipleUsers['description'] = $this->faker->text;

        $response = $this->actingAs($this->admin)->postJson(route('tasks.edit'), $this->taskRequestWithMultipleUsers);

        $response->assertOk();
        $response->assertJson(['data' => $this->task->toArray()]);
        $this->assertDatabaseHas('tasks', ['id' => $this->task->id, 'description' => $this->taskRequestWithMultipleUsers['description']]);

        foreach ($this->taskRequestWithMultipleUsers['users'] as $user) {
            $this->assertDatabaseHas('tasks_users', [
                'task_id' => $response->json()['data']['id'],
                'user_id' => $user,
            ]);
        }
    }

    public function test_edit_as_manager(): void
    {
        $this->task->description = $this->taskRequest['description'] = $this->faker->text;

        $response = $this->actingAs($this->manager)->postJson(route('tasks.edit'), $this->taskRequest);

        $response->assertOk();
        $response->assertJson(['data' => $this->task->toArray()]);
        $this->assertDatabaseHas('tasks', ['id' => $this->task->id, 'description' => $this->taskRequest['description']]);

        foreach ($this->taskRequest['users'] as $user) {
            $this->assertDatabaseHas('tasks_users', [
                'task_id' => $response->json()['data']['id'],
                'user_id' => $user,
            ]);
        }
    }

    public function test_edit_with_multiple_users_as_manager(): void
    {
        $this->task->description = $this->taskRequestWithMultipleUsers['description'] = $this->faker->text;

        $response = $this->actingAs($this->manager)->postJson(route('tasks.edit'), $this->taskRequestWithMultipleUsers);

        $response->assertOk();
        $response->assertJson(['data' => $this->task->toArray()]);
        $this->assertDatabaseHas('tasks', ['id' => $this->task->id, 'description' => $this->taskRequestWithMultipleUsers['description']]);

        foreach ($this->taskRequestWithMultipleUsers['users'] as $user) {
            $this->assertDatabaseHas('tasks_users', [
                'task_id' => $response->json()['data']['id'],
                'user_id' => $user,
            ]);
        }
    }

    public function test_edit_as_auditor(): void
    {
        $this->task->description = $this->taskRequest['description'] = $this->faker->text;

        $response = $this->actingAs($this->auditor)->postJson(route('tasks.edit'), $this->taskRequest);

        $response->assertForbidden();
    }

    public function test_edit_as_user(): void
    {
        $this->task->description = $this->taskRequest['description'] = $this->faker->text;

        $response = $this->actingAs($this->user)->postJson(route('tasks.edit'), $this->taskRequest);

        $response->assertForbidden();
    }

    public function test_edit_as_project_manager(): void
    {
        $this->task->description = $this->taskRequest['description'] = $this->faker->text;

        $response = $this->actingAs($this->projectManager)->postJson(route('tasks.edit'), $this->taskRequest);

        $response->assertOk();
        $response->assertJson(['data' => $this->task->toArray()]);
        $this->assertDatabaseHas('tasks', ['id' => $this->task->id, 'description' => $this->taskRequest['description']]);

        foreach ($this->taskRequest['users'] as $user) {
            $this->assertDatabaseHas('tasks_users', [
                'task_id' => $response->json()['data']['id'],
                'user_id' => $user,
            ]);
        }
    }

    public function test_edit_with_multiple_users_as_project_manager(): void
    {
        $this->task->description = $this->taskRequestWithMultipleUsers['description'] = $this->faker->text;

        $response = $this->actingAs($this->projectManager)->postJson(route('tasks.edit'), $this->taskRequestWithMultipleUsers);

        $response->assertOk();
        $response->assertJson(['data' => $this->task->toArray()]);
        $this->assertDatabaseHas('tasks', ['id' => $this->task->id, 'description' => $this->taskRequestWithMultipleUsers['description']]);

        foreach ($this->taskRequestWithMultipleUsers['users'] as $user) {
            $this->assertDatabaseHas('tasks_users', [
                'task_id' => $response->json()['data']['id'],
                'user_id' => $user,
            ]);
        }
    }

    public function test_edit_as_project_auditor(): void
    {
        $this->task->description = $this->taskRequest['description'] = $this->faker->text;

        $response = $this->actingAs($this->projectAuditor)->postJson(route('tasks.edit'), $this->taskRequest);

        $response->assertForbidden();
    }

    public function test_edit_as_project_project_user(): void
    {
        $this->task->description = $this->taskRequest['description'] = $this->faker->text;

        $response = $this->actingAs($this->projectUser)->postJson(route('tasks.edit'), $this->taskRequest);

        $response->assertForbidden();
    }

    public function test_edit_not_existing(): void
    {
        $this->taskRequest['id'] = Task::withoutGlobalScopes()->count() + 20;

        $response = $this->actingAs($this->admin)->postJson(route('tasks.edit'), $this->taskRequest);

        $response->assertValidationError();
    }

    public function test_unauthorized(): void
    {
        $response = $this->postJson(route('tasks.edit'));

        $response->assertUnauthorized();
    }

    public function test_without_params(): void
    {
        $response = $this->actingAs($this->admin)->postJson(route('tasks.edit'));

        $response->assertValidationError();
    }
}
