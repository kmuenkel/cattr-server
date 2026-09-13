<?php

namespace Tests\Feature\ProjectReport;

use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Tests\Facades\IntervalFactory;
use Tests\Facades\TaskFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class TaskTest extends TestCase
{
    private User $admin;

    private Collection $intervals;

    private int $duration = 0;
    private array $requestData;

    private Task $task;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::asAdmin()->withTokens()->create();
        $this->task = TaskFactory::forUser($this->admin)->create();

        $this->intervals = collect([
            IntervalFactory::forTask($this->task)->create(),
            IntervalFactory::forTask($this->task)->create(),
            IntervalFactory::forTask($this->task)->create(),
        ]);

        $this->requestData = [
            'projects' => [$this->task->project->id],
            'start_at' => Carbon::parse($this->intervals->min('start_at')),
            'end_at' => Carbon::parse($this->intervals->max('end_at'))->addMinute(),
            'uid' => $this->intervals->first()->user->id
        ];

        $this->duration = $this->intervals->sum(
            fn ($interval) => Carbon::parse($interval->end_at)->diffInSeconds($interval->start_at)
        );
    }

    public function test_list_task(): void
    {
        $response = $this->actingAs($this->admin)->postJson(route('report.project'), $this->requestData);
        $duration = collect($response->json()['data'])
            ->pluck('users')->flatten(1)
            ->pluck('tasks')->flatten(1)
            ->pluck('intervals')->flatten(1)
            ->pluck('items')->flatten(1)
            ->map(fn (array $item) => collect($item)->sum('duration'))->sum();

        $response->assertOk();
        $this->assertEquals($this->duration, $duration);
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
