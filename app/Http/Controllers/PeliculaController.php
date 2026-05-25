<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Film;

class PeliculaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * Este método devuelve el listado de películas.
     * Como se está usando desde la API, la respuesta se devuelve en formato JSON.
     */
    public function index()
    {
        // Obtenemos todas las películas junto con su director asociado
        $peliculas = Film::with('director')->get();

        // Devolvemos las películas en formato JSON
        return response()->json($peliculas);
    }

    /**
     * Store a newly created resource in storage.
     *
     * Este método permite crear una nueva película desde la API.
     */
    public function store(Request $request)
    {
        // Validamos los datos recibidos en la petición
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'sinopsis' => ['nullable', 'string'],
            'release_date' => ['required', 'date'],
            'gendre' => ['required', 'string', 'max:255'],
            'duration' => ['nullable', 'integer'],
            'director_id' => ['required', 'exists:directors,id'],
        ]);

        // Creamos la película con los datos validados
        $pelicula = Film::create($validated);

        // Devolvemos la película creada con código HTTP 201
        return response()->json($pelicula, 201);
    }

    /**
     * Display the specified resource.
     *
     * Este método muestra una película concreta.
     */
    public function show(Film $pelicula)
    {
        // Devolvemos la película junto con los datos de su director
        return response()->json($pelicula->load('director'));
    }

    /**
     * Update the specified resource in storage.
     *
     * Este método permite actualizar una película existente.
     */
    public function update(Request $request, Film $pelicula)
    {
        // Validamos los datos recibidos
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'sinopsis' => ['nullable', 'string'],
            'release_date' => ['required', 'date'],
            'gendre' => ['required', 'string', 'max:255'],
            'duration' => ['nullable', 'integer'],
            'director_id' => ['required', 'exists:directors,id'],
        ]);

        // Actualizamos la película
        $pelicula->update($validated);

        // Devolvemos la película actualizada en formato JSON
        return response()->json($pelicula);
    }

    /**
     * Remove the specified resource from storage.
     *
     * Este método elimina una película.
     */
    public function destroy(Film $pelicula)
    {
        // Eliminamos la película
        $pelicula->delete();

        // Devolvemos una respuesta JSON confirmando la eliminación
        return response()->json([
            'message' => 'Película eliminada correctamente'
        ]);
    }
}