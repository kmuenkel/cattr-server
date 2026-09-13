<?php

namespace Tests\Feature\Screenshots;

use App\Models\Screenshot;
use App\Models\TimeInterval;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Tests\Facades\ScreenshotFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class RemoveTest extends TestCase
{
    private User $admin;

    private TimeInterval $screenshot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::asAdmin()->withTokens()->create();

        $this->screenshot = ScreenshotFactory::fake()->create();
        Event::fake();
    }

    public function test_remove(): void
    {
        $screenshot = $this->screenshot->toArray();
        unset($screenshot['has_screenshot']);
        $this->assertDatabaseHas('time_intervals', $screenshot);

        $response = $this->actingAs($this->admin)->postJson(route('intervals.destroy'), $this->screenshot->only('id'));

        $response->assertNoContent();
        $this->assertSoftDeleted('time_intervals', $this->screenshot->only('id'));
    }

    public function test_unauthorized(): void
    {
        $response = $this->postJson(route('intervals.destroy'));

        $response->assertUnauthorized();
    }

    public function test_without_params(): void
    {
        $response = $this->actingAs($this->admin)->postJson(route('intervals.destroy'));

        $response->assertValidationError();
    }
}
