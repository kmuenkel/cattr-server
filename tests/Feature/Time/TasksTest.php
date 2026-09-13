<?php

namespace Tests\Feature\Time;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Tests\Facades\IntervalFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class TasksTest extends TestCase
{
    private const URI = 'time/tasks';

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

        $response = $this->actingAs($this->admin)->postJson(route('time.tasks'), $requestData);
        $response->assertOk();

        //TODO CHECK RESPONSE CONTENT
    }

    public function test_unauthorized(): void
    {
        $response = $this->getJson(route('time.tasks'));

        $response->assertUnauthorized();
    }

    public function test_wrong_params(): void
    {
        $response = $this->actingAs($this->admin)->postJson(route('time.tasks'), ['task_id' => 'wrong']);

        $response->assertValidationError();
    }
}
