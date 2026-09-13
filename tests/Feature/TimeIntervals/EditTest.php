<?php


namespace Tests\Feature\TimeIntervals;

use App\Models\TimeInterval;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Tests\Facades\IntervalFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class EditTest extends TestCase
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
    /** @var TimeInterval $timeIntervalForManager */
    private TimeInterval $timeIntervalForManager;
    /** @var TimeInterval $timeIntervalForAuditor */
    private TimeInterval $timeIntervalForAuditor;
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
        $this->timeIntervalForManager = IntervalFactory::forUser($this->manager)->create();
        $this->timeIntervalForAuditor = IntervalFactory::forUser($this->auditor)->create();
        $this->timeIntervalForUser = IntervalFactory::forUser($this->user)->create();

        Event::fake();
    }

    public function test_edit_as_admin(): void
    {
        $timeInterval = $this->timeInterval->toArray();
        unset($timeInterval['has_screenshot']);
        $this->assertDatabaseHas('time_intervals', $timeInterval);

        $editedInterval = clone $this->timeInterval;
        $editedInterval->user_id = UserFactory::refresh()->asUser()->create()->id;

        $response = $this->actingAs($this->admin)->postJson(route('intervals.edit'), $editedInterval->toArray());

        $response->assertOk();

        $editedIntervalData = $editedInterval->toArray();
        unset($editedIntervalData['has_screenshot']);
        $this->assertDatabaseHas('time_intervals', $editedIntervalData);
    }

    public function test_edit_as_manager(): void
    {
        $timeInterval = $this->timeInterval->toArray();
        unset($timeInterval['has_screenshot']);
        $this->assertDatabaseHas('time_intervals', $timeInterval);

        $editedInterval = clone $this->timeInterval;
        $editedInterval->user_id = UserFactory::refresh()->asUser()->create()->id;

        $response = $this->actingAs($this->manager)->postJson(route('intervals.edit'), $editedInterval->toArray());
        $response->assertForbidden();
    }

    public function test_edit_your_own_as_manager(): void
    {
        $timeInterval = $this->timeInterval->toArray();
        unset($timeInterval['has_screenshot']);
        $this->assertDatabaseHas('time_intervals', $timeInterval);

        $editedInterval = clone $this->timeIntervalForManager;
        $editedInterval->user_id = UserFactory::refresh()->asUser()->create()->id;

        $response = $this->actingAs($this->manager)->postJson(route('intervals.edit'), $editedInterval->toArray());

        $response->assertOk();
        $editedIntervalData = $editedInterval->toArray();
        unset($editedIntervalData['has_screenshot']);
        $this->assertDatabaseHas('time_intervals', $editedIntervalData);
    }

    public function test_edit_as_auditor(): void
    {
        $timeInterval = $this->timeInterval->toArray();
        unset($timeInterval['has_screenshot']);
        $this->assertDatabaseHas('time_intervals', $timeInterval);

        $editedInterval = clone $this->timeInterval;
        $editedInterval->user_id = UserFactory::refresh()->asUser()->create()->id;

        $response = $this->actingAs($this->auditor)->postJson(route('intervals.edit'), $editedInterval->toArray());

        $response->assertForbidden();
    }

    public function test_edit_your_own_as_auditor(): void
    {
        $timeInterval = $this->timeInterval->toArray();
        unset($timeInterval['has_screenshot']);
        $this->assertDatabaseHas('time_intervals', $timeInterval);

        $editedInterval = clone $this->timeIntervalForAuditor;
        $editedInterval->user_id = UserFactory::refresh()->asUser()->create()->id;

        $response = $this->actingAs($this->auditor)->postJson(route('intervals.edit'), $editedInterval->toArray());

        $response->assertOk();
        $editedIntervalData = $editedInterval->toArray();
        unset($editedIntervalData['has_screenshot']);
        $this->assertDatabaseHas('time_intervals', $editedIntervalData);
    }

    public function test_edit_as_user(): void
    {
        $timeInterval = $this->timeInterval->toArray();
        unset($timeInterval['has_screenshot']);
        $this->assertDatabaseHas('time_intervals', $timeInterval);

        $editedInterval = clone $this->timeInterval;
        $editedInterval->user_id = UserFactory::refresh()->asUser()->create()->id;

        $response = $this->actingAs($this->user)->postJson(route('intervals.edit'), $editedInterval->toArray());

        $response->assertForbidden();
    }

    public function test_edit_your_own_as_user(): void
    {
        $timeInterval = $this->timeInterval->toArray();
        unset($timeInterval['has_screenshot']);
        $this->assertDatabaseHas('time_intervals', $timeInterval);

        $editedInterval = clone $this->timeIntervalForUser;
        $editedInterval->user_id = UserFactory::refresh()->asUser()->create()->id;

        $response = $this
            ->actingAs($this->user)
            ->postJson(route('intervals.edit'), $editedInterval->toArray());

        $response->assertOk();
        $editedIntervalData = $editedInterval->toArray();
        unset($editedIntervalData['has_screenshot']);
        $this->assertDatabaseHas('time_intervals', $editedIntervalData);
    }

    public function test_unauthorized(): void
    {
        $response = $this->postJson(route('intervals.edit'));

        $response->assertUnauthorized();
    }

    public function test_without_params(): void
    {
        $response = $this->actingAs($this->admin)->postJson(route('intervals.edit'));

        $response->assertValidationError();
    }
}
