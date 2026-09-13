<?php


namespace Tests\Feature\TimeIntervals;

use App\Models\TimeInterval;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Tests\Facades\IntervalFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class RemoveTest extends TestCase
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

        Event::fake();

        $this->admin = UserFactory::refresh()->asAdmin()->withTokens()->create();
        $this->manager = UserFactory::refresh()->asManager()->withTokens()->create();
        $this->auditor = UserFactory::refresh()->asAuditor()->withTokens()->create();
        $this->user = UserFactory::refresh()->asUser()->withTokens()->create();

        $this->timeInterval = IntervalFactory::create();
        $this->timeIntervalForManager = IntervalFactory::forUser($this->manager)->create();
        $this->timeIntervalForAuditor = IntervalFactory::forUser($this->auditor)->create();
        $this->timeIntervalForUser = IntervalFactory::forUser($this->user)->create();
    }

    public function test_remove_as_admin(): void
    {
        $timeInterval = $this->timeInterval->toArray();
        unset($timeInterval['has_screenshot']);
        $this->assertDatabaseHas('time_intervals', $timeInterval);

        $response = $this->actingAs($this->admin)->postJson(
            route('intervals.destroy'),
            $this->timeInterval->only('id')
        );

        $response->assertNoContent();
        $this->assertSoftDeleted('time_intervals', ['id' => $this->timeInterval->id]);
    }

    public function test_remove_as_manager(): void
    {
        $timeInterval = $this->timeInterval->toArray();
        unset($timeInterval['has_screenshot']);
        $this->assertDatabaseHas('time_intervals', $timeInterval);

        $response = $this->actingAs($this->manager)->postJson(
            route('intervals.destroy'),
            $this->timeInterval->only('id')
        );

        $response->assertConflict();
    }

    public function test_remove_your_own_as_manager(): void
    {
        $timeInterval = $this->timeInterval->toArray();
        unset($timeInterval['has_screenshot']);
        $this->assertDatabaseHas('time_intervals', $timeInterval);

        $response = $this
            ->actingAs($this->manager)
            ->postJson(route('intervals.destroy'), $this->timeIntervalForManager->only('id'));

        $response->assertNoContent();
        $this->assertSoftDeleted('time_intervals', ['id' => $this->timeIntervalForManager->id]);
    }

    public function test_remove_as_auditor(): void
    {
        $timeInterval = $this->timeInterval->toArray();
        unset($timeInterval['has_screenshot']);
        $this->assertDatabaseHas('time_intervals', $timeInterval);

        $response = $this->actingAs($this->auditor)->postJson(
            route('intervals.destroy'),
            $this->timeInterval->only('id')
        );

        $response->assertConflict();
    }

    public function test_remove_your_own_as_auditor(): void
    {
        $timeInterval = $this->timeInterval->toArray();
        unset($timeInterval['has_screenshot']);
        $this->assertDatabaseHas('time_intervals', $timeInterval);

        $response = $this
            ->actingAs($this->auditor)
            ->postJson(route('intervals.destroy'), $this->timeIntervalForAuditor->only('id'));

        $response->assertNoContent();
        $this->assertSoftDeleted('time_intervals', ['id' => $this->timeIntervalForAuditor->id]);
    }

    public function test_remove_as_user(): void
    {
        $timeInterval = $this->timeInterval->toArray();
        unset($timeInterval['has_screenshot']);
        $this->assertDatabaseHas('time_intervals', $timeInterval);

        $response = $this->actingAs($this->user)->postJson(route('intervals.destroy'), $this->timeInterval->only('id'));

        $response->assertConflict();
    }

    public function test_remove_your_own_as_user(): void
    {
        $timeInterval = $this->timeInterval->toArray();
        unset($timeInterval['has_screenshot']);
        $this->assertDatabaseHas('time_intervals', $timeInterval);

        $response = $this
            ->actingAs($this->user)
            ->postJson(route('intervals.destroy'), $this->timeIntervalForUser->only('id'));

        $response->assertNoContent();
        $this->assertSoftDeleted('time_intervals', ['id' => $this->timeIntervalForUser->id]);
    }

    public function test_unauthorized(): void
    {
        $response = $this->postJson(route('intervals.destroy'));

        $response->assertUnauthorized();
    }

    public function test_without_params(): void
    {
        $response = $this->actingAs($this->admin)->postJson(route('intervals.destroy'));

        $response->assertValidationError();
    }
}
