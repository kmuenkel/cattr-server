<?php

namespace Tests\Feature\TimeIntervals;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Tests\Facades\IntervalFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    private const INTERVALS_AMOUNT = 2;

    private Collection $intervals;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::asAdmin()->withTokens()->create();

        $this->intervals = IntervalFactory::forUser($this->admin)->createMany(self::INTERVALS_AMOUNT);
    }

    public function test_dashboard(): void
    {
        $requestData = [
            'start_at' => Carbon::parse($this->intervals->min('start_at')),
            'end_at' => Carbon::parse($this->intervals->max('start_at'))->addHour(),
            'user_ids' => [$this->admin->id],
            'user_timezone' => 'Asia/Omsk',
        ];

        $response = $this->actingAs($this->admin)->postJson(route('report.dashboard'), $requestData);

        $response->assertOk();

        $this->assertCount(
            $this->intervals->count(),
            $response->json('data')[$this->admin->id]
        );
    }

    public function test_unauthorized(): void
    {
        auth()->logout();
        $response = $this->postJson(route('report.dashboard'));

        $response->assertUnauthorized();
    }

    public function test_without_params(): void
    {
        $response = $this->actingAs($this->admin)->postJson(route('report.dashboard'));

        $response->assertValidationError();
    }
}
