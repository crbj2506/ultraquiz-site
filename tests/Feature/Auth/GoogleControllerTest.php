<?php

namespace Tests\Feature\Auth;

use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

class GoogleControllerTest extends TestCase
{
    public function test_google_callback_failure_redirects_without_exposing_exception(): void
    {
        $provider = Mockery::mock(Provider::class);
        Socialite::shouldReceive('driver')
            ->once()
            ->with('google')
            ->andReturn($provider);
        $provider->shouldReceive('user')
            ->once()
            ->andThrow(new \RuntimeException('sensitive provider details'));

        $this->get('/auth/google/callback')
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors([
                'email' => 'Não foi possível concluir o login pelo Google. Tente novamente.',
            ])
            ->assertDontSee('sensitive provider details');
    }
}
