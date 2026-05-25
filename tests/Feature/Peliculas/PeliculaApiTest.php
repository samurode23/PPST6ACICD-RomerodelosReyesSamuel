<?php

use App\Models\Director;
use App\Models\Film;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

$crearTokenPelicula = function (): string {
    $user = User::factory()->create([
        'email' => fake()->unique()->safeEmail(),
        'password' => Hash::make('password'),
    ]);

    return auth('api')->login($user);
};

$crearDirectorPelicula = function (): Director {
    return Director::forceCreate([
        'name' => 'Christopher',
        'surname' => 'Nolan',
        'birthdate' => '1970-07-30',
    ]);
};

test('listar peliculas autenticado devuelve coleccion', function () use ($crearTokenPelicula, $crearDirectorPelicula) {
    $director = $crearDirectorPelicula();

    Film::forceCreate([
        'title' => 'Origen',
        'sinopsis' => 'Pelicula de ciencia ficcion',
        'release_date' => '2010-07-16',
        'gendre' => 'Ciencia ficcion',
        'duration' => 148,
        'director_id' => $director->id,
    ]);

    $token = $crearTokenPelicula();

    $response = $this->withHeader('Authorization', 'Bearer ' . $token)
        ->getJson('/api/peliculas');

    $response->assertStatus(200)
        ->assertJsonFragment([
            'title' => 'Origen',
            'duration' => 148,
        ]);
});

test('crear pelicula asociada a director existente', function () use ($crearTokenPelicula, $crearDirectorPelicula) {
    $director = $crearDirectorPelicula();

    $token = $crearTokenPelicula();

    $response = $this->withHeader('Authorization', 'Bearer ' . $token)
        ->postJson('/api/peliculas', [
            'title' => 'Interstellar',
            'sinopsis' => 'Viaje espacial',
            'release_date' => '2014-11-07',
            'gendre' => 'Ciencia ficcion',
            'duration' => 169,
            'director_id' => $director->id,
        ]);

    $response->assertStatus(201)
        ->assertJsonFragment([
            'title' => 'Interstellar',
            'director_id' => $director->id,
        ]);

    $this->assertDatabaseHas('films', [
        'title' => 'Interstellar',
        'release_date' => '2014-11-07',
        'gendre' => 'Ciencia ficcion',
        'director_id' => $director->id,
    ]);
});

test('crear pelicula con director inexistente devuelve 422', function () use ($crearTokenPelicula) {
    $token = $crearTokenPelicula();

    $response = $this->withHeader('Authorization', 'Bearer ' . $token)
        ->postJson('/api/peliculas', [
            'title' => 'Pelicula Sin Director',
            'sinopsis' => 'No debe guardarse',
            'release_date' => '2020-01-01',
            'gendre' => 'Drama',
            'duration' => 100,
            'director_id' => 999999,
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['director_id']);
});

test('actualizar pelicula', function () use ($crearTokenPelicula, $crearDirectorPelicula) {
    $director = $crearDirectorPelicula();

    $pelicula = Film::forceCreate([
        'title' => 'Titulo Antiguo',
        'sinopsis' => 'Sinopsis antigua',
        'release_date' => '2000-01-01',
        'gendre' => 'Drama',
        'duration' => 100,
        'director_id' => $director->id,
    ]);

    $token = $crearTokenPelicula();

    $response = $this->withHeader('Authorization', 'Bearer ' . $token)
        ->putJson('/api/peliculas/' . $pelicula->id, [
            'title' => 'Titulo Nuevo',
            'sinopsis' => 'Sinopsis nueva',
            'release_date' => '2020-01-01',
            'gendre' => 'Accion',
            'duration' => 130,
            'director_id' => $director->id,
        ]);

    $response->assertStatus(200)
        ->assertJsonFragment([
            'title' => 'Titulo Nuevo',
            'duration' => 130,
        ]);

    $this->assertDatabaseHas('films', [
        'id' => $pelicula->id,
        'title' => 'Titulo Nuevo',
        'release_date' => '2020-01-01',
        'gendre' => 'Accion',
        'duration' => 130,
        'director_id' => $director->id,
    ]);
});

test('eliminar pelicula', function () use ($crearTokenPelicula, $crearDirectorPelicula) {
    $director = $crearDirectorPelicula();

    $pelicula = Film::forceCreate([
        'title' => 'Pelicula a eliminar',
        'sinopsis' => 'Sinopsis',
        'release_date' => '2020-01-01',
        'gendre' => 'Drama',
        'duration' => 90,
        'director_id' => $director->id,
    ]);

    $token = $crearTokenPelicula();

    $response = $this->withHeader('Authorization', 'Bearer ' . $token)
        ->deleteJson('/api/peliculas/' . $pelicula->id);

    expect($response->status())->toBeIn([200, 204]);

    $this->assertDatabaseMissing('films', [
        'id' => $pelicula->id,
    ]);
});

test('mostrar pelicula incluye datos del director', function () use ($crearTokenPelicula, $crearDirectorPelicula) {
    $director = $crearDirectorPelicula();

    $pelicula = Film::forceCreate([
        'title' => 'Dunkirk',
        'sinopsis' => 'Pelicula belica',
        'release_date' => '2017-07-21',
        'gendre' => 'Belica',
        'duration' => 106,
        'director_id' => $director->id,
    ]);

    $token = $crearTokenPelicula();

    $response = $this->withHeader('Authorization', 'Bearer ' . $token)
        ->getJson('/api/peliculas/' . $pelicula->id);

    $response->assertStatus(200)
        ->assertJsonFragment([
            'title' => 'Dunkirk',
        ])
        ->assertJsonPath('director.id', $director->id)
        ->assertJsonPath('director.name', $director->name);
});