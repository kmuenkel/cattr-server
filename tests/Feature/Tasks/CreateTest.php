<?php

namespace Tests\Feature\Tasks;

use App\Models\User;
use Illuminate\Support\Facades\Event;
use Tests\Facades\ProjectFactory;
use Tests\Facades\TaskFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class CreateTest extends TestCase
{
    private const URI = 'tasks/create';

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

    /** @var array */
    private $taskData;
    /** @var array */
    private $taskRequest;
    /** @var array */
    private $taskRequestWithMultipleUsers;

    protected function setUp(): void
    {
        parent::setUp();

        Event::fake();

        $this->admin = UserFactory::refresh()->asAdmin()->withTokens()->create();
        $this->manager = UserFactory::refresh()->asManager()->withTokens()->create();
        $this->auditor = UserFactory::refresh()->asAuditor()->withTokens()->create();
        $this->user = UserFactory::refresh()->asUser()->withTokens()->create();

        $project = ProjectFactory::create();
        $this->taskData = array_merge(TaskFactory::createRandomModelData(), [
            'project_id' => $project->id,
            'priority_id' => $project->default_priority_id
        ]);

        $this->taskRequest = array_merge($this->taskData, [
            'users' => [UserFactory::create()->id],
        ]);

        $this->taskRequestWithMultipleUsers = array_merge($this->taskData, [
            'users' => [
                UserFactory::create()->id,
                UserFactory::create()->id,
                UserFactory::create()->id,
            ],
        ]);

        $this->projectManager = UserFactory::refresh()->asUser()->withTokens()->create();
        $this->projectManager->projects()->attach($this->taskData['project_id'], ['role_id' => 1]);

        $this->projectAuditor = UserFactory::refresh()->asUser()->withTokens()->create();
        $this->projectAuditor->projects()->attach($this->taskData['project_id'], ['role_id' => 3]);

        $this->projectUser = UserFactory::refresh()->asUser()->withTokens()->create();
        $this->projectUser->projects()->attach($this->taskData['project_id'], ['role_id' => 2]);
    }

    public function test_create_without_user(): void
    {
        $this->taskRequest['users'] = [];

        $this->assertDatabaseMissing('tasks', $this->taskData);

        $response = $this->actingAs($this->admin)->postJson(route('tasks.create'), $this->taskRequest);

        $response->assertSuccess();
        $response->assertJson(['data' => $this->taskData]);
        $this->assertDatabaseHas('tasks', $this->taskData);
    }

    public function test_create_as_admin(): void
    {
        $this->assertDatabaseMissing('tasks', $this->taskData);

        $response = $this->actingAs($this->admin)->postJson(route('tasks.create'), $this->taskRequest);

        $response->assertOk();
        $response->assertJson(['data' => $this->taskData]);
        $this->assertDatabaseHas('tasks', $this->taskData);

        foreach ($this->taskRequest['users'] as $user) {
            $this->assertDatabaseHas('tasks_users', [
                'task_id' => $response->json()['data']['id'],
                'user_id' => $user,
            ]);
        }
    }

    public function test_create_with_multiple_users_as_admin(): void
    {
        $this->assertDatabaseMissing('tasks', $this->taskData);

        $response = $this->actingAs($this->admin)->postJson(route('tasks.create'), $this->taskRequestWithMultipleUsers);

        $response->assertOk();
        $response->assertJson(['data' => $this->taskData]);
        $this->assertDatabaseHas('tasks', $this->taskData);

        foreach ($this->taskRequestWithMultipleUsers['users'] as $user) {
            $this->assertDatabaseHas('tasks_users', [
                'task_id' => $response->json()['data']['id'],
                'user_id' => $user,
            ]);
        }
    }

    public function test_create_as_manager(): void
    {
        $this->assertDatabaseMissing('tasks', $this->taskData);

        $response = $this->actingAs($this->manager)->postJson(route('tasks.create'), $this->taskRequest);

        $response->assertOk();
        $response->assertJson(['data' => $this->taskData]);
        $this->assertDatabaseHas('tasks', $this->taskData);

        foreach ($this->taskRequest['users'] as $user) {
            $this->assertDatabaseHas('tasks_users', [
                'task_id' => $response->json()['data']['id'],
                'user_id' => $user,
            ]);
        }
    }

    public function test_create_with_multiple_users_as_manager(): void
    {
        $this->assertDatabaseMissing('tasks', $this->taskData);

        $response = $this->actingAs($this->manager)->postJson(route('tasks.create'), $this->taskRequestWithMultipleUsers);

        $response->assertOk();
        $response->assertJson(['data' => $this->taskData]);
        $this->assertDatabaseHas('tasks', $this->taskData);

        foreach ($this->taskRequestWithMultipleUsers['users'] as $user) {
            $this->assertDatabaseHas('tasks_users', [
                'task_id' => $response->json()['data']['id'],
                'user_id' => $user,
            ]);
        }
    }

    public function test_create_as_auditor(): void
    {
        $response = $this->actingAs($this->auditor)->postJson(route('tasks.create'), $this->taskRequest);

        $response->assertForbidden();
    }

    public function test_create_as_user(): void
    {
        $response = $this->actingAs($this->user)->postJson(route('tasks.create'), $this->taskRequest);

        $response->assertForbidden();
    }

    public function test_create_as_project_manager(): void
    {
        $this->assertDatabaseMissing('tasks', $this->taskData);

        $response = $this->actingAs($this->projectManager)->postJson(route('tasks.create'), $this->taskRequest);

        $response->assertOk();
        $response->assertJson(['data' => $this->taskData]);
        $this->assertDatabaseHas('tasks', $this->taskData);

        foreach ($this->taskRequest['users'] as $user) {
            $this->assertDatabaseHas('tasks_users', [
                'task_id' => $response->json()['data']['id'],
                'user_id' => $user,
            ]);
        }
    }

    public function test_create_with_multiple_users_as_project_manager(): void
    {
        $this->assertDatabaseMissing('tasks', $this->taskData);

        $response = $this->actingAs($this->projectManager)->postJson(route('tasks.create'), $this->taskRequestWithMultipleUsers);

        $response->assertOk();
        $response->assertJson(['data' => $this->taskData]);
        $this->assertDatabaseHas('tasks', $this->taskData);

        foreach ($this->taskRequestWithMultipleUsers['users'] as $user) {
            $this->assertDatabaseHas('tasks_users', [
                'task_id' => $response->json()['data']['id'],
                'user_id' => $user,
            ]);
        }
    }

    public function test_create_as_project_auditor(): void
    {
        $response = $this->actingAs($this->projectAuditor)->postJson(route('tasks.create'), $this->taskRequest);

        $response->assertForbidden();
    }

    public function test_create_as_project_user(): void
    {
        $response = $this->actingAs($this->projectUser)->postJson(route('tasks.create'), $this->taskRequest);

        $response->assertOk();
        unset($this->taskRequest['users']);
        $this->assertEmpty(array_diff_assoc($this->taskRequest, $response->json('data')));
    }

    public function test_unauthorized(): void
    {
        $response = $this->postJson(route('tasks.create'));

        $response->assertUnauthorized();
    }

    public function test_without_params(): void
    {
        $response = $this->actingAs($this->admin)->postJson(route('tasks.create'));

        $response->assertValidationError();
    }
}
