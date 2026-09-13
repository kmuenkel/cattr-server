<?php


namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class LogoutFromAllTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = UserFactory::withTokens(4)->create();
    }

    public function test_logout_from_all(): void
    {
        $tokens = cache("testing:{$this->user->id}:tokens");

        $this->assertNotEmpty($tokens);

        foreach ($tokens as $token) {
            $this->actingAs($token['token'])->get(route('auth.me'))->assertOk();
        }

        $response = $this->actingAs($tokens[0]['token'])->postJson(route('auth.logout_all'));
        app('auth')->forgetGuards();
        $response->assertNoContent();

        foreach ($tokens as $token) {
            $this->actingAs($token['token'])->get(route('auth.me'))->assertUnauthorized();
        }
    }

    public function test_unauthorized(): void
    {
        $response = $this->postJson(route('auth.logout_all'));

        $response->assertUnauthorized();
    }
}
