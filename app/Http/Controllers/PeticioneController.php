<?php

namespace App\Http\Controllers;

use App\Models\Peticione;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Categoria;
use Illuminate\Support\Facades\Validator;

class PeticioneController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $peticiones = Peticione::all();
        return $peticiones;
    }

    public function listMine(Request $request)
    {
        //parent::index()
        //$user= Auth::user();
        $id = 1;
        $peticiones = Peticione::all()->where('user_id', $id);
        return $peticiones;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'titulo' => 'required|max:255',
            'descripcion' => 'required',
            'destinatario' => 'required',
            'categoria_id' => 'required',
        // 'file' => 'required',
        ]);
        $input = $request->all();
        $category = Categoria::query()->findOrFail($request->input('categoria_id'));
        $user = 1; //harcodeamos el usuario
        //$user = Auth::user(); //asociarlo al usuario authenticado
        $peticion = new Peticione($input);
        $peticion->user()->associate($user);
        $peticion->categoria()->associate($category);
        $peticion->firmantes = 0;
        $peticion->estado = 'pendiente';
        $peticion->save();
        return $peticion;
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, $id)
    {
        $peticion = Peticione::query()->findOrFail($id);
        return $peticion;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $peticion = Peticione::query()->findOrFail($id);
        $peticion->update($request->all());
        return $peticion;
    }

    public function firmar(Request $request, $id)
    {
        $peticion = Peticione::query()->findOrFail($id);
        //$user = Auth::user();
        $user = 1;
        $user_id = [$user];
        //$user_id = [$user‐>id];
        $peticion->firmas()->attach($user_id);
        $peticion->firmantes = $peticion->firmantes + 1;
        $peticion->save();
        return $peticion;
    }
    public function cambiarEstado(Request $request, $id)
    {
        $peticion = Peticione::query()->findOrFail($id);
        $peticion->estado = 'aceptada';
        $peticion->save();
        return $peticion;
    }
    public function delete(Request $request, $id)
    {
        $peticion = Peticione::query()->findOrFail($id);
        $peticion->delete();
        return $peticion;
    }
}
