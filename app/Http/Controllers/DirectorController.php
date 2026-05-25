<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Director;

class DirectorController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * Este método se utiliza tanto para la parte web como para la API.
     * Si la petición viene desde /api, se devuelve una respuesta JSON.
     * Si la petición viene desde la parte web, se devuelve la vista Blade.
     */
    public function index()
    {
        // Obtenemos todos los directores de la base de datos
        $directores = Director::all();

        // Si la petición procede de una ruta API, devolvemos JSON
        if (request()->is('api/*')) {
            return response()->json($directores);
        }

        // Inicializamos la variable para evitar el error:
        // Undefined variable $tableData
        $tableData = [];

        // Recorremos los directores para construir los datos de la tabla web
        foreach ($directores as $director) {
            $tableData[$director->id] = [
                $director->name,
                $director->surname,
                $director->birthdate
            ];
        }

        // Convertimos los datos a colección para usarlos en la vista
        $tableData = collect($tableData);

        // Cabecera de la tabla que se mostrará en la vista web
        $header = collect(['Nombre', 'Apellido', 'Fecha nacimiento']);

        // Si no es una petición API, devolvemos la vista web
        return view('director.index', compact('tableData', 'header'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * Este método pertenece a la parte web.
     * Devuelve la vista con el formulario de creación de directores.
     */
    public function create()
    {
        // Devolvemos la vista de creación del formulario
        return view('director.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * Este método permite crear un nuevo director.
     * Si la petición viene desde la API, devuelve una respuesta JSON.
     */
    public function store(Request $request)
    {
        // Validamos los datos recibidos
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'surname' => ['required', 'string', 'max:255'],
            'birthdate' => ['nullable', 'date'],
        ]);

        // Creamos un nuevo director
        $director = new Director();
        $director->name = $validated['name'];
        $director->surname = $validated['surname'];
        $director->birthdate = $validated['birthdate'] ?? null;
        $director->save();

        // Si la petición procede de la API, devolvemos JSON con código 201
        if (request()->is('api/*')) {
            return response()->json($director, 201);
        }

        // Si la petición procede de la web, redirigimos al listado de directores
        return redirect()->route('directors.index');
    }

    /**
     * Display the specified resource.
     *
     * Muestra un director concreto.
     * En API devuelve JSON.
     * En web devuelve una vista Blade con los datos del director y sus películas.
     */
    public function show(Director $director)
    {
        // Si la petición procede de una ruta API, devolvemos el director en JSON
        // junto con sus películas asociadas
        if (request()->is('api/*')) {
            return response()->json($director->load('films'));
        }

        // Cabecera de la tabla de películas para la vista web
        $headerPeliculas = collect(['Title', 'Sinopsis', 'Duration']);

        // Obtenemos las películas asociadas al director
        $films = $director->films;

        // Inicializamos la variable para evitar errores si no hay películas
        $tableData = [];

        // Recorremos las películas para preparar los datos de la tabla
        foreach ($films as $film) {
            $tableData[$film->id] = [
                $film->title,
                $film->sinopsis,
                $film->duration
            ];
        }

        // Convertimos los datos a colección
        $tableData = collect($tableData);

        // Devolvemos la vista web del director
        return view('director.show', compact('director', 'headerPeliculas', 'tableData'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * Este método queda reservado para la parte web.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * Este método permite actualizar un director existente.
     * En API devuelve la respuesta en formato JSON.
     */
    public function update(Request $request, Director $director)
    {
        // Validamos los datos recibidos
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'surname' => ['required', 'string', 'max:255'],
            'birthdate' => ['nullable', 'date'],
        ]);

        // Actualizamos los datos del director
        $director->name = $validated['name'];
        $director->surname = $validated['surname'];
        $director->birthdate = $validated['birthdate'] ?? null;
        $director->save();

        // Si la petición procede de la API, devolvemos el director actualizado
        if (request()->is('api/*')) {
            return response()->json($director);
        }

        // Si la petición procede de la web, redirigimos al listado
        return redirect()->route('directors.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * Este método elimina un director.
     * En API devuelve una respuesta JSON.
     */
    public function destroy(Director $director)
    {
        if ($director->films()->exists()) {
            if (request()->is('api/*')) {
                return response()->json([
                    'message' => 'No se puede eliminar el director porque tiene películas asociadas'
                ], 409);
            }

            return redirect()->route('directors.index');
        }

        $director->delete();

        if (request()->is('api/*')) {
            return response()->json([
                'message' => 'Director eliminado correctamente'
            ]);
        }

        return redirect()->route('directors.index');
    }
}