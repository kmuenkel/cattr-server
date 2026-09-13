<?php

namespace Tests\Feature\Screenshots;

use App\Models\Screenshot;
use App\Models\TimeInterval;
use App\Models\User;
use Tests\Facades\ScreenshotFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class ShowTest extends TestCase
{
    private User $admin;
    private TimeInterval $screenshot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::asAdmin()->withTokens()->create();

        $this->screenshot = ScreenshotFactory::fake()->create();
    }

    public function test_show(): void
    {
        $screenshot = $this->screenshot->toArray();
        unset($screenshot['has_screenshot']);
        $this->assertDatabaseHas('time_intervals', $screenshot);

        $response = $this->actingAs($this->admin)->postJson(route('intervals.show'), $this->screenshot->only('id'));
        $response->assertOk();
    }

    public function test_unauthorized(): void
    {
        $response = $this->getJson(route('intervals.show'));

        $response->assertUnauthorized();
    }

    public function test_without_params(): void
    {
        $response = $this->actingAs($this->admin)->getJson(route('intervals.show'));

        $response->assertValidationError();
    }
}
