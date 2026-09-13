<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Cache;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class LoginTest extends TestCase
{
    private User $user;

    private array $loginData;

    private const CAPTCHA_CACHE_KEY = 'AUTH_RECAPTCHA_LIMITER_{ip}_{email}_ATTEMPTS';
    private const BAN_CACHE_KEY = 'AUTH_RATE_LIMITER_{ip}';

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = UserFactory::create();

        $this->loginData = [
            'email' => $this->user->email,
            'password' => $this->user->full_name
        ];
    }

    public function test_success(): void
    {
        $response = $this->postJson(route('auth.login'), $this->loginData);
        $response->assertOk();

        $this->actingAs($response->decodeResponseJson()['data']['access_token'])->get(route('auth.me'))->assertOk();
    }

    public function test_wrong_credentials(): void
    {
        $this->loginData['password'] = 'wrong_password';
        $response = $this->postJson(route('auth.login'), $this->loginData);

        $response->assertUnauthorized();
    }

    public function test_disabled_user(): void
    {
        $this->user->active = false;
        $this->user->save();
        $response = $this->postJson(route('auth.login'), $this->loginData);

        $response->assertForbidden('authorization.user_disabled', false);
    }

    public function test_soft_deleted_user(): void
    {
        $this->user->delete();
        $response = $this->postJson(route('auth.login'), $this->loginData);

        $response->assertUnauthorized();
    }

    public function test_without_params(): void
    {
        $response = $this->postJson(route('auth.login'));

        $response->assertError(self::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function test_recaptcha(): void
    {
        config(['recaptcha.enabled' => true]);
        config(['recaptcha.failed_attempts' => 1]);

        $cacheKey = str_replace(
            ['{email}', '{ip}'],
            [$this->loginData['email'], '127.0.0.1'],
            self::CAPTCHA_CACHE_KEY
        );

        $this->assertFalse(Cache::store('octane')->has($cacheKey));

        $this->loginData['password'] = 'wrong_password';
        $this->postJson(route('auth.login'), $this->loginData);

        $this->assertTrue(Cache::store('octane')->has($cacheKey));

        $this->assertEquals(1, Cache::store('octane')->get($cacheKey));

        $response = $this->postJson(route('auth.login'), $this->loginData);

        $response->assertError(self::HTTP_TOO_MANY_REQUESTS, 'authorization.captcha');
    }

    public function test_ban(): void
    {
        config(['recaptcha.enabled' => true]);
        config(['recaptcha.rate_limiter_enabled' => true]);
        config(['recaptcha.failed_attempts' => 0]);
        config(['recaptcha.ban_attempts' => 1]);

        $cacheKey = str_replace('{ip}', '127.0.0.1', self::BAN_CACHE_KEY);

        $this->assertFalse(Cache::store('octane')->has($cacheKey));

        $this->loginData['password'] = 'wrong_password';
        $this->postJson(route('auth.login'), $this->loginData);

        $this->assertTrue(Cache::store('octane')->has($cacheKey));

        $cacheResponse = Cache::store('octane')->get($cacheKey);

        $this->assertArrayHasKey('amounts', $cacheResponse);
        $this->assertArrayHasKey('time', $cacheResponse);

        $this->assertEquals(1, $cacheResponse['amounts']);

        $response = $this->postJson(route('auth.login'), $this->loginData);

        $response->assertError(self::HTTP_LOCKED, 'authorization.banned');
    }
}
