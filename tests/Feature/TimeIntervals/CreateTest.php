<?php

namespace Tests\Feature\TimeIntervals;

use App\Models\Task;
use App\Models\TimeInterval;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Tests\Facades\IntervalFactory;
use Tests\Facades\TaskFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class CreateTest extends TestCase
{
    private User $admin;
    private Task $task;

    private array $intervalData;

    protected function setUp(): void
    {
        parent::setUp();

        Event::fake();

        $this->admin = UserFactory::asAdmin()->withTokens()->create();

        $this->task = TaskFactory::forUser($this->admin)->create();

        $this->intervalData = IntervalFactory::createRandomModelDataWithRelation();
        $this->intervalData['user_id'] = $this->admin->id;

        Event::fake();
    }

    public function test_create(): void
    {
        $this->assertDatabaseMissing('time_intervals', $this->intervalData);

        $response = $this->actingAs($this->admin)->postJson(route('intervals.create'), $this->intervalData);

        $response->assertOk();
        $this->assertDatabaseHas('time_intervals', $this->intervalData);
    }

    public function test_already_exists_left(): void
    {
        $this->intervalData['start_at'] = '1900-01-01 01:00:00';
        $this->intervalData['end_at'] = '1900-01-01 01:05:00';

        TimeInterval::create($this->intervalData);
        $this->assertDatabaseHas('time_intervals', $this->intervalData);

        $newInterval = $this->intervalData;
        $newInterval['start_at'] = '1900-01-01 00:55:00';
        $newInterval['end_at'] = '1900-01-01 01:05:00';

        $response = $this->actingAs($this->admin)->postJson(route('intervals.create'), $newInterval);

        $response->assertValidationError();
    }

    public function test_already_exists_right(): void
    {
        $this->intervalData['start_at'] = '1900-01-01 01:00:00';
        $this->intervalData['end_at'] = '1900-01-01 01:05:00';

        TimeInterval::create($this->intervalData);
        $this->assertDatabaseHas('time_intervals', $this->intervalData);

        $newInterval = $this->intervalData;
        $newInterval['start_at'] = '1900-01-01 01:00:00';
        $newInterval['end_at'] = '1900-01-01 01:10:00';

        $response = $this->actingAs($this->admin)->postJson(route('intervals.create'), $newInterval);

        $response->assertValidationError();
    }

    public function test_already_exists_left_inner(): void
    {
        $this->intervalData['start_at'] = '1900-01-01 01:00:00';
        $this->intervalData['end_at'] = '1900-01-01 01:05:00';

        TimeInterval::create($this->intervalData);
        $this->assertDatabaseHas('time_intervals', $this->intervalData);

        $newInterval = $this->intervalData;
        $newInterval['start_at'] = '1900-01-01 00:55:00';
        $newInterval['end_at'] = '1900-01-01 01:03:00';

        $response = $this->actingAs($this->admin)->postJson(route('intervals.create'), $newInterval);

        $response->assertValidationError();
    }

    public function test_already_exists_right_inner(): void
    {
        $this->intervalData['start_at'] = '1900-01-01 01:00:00';
        $this->intervalData['end_at'] = '1900-01-01 01:05:00';

        TimeInterval::create($this->intervalData);
        $this->assertDatabaseHas('time_intervals', $this->intervalData);

        $newInterval = $this->intervalData;
        $newInterval['start_at'] = '1900-01-01 01:03:00';
        $newInterval['end_at'] = '1900-01-01 01:10:00';

        $response = $this->actingAs($this->admin)->postJson(route('intervals.create'), $newInterval);

        $response->assertValidationError();
    }

    public function test_already_exists_inner(): void
    {
        $this->intervalData['start_at'] = '1900-01-01 01:00:00';
        $this->intervalData['end_at'] = '1900-01-01 01:05:00';

        TimeInterval::create($this->intervalData);
        $this->assertDatabaseHas('time_intervals', $this->intervalData);

        $newInterval = $this->intervalData;
        $newInterval['start_at'] = '1900-01-01 01:01:00';
        $newInterval['end_at'] = '1900-01-01 01:03:00';

        $response = $this->actingAs($this->admin)->postJson(route('intervals.create'), $newInterval);

        $response->assertValidationError();
    }

    public function test_already_exists_outer(): void
    {
        $this->intervalData['start_at'] = '1900-01-01 01:00:00';
        $this->intervalData['end_at'] = '1900-01-01 01:05:00';

        TimeInterval::create($this->intervalData);
        $this->assertDatabaseHas('time_intervals', $this->intervalData);

        $newInterval = $this->intervalData;
        $newInterval['start_at'] = '1900-01-01 00:55:00';
        $newInterval['end_at'] = '1900-01-01 01:10:00';

        $response = $this->actingAs($this->admin)->postJson(route('intervals.create'), $newInterval);

        $response->assertValidationError();
    }

    public function test_already_exists(): void
    {
        TimeInterval::create($this->intervalData);
        $this->assertDatabaseHas('time_intervals', $this->intervalData);

        $response = $this->actingAs($this->admin)->postJson(route('intervals.create'), $this->intervalData);

        $response->assertValidationError();
    }

    public function test_unauthorized(): void
    {
        $response = $this->postJson(route('intervals.create'));

        $response->assertUnauthorized();
    }

    public function test_without_params(): void
    {
        $response = $this->actingAs($this->admin)->postJson(route('intervals.create'));

        $response->assertValidationError();
    }
}
