<?php

namespace Tests\Feature\TimeIntervals;

use App\Models\TimeInterval;
use App\Models\User;
use Tests\Facades\IntervalFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class ListTest extends TestCase
{
    private const INTERVALS_AMOUNT = 10;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::asAdmin()->withTokens()->create();

        IntervalFactory::createMany(self::INTERVALS_AMOUNT);
    }

    public function test_list(): void
    {
        $response = $this->actingAs($this->admin)->getJson(route('intervals.list'));

        $response->assertOk();
        $response->assertJson(['data' => TimeInterval::all()->toArray()]);
    }

    public function test_unauthorized(): void
    {
        $response = $this->getJson(route('intervals.list'));

        $response->assertUnauthorized();
    }
}
