<?php

namespace App\Http\Controllers;

use App\Models\Peticione;
use Illuminate\Http\Request;
use App\Models\Categoria;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;

class PeticioneController extends Controller
{
    public function index(Request $request)
    {
        try {
            $peticiones = Peticione::all();
            return response()->json($peticiones, 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Error al obtener las peticiones'], 500);
        }
    }

    public function listMine(Request $request)
    {
        try {
            $user = Auth::user();
            $peticiones = Peticione::where('user_id', $user->id)->get();
            return response()->json($peticiones, 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Error al obtener tus peticiones'], 500);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'titulo' => 'required|max:255',
            'descripcion' => 'required',
            'destinatario' => 'required',
            'categoria_id' => 'required|exists:categorias,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 401);
        }

        try {
            //$user = Auth::user();
            $user= 1;
            $category = Categoria::findOrFail($request->categoria_id);

            $peticion = new Peticione($request->all());
            $peticion->user()->associate($user);
            $peticion->categoria()->associate($category);

            $peticion->firmantes = 0;
            $peticion->estado = 'pendiente';
            $peticion->save();

            return response()->json($peticion, 201);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Categoría no encontrada'], 404);
        } catch (Exception $e) {
            return response()->json(['error' => 'Error al crear la petición'], 500);
        }
    }

    public function show(Request $request, $id)
    {
        try {
            $peticion = Peticione::findOrFail($id);
            return response()->json($peticion, 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Petición no encontrada'], 404);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $peticion = Peticione::findOrFail($id);
            $peticion->update($request->all());
            return response()->json($peticion, 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Petición no encontrada'], 404);
        } catch (Exception $e) {
            return response()->json(['error' => 'Error al actualizar la petición'], 500);
        }
    }

    public function firmar(Request $request, $id)
    {
        try {
            $peticion = Peticione::findOrFail($id);
            $user = Auth::user();

            if ($peticion->firmas()->where('user_id', $user->id)->exists()) {
                return response()->json(['error' => 'Ya has firmado esta petición'], 400);
            }

            $peticion->firmas()->attach($user->id);
            $peticion->increment('firmantes');

            return response()->json($peticion, 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Petición no encontrada'], 404);
        } catch (Exception $e) {
            return response()->json(['error' => 'Error al firmar la petición'], 500);
        }
    }

    public function cambiarEstado(Request $request, $id)
    {
        try {
            $peticion = Peticione::findOrFail($id);
            $peticion->estado = 'aceptada';
            $peticion->save();
            return response()->json($peticion, 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Petición no encontrada'], 404);
        } catch (Exception $e) {
            return response()->json(['error' => 'Error al cambiar el estado'], 500);
        }
    }

    public function delete(Request $request, $id)
    {
        try {
            $peticion = Peticione::findOrFail($id);
            $peticion->delete();
            return response()->json(['message' => 'Petición eliminada'], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Petición no encontrada'], 404);
        } catch (Exception $e) {
            return response()->json(['error' => 'Error al eliminar la petición'], 500);
        }
    }
}
