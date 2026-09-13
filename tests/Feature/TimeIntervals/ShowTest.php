<?php


namespace Tests\Feature\TimeIntervals;

use App\Models\TimeInterval;
use App\Models\User;
use BackedEnum;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Tests\Facades\IntervalFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class ShowTest extends TestCase
{
    /** @var User $admin */
    private User $admin;
    /** @var User $manager */
    private User $manager;
    /** @var User $auditor */
    private User $auditor;
    /** @var User $user */
    private User $user;

    /** @var TimeInterval $timeInterval */
    private TimeInterval $timeInterval;
    /** @var TimeInterval $timeIntervalForUser */
    private TimeInterval $timeIntervalForUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::refresh()->asAdmin()->withTokens()->create();
        $this->manager = UserFactory::refresh()->asManager()->withTokens()->create();
        $this->auditor = UserFactory::refresh()->asAuditor()->withTokens()->create();
        $this->user = UserFactory::refresh()->asUser()->withTokens()->create();

        $this->timeInterval = IntervalFactory::create();
        $this->timeIntervalForUser = IntervalFactory::forUser($this->user)->create();
    }

    public function test_show_as_admin(): void
    {
        $timeInterval = $this->timeInterval->toArray();
        unset($timeInterval['has_screenshot']);
        $this->assertDatabaseHas('time_intervals', $timeInterval);

        $response = $this->actingAs($this->admin)->postJson(route('intervals.show'), $this->timeInterval->only('id'));
        $response->assertOk();

        $timeInterval = $this->timeInterval->toArray();
        $timeInterval['start_at'] = Carbon::make($timeInterval['start_at'])->format('Y-m-d H:i:s');
        $timeInterval['end_at'] = Carbon::make($timeInterval['end_at'])->format('Y-m-d H:i:s');
        $response->assertJson(['data' => $timeInterval]);
    }

    public function test_show_as_manager(): void
    {
        $timeInterval = $this->timeInterval->toArray();
        unset($timeInterval['has_screenshot']);
        $this->assertDatabaseHas('time_intervals', $timeInterval);

        $response = $this->actingAs($this->manager)->postJson(route('intervals.show'), $this->timeInterval->only('id'));
        $response->assertOk();

        $timeInterval = $this->timeInterval->toArray();
        $timeInterval['start_at'] = Carbon::make($timeInterval['start_at'])->format('Y-m-d H:i:s');
        $timeInterval['end_at'] = Carbon::make($timeInterval['end_at'])->format('Y-m-d H:i:s');
        $response->assertJson(['data' => $timeInterval]);
    }

    public function test_show_as_auditor(): void
    {
        $timeInterval = $this->timeInterval->toArray();
        unset($timeInterval['has_screenshot']);
        $this->assertDatabaseHas('time_intervals', $timeInterval);

        $response = $this->actingAs($this->auditor)->postJson(route('intervals.show'), $this->timeInterval->only('id'));
        $response->assertOk();

        $timeInterval = $this->timeInterval->toArray();
        $timeInterval['start_at'] = Carbon::make($timeInterval['start_at'])->format('Y-m-d H:i:s');
        $timeInterval['end_at'] = Carbon::make($timeInterval['end_at'])->format('Y-m-d H:i:s');
        $response->assertJson(['data' => $timeInterval]);
    }

    public function test_show_as_user(): void
    {
        $timeInterval = $this->timeInterval->toArray();
        unset($timeInterval['has_screenshot']);
        $this->assertDatabaseHas('time_intervals', $timeInterval);

        $response = $this->actingAs($this->user)->postJson(route('intervals.show'), $this->timeInterval->only('id'));

        $response->assertOk();
    }

    public function test_show_your_own_as_user(): void
    {
        $timeInterval = $this->timeIntervalForUser->toArray();
        unset($timeInterval['has_screenshot']);
        $this->assertDatabaseHas('time_intervals', $timeInterval);

        $response = $this
            ->actingAs($this->user)
            ->postJson(route('intervals.show'), $this->timeIntervalForUser->only('id'));

        $timeInterval = $this->timeIntervalForUser->toArray();
        $timeInterval['start_at'] = Carbon::make($timeInterval['start_at'])->format('Y-m-d H:i:s');
        $timeInterval['end_at'] = Carbon::make($timeInterval['end_at'])->format('Y-m-d H:i:s');
        $response->assertJson(['data' => $timeInterval]);
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
