<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('login con credenciales validas devuelve token', function () {
    User::factory()->create([
        'email' => 'usuario@example.com',
        'password' => Hash::make('password'),
    ]);

    $response = $this->postJson('/api/auth/login', [
        'email' => 'usuario@example.com',
        'password' => 'password',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'access_token',
            'token_type',
            'expires_in',
        ]);
});

test('login con credenciales invalidas devuelve 401', function () {
    User::factory()->create([
        'email' => 'usuario@example.com',
        'password' => Hash::make('password'),
    ]);

    $response = $this->postJson('/api/auth/login', [
        'email' => 'usuario@example.com',
        'password' => 'incorrecta',
    ]);

    $response->assertStatus(401)
        ->assertJsonMissing(['exception', 'file', 'line', 'trace']);
});

test('login con campos faltantes devuelve 422', function () {
    $response = $this->postJson('/api/auth/login', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email', 'password']);
});

test('logout invalida el token', function () {
    User::factory()->create([
        'email' => 'usuario@example.com',
        'password' => Hash::make('password'),
    ]);

    $login = $this->postJson('/api/auth/login', [
        'email' => 'usuario@example.com',
        'password' => 'password',
    ]);

    $token = $login->json('access_token');

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/auth/logout')
        ->assertStatus(200);

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/auth/me')
        ->assertStatus(401);
});

test('refresh devuelve nuevo token valido', function () {
    User::factory()->create([
        'email' => 'usuario@example.com',
        'password' => Hash::make('password'),
    ]);

    $login = $this->postJson('/api/auth/login', [
        'email' => 'usuario@example.com',
        'password' => 'password',
    ]);

    $token = $login->json('access_token');

    $refresh = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/auth/refresh');

    $refresh->assertStatus(200)
        ->assertJsonStructure([
            'access_token',
            'token_type',
            'expires_in',
        ]);

    $newToken = $refresh->json('access_token');

    $this->withHeader('Authorization', 'Bearer '.$newToken)
        ->getJson('/api/auth/me')
        ->assertStatus(200);
});

test('me devuelve datos del usuario autenticado', function () {
    $user = User::factory()->create([
        'email' => 'usuario@example.com',
        'password' => Hash::make('password'),
    ]);

    $login = $this->postJson('/api/auth/login', [
        'email' => 'usuario@example.com',
        'password' => 'password',
    ]);

    $token = $login->json('access_token');

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/auth/me');

    $response->assertStatus(200)
        ->assertJsonFragment([
            'email' => $user->email,
        ])
        ->assertJsonMissingPath('password');
});

test('acceso sin token devuelve 401', function () {
    $response = $this->getJson('/api/directores');

    $response->assertStatus(401);
});

test('acceso con token malformado devuelve 401', function () {
    $response = $this->withHeader('Authorization', 'Bearer token_inventado')
        ->getJson('/api/directores');

    $response->assertStatus(401)
        ->assertJsonMissing(['exception', 'file', 'line', 'trace']);
});
