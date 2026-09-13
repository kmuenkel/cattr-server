<?php

namespace Tests\Feature\Screenshots;

use App\Models\Screenshot;
use App\Models\TimeInterval;
use App\Models\User;
use Tests\Facades\ScreenshotFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class CountTest extends TestCase
{
    private const SCREENSHOTS_AMOUNT = 10;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::asAdmin()->withTokens()->create();

        ScreenshotFactory::fake()->createMany(self::SCREENSHOTS_AMOUNT);
    }

    public function test_count(): void
    {
        $response = $this->actingAs($this->admin)->getJson(route('intervals.count'));

        $response->assertOk();
        $response->assertJson(['data' => ['total' => TimeInterval::count()]]);
    }

    public function test_unauthorized(): void
    {
        $response = $this->getJson(route('intervals.count'));

        $response->assertUnauthorized();
    }
}
