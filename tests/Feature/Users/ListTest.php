<?php

namespace Tests\Feature\Users;

use App\Enums\Role;
use App\Models\Project;
use App\Models\User;
use App\Scopes\UserAccessScope;
use BackedEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Tests\Facades\ProjectFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class ListTest extends TestCase
{
    use RefreshDatabase;

    private const USERS_AMOUNT = 10;

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

        UserFactory::createMany(self::USERS_AMOUNT);

        $this->project = ProjectFactory::create();

        $this->projectManager = UserFactory::refresh()->asUser()->withTokens()->create();
        $this->projectManager->projects()->attach($this->project->id, ['role_id' => Role::MANAGER]);

        $this->projectAuditor = UserFactory::refresh()->asUser()->withTokens()->create();
        $this->projectAuditor->projects()->attach($this->project->id, ['role_id' => Role::AUDITOR]);

        $this->projectUser = UserFactory::refresh()->asUser()->withTokens()->create();
        $this->projectUser->projects()->attach($this->project->id, ['role_id' => Role::USER]);
    }

    public function test_list_as_admin(): void
    {
        $response = $this->actingAs($this->admin)->getJson(
            route('users.list'),
            ['X-Paginate' => 'false'],
        );

        $users = User::withoutGlobalScopes()->setEagerLoads([])->get()->toArray();

        $response->assertOk();
        $response->assertExactJson(['data' => $users, 'status' => 200, 'success' => true]);
    }

    public function test_list_as_manager(): void
    {
        $response = $this->actingAs($this->manager)->getJson(
            route('users.list'),
            ['X-Paginate' => 'false'],
        );

        $users = User::withoutGlobalScopes()->setEagerLoads([])->get()->toArray();

        $response->assertOk();
        $response->assertExactJson(['data' => $users, 'status' => 200, 'success' => true]);
    }

    public function test_list_as_auditor(): void
    {
        $response = $this->actingAs($this->auditor)->getJson(
            route('users.list'),
            ['X-Paginate' => 'false'],
        );

        $users = User::withoutGlobalScopes()->setEagerLoads([])->get()->toArray();

        $response->assertOk();
        $response->assertExactJson(['data' => $users, 'status' => 200, 'success' => true]);
    }

    public function test_list_as_user(): void
    {
        $response = $this->actingAs($this->user)->getJson(route('users.list'));

        $users = User::withoutGlobalScopes()
            ->where('id', $this->user->id)
            ->setEagerLoads([])
            ->get();

        $response->assertOk();
        $expected = $users->map(fn (User $user) => Arr::map(
            $user->toArray(),
            fn ($value) => $value instanceof BackedEnum ? $value->value : $value
        ))->toArray();
        $response->assertJson(['data' => $expected, 'status' => 200, 'success' => true]);
    }

    public function test_list_as_project_manager(): void
    {
        $response = $this->actingAs($this->projectManager)->getJson(
            route('users.list'),
            ['X-Paginate' => 'false'],
        );

        $users = User::withoutGlobalScopes()
            ->whereHas('projects', function ($query) {
                $query->where('project_id', $this->project->id);
            })
            ->setEagerLoads([])
            ->get();

        $response->assertOk();
        $expected = $users->map(fn (User $user) => Arr::map(
            $user->toArray(),
            fn ($value) => $value instanceof BackedEnum ? $value->value : $value
        ))->toArray();
        $response->assertJson(['data' => $expected]);
    }

    public function test_list_as_project_manager_with_global_scope(): void
    {
        $this->markTestSkipped('"global_scope" parameter is no longer supported');

        $response = $this->actingAs($this->projectManager)->postJson(
            route('users.list'),
            ['global_scope' => true],
            ['X-Paginate' => 'false'],
        );

        $users = User::withoutGlobalScope(UserAccessScope::class)
            ->setEagerLoads([])
            ->get();

        $response->assertOk();
        $expected = $users->map(fn (User $user) => Arr::map(
            $user->toArray(),
            fn ($value) => $value instanceof BackedEnum ? $value->value : $value
        ))->toArray();
        $response->assertJson(['data' => $expected]);
    }

    public function test_list_as_project_auditor(): void
    {
        $response = $this->actingAs($this->projectAuditor)->getJson(
            route('users.list'),
            ['X-Paginate' => 'false'],
        );

        $users = User::withoutGlobalScopes()
            ->whereHas('projects', function ($query) {
                $query->where('project_id', $this->project->id);
            })
            ->setEagerLoads([])
            ->get();

        $response->assertOk();
        $expected = $users->map(fn (User $user) => Arr::map(
            $user->toArray(),
            fn ($value) => $value instanceof BackedEnum ? $value->value : $value
        ))->toArray();
        $response->assertJson(['data' => $expected]);
    }

    public function test_list_as_project_user(): void
    {
        $response = $this->actingAs($this->projectManager)->getJson(
            route('users.list'),
            ['X-Paginate' => 'false'],
        );

        $users = User::withoutGlobalScopes()
            ->whereHas('projects', function ($query) {
                $query->where('project_id', $this->project->id);
            })
            ->setEagerLoads([])
            ->get();

        $response->assertOk();
        $expected = $users->map(fn (User $user) => Arr::map(
            $user->toArray(),
            fn ($value) => $value instanceof BackedEnum ? $value->value : $value
        ))->toArray();
        $response->assertJson(['data' => $expected]);
    }

    public function test_unauthorized(): void
    {
        $response = $this->getJson(route('users.list'));

        $response->assertUnauthorized();
    }
}
