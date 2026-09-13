<?php

namespace Tests\Feature\Projects;

use App\Enums\Role;
use App\Models\Project;
use App\Models\User;
use Tests\Facades\ProjectFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class CountTest extends TestCase
{
    private const PROJECTS_AMOUNT = 10;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::asAdmin()->withTokens()->create();

        ProjectFactory::createMany(self::PROJECTS_AMOUNT)
            ->each(fn (Project $project) => $this->admin->projects()->attach($project, ['role_id' => Role::ADMIN]));
    }

    public function test_count(): void
    {
        $response = $this->actingAs($this->admin)->getJson(route('projects.count'));

        $response->assertOk();
        $response->assertJson(['data' => ['total' => Project::count()]]);
    }

    public function test_unauthorized(): void
    {
        $response = $this->getJson(route('projects.count'));

        $response->assertUnauthorized();
    }
}
