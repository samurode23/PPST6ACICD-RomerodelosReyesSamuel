<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

$base64UrlEncode = function (string $data): string {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
};

$crearTokenJwtExpirado = function (User $user) use ($base64UrlEncode): string {
    $header = [
        'typ' => 'JWT',
        'alg' => 'HS256',
    ];

    $now = time();

    $payload = [
        'iss' => 'http://localhost/api/auth/login',
        'iat' => $now - 3600,
        'exp' => $now - 1800,
        'nbf' => $now - 3600,
        'jti' => bin2hex(random_bytes(16)),
        'sub' => (string) $user->getKey(),
        'prv' => sha1(User::class),
    ];

    $segments = [
        $base64UrlEncode(json_encode($header)),
        $base64UrlEncode(json_encode($payload)),
    ];

    $signingInput = implode('.', $segments);

    $secret = config('jwt.secret');

    $signature = hash_hmac('sha256', $signingInput, $secret, true);

    $segments[] = $base64UrlEncode($signature);

    return implode('.', $segments);
};

test('token expirado devuelve 401', function () use ($crearTokenJwtExpirado) {
    $user = User::factory()->create([
        'email' => 'usuario@example.com',
        'password' => Hash::make('password'),
    ]);

    $token = $crearTokenJwtExpirado($user);

    $this->withExceptionHandling();

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/auth/me');

    $response->assertStatus(401)
        ->assertJsonMissing(['exception', 'file', 'line', 'trace']);
});

test('respuestas de error no exponen stack trace', function () {
    config([
        'app.env' => 'production',
        'app.debug' => false,
    ]);

    $response = $this->withHeader('Authorization', 'Bearer token_inventado')
        ->getJson('/api/directores');

    $response->assertStatus(401)
        ->assertJsonMissing(['exception', 'file', 'line', 'trace']);
});

test('password no aparece en respuesta me', function () {
    $user = User::factory()->create([
        'email' => 'usuario@example.com',
        'password' => Hash::make('password'),
    ]);

    $token = auth('api')->login($user);

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/auth/me');

    $response->assertStatus(200)
        ->assertJsonFragment([
            'email' => 'usuario@example.com',
        ])
        ->assertJsonMissingPath('password');
});
