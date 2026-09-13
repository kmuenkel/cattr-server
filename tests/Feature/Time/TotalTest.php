<?php

namespace Tests\Feature\Time;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Tests\Facades\IntervalFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class TotalTest extends TestCase
{
    private const INTERVALS_AMOUNT = 10;

    private Collection $intervals;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::asAdmin()->withTokens()->create();

        $this->intervals = IntervalFactory::forUser($this->admin)->createMany(self::INTERVALS_AMOUNT);
    }

    public function test_total(): void
    {
        $requestData = [
            'start_at' => $this->intervals->min('start_at'),
            'end_at' => Carbon::parse($this->intervals->max('end_at'))->addMinute()->format('c'),
            'user_id' => $this->admin->id
        ];

        $response = $this->actingAs($this->admin)->postJson(route('time.total'), $requestData);
        $response->assertOk();

        $totalTime = $this->intervals->sum(static function ($interval) {
            return Carbon::parse($interval->end_at)->diffInSeconds($interval->start_at);
        });

        $response->assertJson(['data' => ['time' => $totalTime]]);
        $response->assertJson(['data' => ['start' => Carbon::make($this->intervals->min('start_at'))->format('Y-m-d H:i:s')]]);
        $response->assertJson(['data' => ['end' => Carbon::make($this->intervals->max('end_at'))->format('Y-m-d H:i:s')]]);
    }

    public function test_unauthorized(): void
    {
        $response = $this->getJson(route('time.total'));

        $response->assertUnauthorized();
    }

    public function test_without_params(): void
    {
        $response = $this->actingAs($this->admin)->getJson(route('time.total'));

        $response->assertValidationError();
    }
}
