<?php

namespace Tests\Feature\Projects;

use App\Models\User;
use Illuminate\Support\Facades\Event;
use Tests\Facades\ProjectFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class CreateTest extends TestCase
{
    /** @var User $admin */
    private User $admin;
    /** @var User $manager */
    private User $manager;
    /** @var User $auditor */
    private User $auditor;
    /** @var User $user */
    private User $user;

    private array $projectData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::refresh()->asAdmin()->withTokens()->create();
        $this->manager = UserFactory::refresh()->asManager()->withTokens()->create();
        $this->auditor = UserFactory::refresh()->asAuditor()->withTokens()->create();
        $this->user = UserFactory::refresh()->asUser()->withTokens()->create();

        $this->projectData = ProjectFactory::createRandomModelData();
        Event::fake();
    }

    public function test_create_as_admin(): void
    {
        $this->assertDatabaseMissing('projects', $this->projectData);

        $response = $this->actingAs($this->admin)->postJson(route('projects.create'), $this->projectData);

        $response->assertOk();
        $this->assertEmpty(array_diff_assoc($this->projectData, $response->json('data')));
        $this->assertDatabaseHas('projects', $this->projectData);
    }

    public function test_create_as_manager(): void
    {
        $this->assertDatabaseMissing('projects', $this->projectData);

        $response = $this->actingAs($this->manager)->postJson(route('projects.create'), $this->projectData);

        $response->assertOk();
        $response->assertJson(['data' => $this->projectData]);
        $this->assertDatabaseHas('projects', $this->projectData);
    }

    public function test_create_as_auditor(): void
    {
        $this->assertDatabaseMissing('projects', $this->projectData);

        $response = $this->actingAs($this->auditor)->postJson(route('projects.create'), $this->projectData);

        $response->assertForbidden();
    }

    public function test_create_as_user(): void
    {
        $this->assertDatabaseMissing('projects', $this->projectData);

        $response = $this->actingAs($this->user)->postJson(route('projects.create'), $this->projectData);

        $response->assertForbidden();
    }

    public function test_unauthorized(): void
    {
        $response = $this->postJson(route('projects.create'));

        $response->assertUnauthorized();
    }

    public function test_without_params(): void
    {
        $response = $this->actingAs($this->admin)->postJson(route('projects.create'));

        $response->assertValidationError();
    }
}
