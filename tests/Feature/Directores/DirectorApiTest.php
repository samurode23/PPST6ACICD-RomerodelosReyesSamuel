<?php

use App\Models\Director;
use App\Models\Film;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

$crearTokenDirector = function (): string {
    $user = User::factory()->create([
        'email' => fake()->unique()->safeEmail(),
        'password' => Hash::make('password'),
    ]);

    return auth('api')->login($user);
};

test('listar directores requiere autenticacion', function () {
    $response = $this->getJson('/api/directores');

    $response->assertStatus(401);
});

test('listar directores autenticado devuelve coleccion', function () use ($crearTokenDirector) {
    Director::forceCreate([
        'name' => 'Christopher',
        'surname' => 'Nolan',
        'birthdate' => '1970-07-30',
    ]);

    Director::forceCreate([
        'name' => 'Quentin',
        'surname' => 'Tarantino',
        'birthdate' => '1963-03-27',
    ]);

    $token = $crearTokenDirector();

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/directores');

    $response->assertStatus(200)
        ->assertJsonFragment([
            'name' => 'Christopher',
            'surname' => 'Nolan',
        ])
        ->assertJsonFragment([
            'name' => 'Quentin',
            'surname' => 'Tarantino',
        ]);
});

test('crear director con datos validos', function () use ($crearTokenDirector) {
    $token = $crearTokenDirector();

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/directores', [
            'name' => 'Steven',
            'surname' => 'Spielberg',
            'birthdate' => '1946-12-18',
        ]);

    $response->assertStatus(201)
        ->assertJsonFragment([
            'name' => 'Steven',
            'surname' => 'Spielberg',
        ]);

    $this->assertDatabaseHas('directors', [
        'name' => 'Steven',
        'surname' => 'Spielberg',
    ]);
});

test('crear director con datos invalidos devuelve 422', function () use ($crearTokenDirector) {
    $token = $crearTokenDirector();

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/directores', [
            'name' => '',
            'surname' => '',
            'birthdate' => 'fecha-no-valida',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name', 'surname', 'birthdate']);
});

test('actualizar director existente', function () use ($crearTokenDirector) {
    $director = Director::forceCreate([
        'name' => 'Nombre Antiguo',
        'surname' => 'Apellido Antiguo',
        'birthdate' => '1970-01-01',
    ]);

    $token = $crearTokenDirector();

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->putJson('/api/directores/'.$director->id, [
            'name' => 'Nombre Nuevo',
            'surname' => 'Apellido Nuevo',
            'birthdate' => '1980-05-10',
        ]);

    $response->assertStatus(200)
        ->assertJsonFragment([
            'name' => 'Nombre Nuevo',
            'surname' => 'Apellido Nuevo',
        ]);

    $this->assertDatabaseHas('directors', [
        'id' => $director->id,
        'name' => 'Nombre Nuevo',
        'surname' => 'Apellido Nuevo',
        'birthdate' => '1980-05-10',
    ]);
});

test('actualizar director inexistente devuelve 404', function () use ($crearTokenDirector) {
    $token = $crearTokenDirector();

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->putJson('/api/directores/999999', [
            'name' => 'Director',
            'surname' => 'Inexistente',
            'birthdate' => '1980-01-01',
        ]);

    $response->assertStatus(404)
        ->assertJsonMissing(['exception', 'file', 'line', 'trace']);
});

test('eliminar director existente', function () use ($crearTokenDirector) {
    $director = Director::forceCreate([
        'name' => 'Director',
        'surname' => 'Eliminar',
        'birthdate' => '1975-01-01',
    ]);

    $token = $crearTokenDirector();

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->deleteJson('/api/directores/'.$director->id);

    expect($response->status())->toBeIn([200, 204]);

    $this->assertDatabaseMissing('directors', [
        'id' => $director->id,
    ]);
});

test('eliminar director con peliculas asociadas devuelve 409', function () use ($crearTokenDirector) {
    $director = Director::forceCreate([
        'name' => 'Director',
        'surname' => 'Con Peliculas',
        'birthdate' => '1970-01-01',
    ]);

    Film::forceCreate([
        'title' => 'Pelicula Asociada',
        'sinopsis' => 'Sinopsis de prueba',
        'release_date' => '2020-01-01',
        'gendre' => 'Drama',
        'duration' => 120,
        'director_id' => $director->id,
    ]);

    $token = $crearTokenDirector();

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->deleteJson('/api/directores/'.$director->id);

    $response->assertStatus(409)
        ->assertJsonFragment([
            'message' => 'No se puede eliminar el director porque tiene películas asociadas',
        ]);

    $this->assertDatabaseHas('directors', [
        'id' => $director->id,
    ]);
});
