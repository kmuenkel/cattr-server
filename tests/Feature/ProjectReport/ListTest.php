<?php

namespace Tests\Feature\ProjectReport;

use App\Models\TimeInterval;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Tests\Facades\IntervalFactory;
use Tests\Facades\ScreenshotFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;
use Tests\TestResponse;

class ListTest extends TestCase
{
    private const INTERVALS_AMOUNT = 10;

    private User $admin;

    private array $pids;
    private array $uids;

    private Collection $intervals;

    private int $duration = 0;
    private array $requestData;

    private function collectResponseUsers(TestResponse $response): Collection
    {
        return collect($response->json()['data'])->pluck('users')->flatten(1);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::asAdmin()->withTokens()->create();

        $this->intervals = IntervalFactory::withRandomRelations()->createMany(self::INTERVALS_AMOUNT);

        $this->intervals->each(function (TimeInterval $interval) {
            ScreenshotFactory::fake()->forInterval($interval)->create();
            $this->uids[] = $interval->user_id;
            $this->pids[] = $interval->task->project->id;
            $this->duration += Carbon::parse($interval->end_at)->diffInSeconds($interval->start_at);
        });

        $this->requestData = [
            'start_at' => $this->intervals->min('start_at'),
            'end_at' => Carbon::parse($this->intervals->max('end_at'))->addMinute()->format('c'),
            'uids' => $this->uids,
            'pids' => $this->pids
        ];
    }

    public function test_list(): void
    {
        $response = $this->actingAs($this->admin)->postJson(route('report.project'), $this->requestData);

        $response->assertOk();

        $users = $this->collectResponseUsers($response);

//        $this->assertEquals($this->duration, $users->sum('tasks_time'));
        $this->assertEquals($this->duration, collect($response->json()['data'])
            ->pluck('users')->flatten(1)
            ->pluck('tasks')->flatten(1)
            ->pluck('intervals')->flatten(1)
            ->pluck('items')->flatten(1)
            ->map(fn (array $item) => collect($item)->sum('duration'))->sum());
        $this->assertEquals(count($this->uids), $users->count());

        //TODO change later
    }

    public function test_unauthorized(): void
    {
        $response = $this->postJson(route('report.project'));

        $response->assertUnauthorized();
    }

    public function test_without_params(): void
    {
        $response = $this->actingAs($this->admin)->postJson(route('report.project'));

        $response->assertValidationError();
    }
}
