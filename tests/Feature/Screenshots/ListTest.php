<?php

namespace Tests\Feature\Screenshots;

use App\Models\TimeInterval;
use App\Models\User;
use Tests\Facades\ScreenshotFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class ListTest extends TestCase
{
    private const SCREENSHOTS_AMOUNT = 10;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::asAdmin()->withTokens()->create();

        ScreenshotFactory::fake()->withRandomRelations()->createMany(self::SCREENSHOTS_AMOUNT);
    }

    public function test_list(): void
    {
        $response = $this->actingAs($this->admin)->postJson(
            route('intervals.list'),
            headers: ['X-Paginate' => 'false'],
        );

        $response->assertOk();
        $response->assertJson(['data' =>TimeInterval::all()->toArray()]);
    }

    public function test_unauthorized(): void
    {
        $response = $this->getJson(
            route('intervals.list'),
            ['X-Paginate' => 'false'],
        );

        $response->assertUnauthorized();
    }
}
