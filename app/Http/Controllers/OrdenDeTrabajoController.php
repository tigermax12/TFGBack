<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\OrdenDeTrabajo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Limpieza;
use App\Models\Trasiego;
use App\Models\Clarificacione;
class OrdenDeTrabajoController extends Controller
{
    public function index() {
        $ordenes = OrdenDeTrabajo::all();
        return response()->json($ordenes);
    }
    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            $request->validate([
                'tipoDeOrden' => 'required|string|in:limpieza,trasiego,clarificacion',
                'prioridad' => 'required|string',
                'estado' => 'required|string',
                'idUserCreador' => 'required|integer',
            ]);

            $orden = OrdenDeTrabajo::create([
                'Tipo_de_Orden' => $request->tipoDeOrden,
                'Prioridad' => $request->prioridad,
                'Estado' => $request->estado,
                'Id_user_creador' => $request->idUserCreador,
                'Fecha_de_creacion' => now(),
                'Fecha_de_modificacion' => now(),
            ]);

            $tipo = $request->tipoDeOrden;
            $campos = $request->camposTipoOrden ?? [];

            switch ($tipo) {
                case 'limpieza':
                    Limpieza::create([
                        'id_orden' => $orden->id_orden,
                        'deposito' => $campos['deposito'] ?? null,
                        'tipo_de_limpieza' => $campos['tipo_de_limpieza'] ?? null,
                        'observaciones' => $campos['observaciones'] ?? null,
                    ]);
                    break;

                case 'trasiego':
                    Trasiego::create([
                        'id_orden' => $orden->id_orden,
                        'producto' => $campos['producto'] ?? null,
                        'deposito_origen' => $campos['deposito_origen'] ?? null,
                        'deposito_destino' => $campos['deposito_destino'] ?? null,
                        'cantidad_a_trasegar' => $campos['cantidad_a_trasegar'] ?? null,
                        'tipo_de_limpieza' => $campos['tipo_de_limpieza'] ?? null,
                        'observaciones' => $campos['observaciones'] ?? null,
                    ]);
                    break;

                case 'clarificacion':
                    Clarificacione::create([
                        'id_orden' => $orden->id_orden,
                        'producto' => $campos['producto'] ?? null,
                        'deposito' => $campos['deposito'] ?? null,
                        'coadyuvantes_extra' => $campos['coadyuvantes_extra'] ?? null,
                        'observaciones' => $campos['observaciones'] ?? null,
                    ]);
                    break;
            }

            DB::commit();
            return response()->json([
                'message' => 'Orden de trabajo creada correctamente',
                'orden' => $orden
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Error al crear la orden: ' . $e->getMessage()
            ], 500);
        }
    }
    public function obtenerOrdenCompleta($id)
    {
        $orden = OrdenDeTrabajo::with([
            'limpieza',
            'trasiego',
            'clarificacion'
        ])->findOrFail($id);

        return response()->json([
            'orden' => $orden
        ]);
    }

    public function asignarOrden(Request $request, $id)
    {
        $request->validate([
            'firma' => 'required|string',
        ]);

        $orden = OrdenDeTrabajo::findOrFail($id);


        switch ($orden->Tipo_de_Orden) {
            case 'limpieza':
                $registro = Limpieza::where('id_orden', $id)->firstOrFail();
                $registro->operario = auth()->id();
                $registro->firma = $request->firma;
                $registro->save();
                break;

            case 'trasiego':
                $registro = Trasiego::where('id_orden', $id)->firstOrFail();
                $registro->operario = auth()->id();
                $registro->firma = $request->firma;
                $registro->save();
                break;

            case 'clarificacion':
                $registro = Clarificacione::where('id_orden', $id)->firstOrFail();
                $registro->operario = auth()->id();
                $registro->firma = $request->firma;
                $registro->save();
                break;
        }

        return response()->json(['message' => 'Orden asignada correctamente']);
    }

}
