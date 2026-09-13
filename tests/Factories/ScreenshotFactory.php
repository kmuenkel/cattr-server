<?php

namespace Tests\Factories;

use App\Models\TimeInterval;
use App\Models\User;
use Faker\Factory as FakerFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Storage;
use Tests\Facades\IntervalFactory;

class ScreenshotFactory extends Factory
{
    private ?TimeInterval $interval = null;

    private bool $fakeStorage = false;

    public function fake(): self
    {
        $this->fakeStorage = true;
        return $this;
    }

    protected function getModelInstance(): Model
    {
        return $this->interval;
    }

    public function createRandomModelData(): array
    {
        return $this->generateScreenshotData();
    }

    private function generateScreenshotData(): array
    {
        $name = FakerFactory::create()->unique()->firstName . '.jpg';
        $image = UploadedFile::fake()->image($name);

        $path = Storage::put('uploads/screenshots', $image);
        $thumbnail = Storage::put('uploads/screenshots', UploadedFile::fake()->image($name));

        return compact('path', 'thumbnail');
    }

    public function create(array $attributes = []): TimeInterval
    {
        if ($this->fakeStorage) {
            Storage::fake();
        }

        $modelData = $this->createRandomModelData();
        $this->interval = TimeInterval::make($modelData);
        $this->interval->user_id = $this->interval->user_id ?? User::inRandomOrder()->first()->id;

        $this->defineInterval();
        $this->interval::withoutEvents(fn () => $this->interval->save());

        if ($this->timestampsHidden) {
            $this->hideTimestamps();
        }

        return $this->interval;
    }

    public function withRandomRelations(): self
    {
        $this->randomRelations = true;
        return $this;
    }

    public function forInterval(TimeInterval $interval): self
    {
        $this->interval = $interval;
        return $this;
    }

    private function defineInterval(): void
    {
        if ($this->randomRelations || !$this->interval) {
            $this->interval = IntervalFactory::create();
        }
    }
}
