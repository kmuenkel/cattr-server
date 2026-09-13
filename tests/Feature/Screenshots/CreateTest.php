<?php

namespace Tests\Feature\Screenshots;

use App\Enums\ScreenshotsState;
use App\Models\TimeInterval;
use App\Models\User;
use Faker\Factory;
use Illuminate\Http\Testing\File;
use Illuminate\Http\UploadedFile;
use Storage;
use Tests\Facades\IntervalFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class CreateTest extends TestCase
{
    private User $admin;
    private File $screenshotFile;
    private TimeInterval $interval;


    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::asAdmin()->withTokens()->create();

        Storage::fake();

        $screenshotName = Factory::create()->firstName . '.jpg';
        $this->screenshotFile = UploadedFile::fake()->image($screenshotName);

        $this->interval = IntervalFactory::create();
    }

    public function test_create(): void
    {
        $requestData = ['time_interval_id' => $this->interval->id, 'screenshot' => $this->screenshotFile];

        $response = $this->actingAs($this->admin)->postJson(
            route('intervals.screenshot.put', ['interval' => $this->interval->id]),
            $requestData
        );

        $response->assertNoContent();
//        $this->assertDatabaseHas('screenshots', $response->json('screenshot'));
//        Storage::assertExists('uploads/screenshots/' . basename($response->json('screenshot.path')));
    }

    public function test_unauthorized(): void
    {
        $response = $this->postJson(route('intervals.screenshot.put', ['interval' => $this->interval->id]));

        $response->assertUnauthorized();
    }

    public function test_without_params(): void
    {
        $response = $this->actingAs($this->admin)->postJson(route('intervals.screenshot.put', ['interval' => $this->interval->id]));

        $response->assertValidationError();
    }
}
