<?php

namespace Tests\Feature\Tasks;

use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Arr;
use Tests\Facades\TaskFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class ListTest extends TestCase
{
    private const URI = 'tasks/list';

    private const TASKS_AMOUNT = 10;

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

    public function test_list_as_admin(): void
    {
        $response = $this->actingAs($this->admin)->getJson(
            route('tasks.list'),
            ['X-Paginate' => 'false'],
        );

        $response->assertOk();

        $tasks = Task::query()
            ->leftJoin('statuses as s', 'tasks.status_id', '=', 's.id')
            ->select('tasks.*')
            ->orderBy('tasks.id')
            ->orderBy('s.active', 'desc')
            ->orderBy('tasks.created_at', 'desc')
            ->get();

        $response->assertJson(['data' => $tasks->toArray()]);
    }

    public function test_list_as_manager(): void
    {
        $response = $this->actingAs($this->manager)->getJson(
            route('tasks.list'),
            ['X-Paginate' => 'false'],
        );

        $response->assertOk();

        $tasks = Task::query()
            ->leftJoin('statuses as s', 'tasks.status_id', '=', 's.id')
            ->select('tasks.*')
            ->orderBy('tasks.id')
            ->orderBy('s.active', 'desc')
            ->orderBy('tasks.created_at', 'desc')
            ->get();

        $response->assertJson(['data' => $tasks->toArray()]);
    }

    public function test_list_as_auditor(): void
    {
        $response = $this->actingAs($this->auditor)->getJson(
            route('tasks.list'),
            ['X-Paginate' => 'false'],
        );

        $response->assertOk();

        $tasks = Task::query()
            ->leftJoin('statuses as s', 'tasks.status_id', '=', 's.id')
            ->select('tasks.*')
            ->orderBy('tasks.id')
            ->orderBy('s.active', 'desc')
            ->orderBy('tasks.created_at', 'desc')
            ->get();

        $response->assertJson(['data' => $tasks->toArray()]);
    }

    public function test_list_as_user(): void
    {
        $response = $this->actingAs($this->user)->getJson(
            route('tasks.list'),
            ['X-Paginate' => 'false'],
        );

        $response->assertOk();
//        $response->assertExactJson([]);
    }

    public function test_list_as_assigned_user(): void
    {
        $response = $this->actingAs($this->assignedUser)->getJson(
            route('tasks.list'),
            ['X-Paginate' => 'false'],
        );

        $response->assertOk();
        $task = Task::query()
            ->where('id', '=', $this->assignedTask->id)
            ->first();

        $responseData = Arr::keyBy($response->json('data'), 'id');
        $this->assertEquals($responseData[$task->getKey()], $task->toArray());
    }

    public function test_list_as_project_manager(): void
    {
        $response = $this->actingAs($this->projectManager)->getJson(
            route('tasks.list'),
            ['X-Paginate' => 'false'],
        );

        $task = Task::where('project_id', $this->task->project_id)->get()->toArray();

        $response->assertOk();
        $response->assertJson(['data' => $task]);
    }

    public function test_list_as_project_auditor(): void
    {
        $response = $this->actingAs($this->projectAuditor)->getJson(
            route('tasks.list'),
            ['X-Paginate' => 'false'],
        );

        $task = Task::where('project_id', $this->task->project_id)->get()->toArray();

        $response->assertOk();
        $response->assertJson(['data' => $task]);
    }

    public function test_list_as_project_user(): void
    {
        $response = $this->actingAs($this->projectUser)->getJson(
            route('tasks.list'),
            ['X-Paginate' => 'false'],
        );

        $task = Task::where('project_id', $this->task->project_id)->get()->toArray();

        $response->assertOk();
        $response->assertJson(['data' => $task]);
    }

    public function test_list_as_assigned_project_user(): void
    {
        $response = $this
            ->actingAs($this->assignedProjectUser)
            ->postJson(
                route('tasks.list'),
                $this->assignedProjectTask->only('id'),
                ['X-Paginate' => 'false'],
            );

        $task = Task::query()
            ->where('project_id', $this->assignedProjectTask->project_id)
            ->orderBy('id')
            ->first();

        $response->assertOk();
        $responseData = Arr::keyBy($response->json('data'), 'id');
        $this->assertEquals($responseData[$task->getKey()], $task->toArray());
    }

    public function test_unauthorized(): void
    {
        $response = $this->getJson(route('tasks.list'));

        $response->assertUnauthorized();
    }
}
