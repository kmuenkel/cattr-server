<?php

namespace Tests\Feature\Projects;

use App\Enums\Role;
use App\Models\Project;
use App\Models\User;
use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Tests\Facades\ProjectFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class ListTest extends TestCase
{
    use RefreshDatabase;

    private const PROJECTS_AMOUNT = 10;

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

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::refresh()->asAdmin()->withTokens()->create();
        $this->manager = UserFactory::refresh()->asManager()->withTokens()->create();
        $this->auditor = UserFactory::refresh()->asAuditor()->withTokens()->create();
        $this->user = UserFactory::refresh()->asUser()->withTokens()->create();

        ProjectFactory::createMany(self::PROJECTS_AMOUNT);

        $this->projectManager = UserFactory::refresh()->asUser()->withTokens()->create();
        $this->projectManager->projects()->attach(Project::first()->id, ['role_id' => Role::MANAGER]);

        $this->projectAuditor = UserFactory::refresh()->asUser()->withTokens()->create();
        $this->projectAuditor->projects()->attach(Project::first()->id, ['role_id' => Role::AUDITOR]);

        $this->projectUser = UserFactory::refresh()->asUser()->withTokens()->create();
        $this->projectUser->projects()->attach(Project::first()->id, ['role_id' => Role::USER]);
    }

    public function test_list_as_admin(): void
    {
        $response = $this->actingAs($this->admin)->getJson(
            route('projects.list'),
            ['X-Paginate' => 'false'],
        );

        $response->assertOk();
        $expected = Project::all()->map(fn (Project $project) => Arr::map(
            $project->toArray(),
            fn ($value) => $value instanceof BackedEnum ? $value->value : $value
        ))->toArray();
        $response->assertJson(['data' => $expected]);
    }

    public function test_list_as_manager(): void
    {
        $response = $this->actingAs($this->manager)->getJson(
            route('projects.list'),
            ['X-Paginate' => 'false'],
        );

        $response->assertOk();
        $expected = Project::all()->map(fn (Project $project) => Arr::map(
            $project->toArray(),
            fn ($value) => $value instanceof BackedEnum ? $value->value : $value
        ))->toArray();
        $response->assertJson(['data' => $expected]);
    }

    public function test_list_as_auditor(): void
    {
        $response = $this->actingAs($this->auditor)->getJson(
            route('projects.list'),
            ['X-Paginate' => 'false'],
        );

        $response->assertOk();
        $expected = Project::all()->map(fn (Project $project) => Arr::map(
            $project->toArray(),
            fn ($value) => $value instanceof BackedEnum ? $value->value : $value
        ))->toArray();
        $response->assertJson(['data' => $expected]);
    }

    public function test_list_as_user(): void
    {
        $response = $this->actingAs($this->user)->getJson(route('projects.list'));

        $response->assertOk();
        $response->assertJson(['data' => []]);
    }

    public function test_list_as_project_manager(): void
    {
        $response = $this->actingAs($this->projectManager)->getJson(
            route('projects.list'),
            ['X-Paginate' => 'false'],
        );

        $whereRelatedUser = fn (Builder $query) => $query->whereKey($this->projectManager);
        $projects = Project::whereHas('users', $whereRelatedUser)->get()->map(fn (Project $project) => Arr::map(
            $project->toArray(),
            fn ($value) => $value instanceof BackedEnum ? $value->value : $value
        ))->toArray();

        $response->assertOk();
        $response->assertJson(['data' => $projects]);
    }

    public function test_list_as_project_auditor(): void
    {
        $response = $this->actingAs($this->projectAuditor)->getJson(
            route('projects.list'),
            ['X-Paginate' => 'false']
        );

        $whereRelatedUser = fn (Builder $query) => $query->whereKey($this->projectAuditor);
        $projects = Project::whereHas('users', $whereRelatedUser)->get()->map(fn (Project $project) => Arr::map(
            $project->toArray(),
            fn ($value) => $value instanceof BackedEnum ? $value->value : $value
        ))->toArray();

        $response->assertOk();
        $response->assertJson(['data' => $projects]);
    }

    public function test_list_as_project_user(): void
    {
        $response = $this->actingAs($this->projectUser)->getJson(
            route('projects.list'),
            ['X-Paginate' => 'false']
        );

        $whereRelatedUser = fn (Builder $query) => $query->whereKey($this->projectUser);
        $projects = Project::whereHas('users', $whereRelatedUser)->get()->map(fn (Project $project) => Arr::map(
            $project->toArray(),
            fn ($value) => $value instanceof BackedEnum ? $value->value : $value
        ))->toArray();

        $response->assertOk();
        $response->assertJson(['data' => $projects]);
    }

    public function test_unauthorized(): void
    {
        $response = $this->getJson(route('projects.list'));

        $response->assertUnauthorized();
    }
}
