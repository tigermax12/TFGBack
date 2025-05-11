<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\OrdenDeTrabajo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Limpieza;
use App\Models\Trasiego;
use App\Models\Clarificacione;
use App\Models\User;;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class OrdenDeTrabajoController extends Controller
{
    public function index() {
        $ordenes = OrdenDeTrabajo::with(['limpieza.usuario', 'trasiego.usuario', 'clarificacion.usuario'])->get();

        $ordenesTransformadas = $ordenes->map(function ($orden) {
            $nombreOperario = null;

            switch ($orden->tipo_de_orden) {
                case 'limpieza':
                    $nombreOperario = optional($orden->limpieza?->usuario)->name;
                    break;
                case 'trasiego':
                    $nombreOperario = optional($orden->trasiego?->usuario)->name;
                    break;
                case 'clarificacion':
                    $nombreOperario = optional($orden->clarificacion?->usuario)->name;
                    break;
            }

            return [
                'id_orden' => $orden->id_orden,
                'tipo_de_orden' => $orden->tipo_de_orden,
                'prioridad' => $orden->prioridad,
                'estado' => $orden->estado,
                'nombre_operario' => $nombreOperario,
            ];
        });


        return response()->json($ordenesTransformadas);
    }


    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            $request->validate([
                'tipoDeOrden' => 'required|string|in:limpieza,trasiego,clarificacion',
                'prioridad' => 'required|integer',
                'estado' => 'required|string',
                'idUserCreador' => 'required|integer',
                'fecha_de_realizacion' => 'required|date|after_or_equal:today'
            ]);

            $orden = OrdenDeTrabajo::create([
                'Tipo_de_Orden' => $request->tipoDeOrden,
                'Prioridad' => $request->prioridad,
                'Estado' => $request->estado,
                'Id_user_creador' => $request->idUserCreador,
                'Fecha_de_realizacion' => $request->fecha_de_realizacion,
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
    public function autoasignar(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'password' => 'required|string',
            'orden_id' => 'required|exists:orden_de_trabajo,id_orden',
        ], [
            'user_id.required' => 'El usuario es obligatorio.',
            'user_id.exists' => 'El usuario no existe.',
            'password.required' => 'La contraseña es obligatoria.',
            'orden_id.required' => 'La orden es obligatoria.',
            'orden_id.exists' => 'La orden no existe.',
        ]);

        try {
            $user = User::findOrFail($request->user_id);

            // Validación de contraseña
            if (!Hash::check($request->password, $user->password)) {
                return response()->json([
                    'error' => 'Credenciales inválidas'
                ], 401);
            }

            $orden = OrdenDeTrabajo::findOrFail($request->orden_id);

            // Verificamos que no esté ya asignada
            if (strtolower($orden->estado) !== 'pendiente') {
                return response()->json([
                    'error' => 'La orden ya está asignada o en proceso'
                ], 400);
            }

            // Actualizamos el estado de la orden
            $orden->estado = 'asignado';
            $orden->save();

            // Asignamos el operario en la tabla específica según el tipo de orden
            $tipo = strtolower(trim($orden->tipo_de_orden));

            switch ($tipo) {
                case 'clarificacion':
                    if ($orden->clarificacion) {
                        $orden->clarificacion->operario = $user->id;
                        $orden->clarificacion->save();
                    }
                    break;

                case 'limpieza':
                    if ($orden->limpieza) {
                        $orden->limpieza->operario = $user->id;
                        $orden->limpieza->save();
                    }
                    break;

                case 'trasiego':
                    if ($orden->trasiego) {
                        $orden->trasiego->operario = $user->id;
                        $orden->trasiego->save();
                    }
                    break;

                default:
                    return response()->json([
                        'error' => 'Tipo de orden no soportado'
                    ], 400);
            }

            return response()->json([
                'message' => 'Orden asignada correctamente',
                'orden' => $orden->id_orden,
                'usuario' => $user->name . ' - ' . $user->numero_trabajador
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error inesperado al asignar la orden',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    public function actualizarOrdenTipo(Request $request, $id)
    {
        try {
            $tipo = strtolower($request->tipo_de_orden);

            if ($tipo === 'clarificacion') {
                $orden = Clarificacione::where('id_orden', $id)->firstOrFail();
                $orden->update([
                    'producto' => $request->producto,
                    'deposito' => $request->deposito,
                    'coadyuvantes_extra' => $request->coadyuvantes_extra,
                    'observaciones' => $request->observaciones,
                ]);
            } elseif ($tipo === 'limpieza') {
                $orden = Limpieza::where('id_orden', $id)->firstOrFail();
                $orden->update([
                    'deposito' => $request->deposito,
                    'tipo_de_limpieza' => $request->tipo_de_limpieza,
                    'observaciones' => $request->observaciones,
                ]);
            } elseif ($tipo === 'trasiego') {
                $orden = Trasiego::where('id_orden', $id)->firstOrFail();
                $orden->update([
                    'producto' => $request->producto,
                    'deposito_origen' => $request->deposito_origen,
                    'deposito_destino' => $request->deposito_destino,
                    'cantidad_a_trasegar' => $request->cantidad_a_trasegar,
                    'tipo_de_limpieza' => $request->tipo_de_limpieza,
                    'observaciones' => $request->observaciones,
                ]);
            } else {
                return response()->json(['error' => 'Tipo de orden no reconocido'], 400);
            }

            return response()->json(['message' => 'Orden actualizada correctamente']);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al actualizar la orden',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    public function finalizarOrden(Request $request, $id)
    {
        try {
            $orden = OrdenDeTrabajo::findOrFail($id);

            if (strtolower($orden->estado) !== 'asignado') {
                return response()->json([
                    'error' => 'Solo se pueden finalizar órdenes en estado asignado.'
                ], 400);
            }

            $orden->estado = 'finalizado';
            $orden->Fecha_de_modificacion = now();
            $orden->save();

            return response()->json([
                'message' => 'Orden finalizada correctamente',
                'orden_id' => $orden->id_orden
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al finalizar la orden',
                'message' => $e->getMessage()
            ], 500);
        }
    }



}
