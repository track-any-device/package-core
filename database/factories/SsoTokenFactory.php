<?php

namespace TrackAnyDevice\Core\Database\Factories;

use TrackAnyDevice\SsoServer\Models\OAuthClient;
use TrackAnyDevice\Core\Models\SsoToken;
use TrackAnyDevice\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SsoToken>
 *
 * The factory generates an unused token by default. Use the consumed() /
 * expired() states for the other lifecycle phases. Tests that need to
 * exercise verifier behaviour should call `withPlainToken('abc')` so they
 * have a known plain value to hash against.
 */
class SsoTokenFactory extends Factory
{
    protected $model = SsoToken::class;

    public function definition(): array
    {
        return [
            'token_hash' => hash('sha256', 'sso_'.Str::random(40)),
            'user_id' => User::factory(),
            'oauth_client_id' => OAuthClient::factory(),
            'state' => null,
            'issued_to_ip' => $this->faker->ipv4(),
            'consumed_from_ip' => null,
            'issued_at' => now(),
            'expires_at' => now()->addMinutes(5),
            'consumed_at' => null,
        ];
    }

    public function withPlainToken(string $plain): static
    {
        return $this->state(fn () => [
            'token_hash' => hash('sha256', $plain),
        ]);
    }

    public function consumed(): static
    {
        return $this->state(fn () => [
            'consumed_at' => now(),
            'consumed_from_ip' => $this->faker->ipv4(),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'issued_at' => now()->subMinutes(20),
            'expires_at' => now()->subMinutes(15),
        ]);
    }
}
