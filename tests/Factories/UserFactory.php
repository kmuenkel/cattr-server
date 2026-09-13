<?php

namespace Tests\Factories;

use App\Enums\Role;
use App\Enums\ScreenshotsState;
use App\Models\User;
use Carbon\Carbon;
use Faker\Factory as FakerFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;

class UserFactory extends Factory
{
    public const USER_ROLE = 2;
    public const MANAGER_ROLE = 1;
    public const AUDITOR_ROLE = 3;

    private int $tokensAmount = 0;
    private ?int $roleId = null;

    private User $user;
    private bool $isAdmin = false;


    protected function getModelInstance(): Model
    {
        return $this->user;
    }

    public function create(): User
    {
        $modelData = $this->createRandomModelData();

        if ($this->isAdmin) {
            $modelData['is_admin'] = true;
        }
        $this->user = User::firstOrCreate(['email' => $modelData['email']], $modelData);

        if ($this->tokensAmount) {
            $this->createTokens();

            $tokens = cache("testing:{$this->user->id}:tokens");

            array_map(fn (array $token) => $this->user->tokens()->create([
                'name' => Str::uuid(),
                'token' => hash('sha256', $token['token']),
                'abilities' => ['*'],
                'expires_at' => $token['expires_at'],
            ]), $tokens);
        }

        if ($this->roleId) {
            $this->assignRole();
        }

        $this->user->save();

        if ($this->timestampsHidden) {
            $this->hideTimestamps();
        }

        return $this->user;
    }


    public function createRandomModelData(): array
    {
        $faker = FakerFactory::create();

        $fullName = $faker->name;

        return [
            'full_name' => $fullName,
            'email' => $faker->unique()->safeEmail,
            'url' => '',
            'company_id' => 1,
            'avatar' => '',
            'screenshots_state' => ScreenshotsState::REQUIRED->value,
            'manual_time' => 0,
            'computer_time_popup' => 300,
            'blur_screenshots' => 0,
            'web_and_app_monitoring' => 1,
            'screenshots_interval' => 5,
            'active' => 1,
            'password' => $fullName,
            'user_language' => 'en',
            'role_id' => $this->roleId ?? Role::USER->value,
            'type' => 'employee',
            'nonce' => 0,
            'last_activity' => Carbon::now()->subMinutes(rand(1, 55)),
        ];
    }

    public function createRandomRegistrationModelData(): array
    {
        $faker = FakerFactory::create();

        return [
            'full_name' => $faker->name,
            'email' => $faker->unique()->safeEmail,
            'active' => 1,
            'password' => $faker->password,
            'screenshots_interval' => 5,
            'user_language' => 'en',
            'screenshots_state' => ScreenshotsState::REQUIRED->value,
            'computer_time_popup' => 10,
            'timezone' => 'UTC',
            'role_id' => 2,
            'type' => 'employee'
        ];
    }

    public function withTokens(int $quantity = 1): self
    {
        $this->tokensAmount = $quantity;
        return $this;
    }

    public function asAdmin(): self
    {
        $this->roleId = Role::ADMIN->value;
        $this->isAdmin = true;
        return $this;
    }

    public function asManager(): self
    {
        $this->roleId = Role::MANAGER->value;
        return $this;
    }

    public function asAuditor(): self
    {
        $this->roleId = Role::AUDITOR->value;
        return $this;
    }

    public function asUser(): self
    {
        $this->roleId = Role::USER->value;
        return $this;
    }

    protected function createTokens(): void
    {
        $tokens = array_map(fn() => [
            'token' => JWTAuth::fromUser($this->user),
            'expires_at' => now()->addDay()
        ], range(0, $this->tokensAmount));

        cache(["testing:{$this->user->id}:tokens" => $tokens]);
    }

    protected function assignRole(): void
    {
        $this->user->role_id = $this->roleId;
    }
}
