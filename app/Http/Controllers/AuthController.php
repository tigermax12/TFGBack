<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\User;

class AuthController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'register']]);
    }

    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);
        if ($validator->fails()) {
            return response()->json($validator-> errors(), 400);
        }
        // $request‐>validate();
        $credentials = $request->only('email', 'password');
        $token = Auth::attempt($credentials);
        if (!$token) {
            return response()->json([
                /*'status' => 'error',
                'message' => 'Unauthorized',*/
                'error' => 'Unauthorized. Either email or password is wrong.',
            ], 401);
        }
        $user = Auth::user();
        $customTTL = 1440; // 24 horas en minutos
        auth()->setTTL($customTTL);
        $token = auth()->fromUser($user); // o auth()->login($user) si es login

        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => $customTTL * 60, // Convertido a segundos
            'user' => $user,
        ]);
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'rol' => 'required|string|max:50',
            'email' => 'required|string|email|max:255|unique:users',
            'numero_trabajador' => 'required|string|max:50|unique:users',
            'password' => 'required|string|min:6',
            'c_password' => 'required|same:password',
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        try {
            $profileImagePath = null;

            if ($request->hasFile('profile_image')) {
                $profileImagePath = $request->file('profile_image')->store('profile_images', 'public');
            } else {
                // Asigna la imagen por defecto
                $profileImagePath = 'profile_images/default.jpg';
            }

            $user = User::create([
                'name' => $request->get('name'),
                'rol' => $request->get('rol'),
                'email' => $request->get('email'),
                'numero_trabajador' => $request->get('numero_trabajador'),
                'password' => Hash::make($request->get('password')),
                'profile_image' => $profileImagePath,
            ]);

            return response()->json([
                'message' => "User successfully registered",
                'user' => $user,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error during user registration',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function me()
    {
        $authUser = Auth::user();
        $user = User::find($authUser->id);

        $user->profile_image_url = $user->profile_image
            ? asset('storage/' . $user->profile_image)
            : null;

        return response()->json($user);
    }
    public function index()
    {
        $users = User::all();
        return response()->json($users);
    }
    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        Auth::logout();
        return response()->json([
        'message' => 'User successfully signed out',
    ]);
}
    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        $user = Auth::user();
        return response()->json([
        'access_token' => Auth::refresh(),
        'token_type' => 'bearer',
        'expires_in' => env('JWT_TTL') * 60,
        'user' => $user,
    ]);
}
    public function delete($id)
    {
        $authUser = Auth::user();
        $targetUser = User::find($id);

        if (!$targetUser) {
            return response()->json(['error' => 'Usuario no encontrado'], 404);
        }

        $roleHierarchy = [
            'supervisor' => ['encargado', 'operario'],
            'encargado' => ['operario'],
            'operario' => [],
        ];

        if (!in_array($targetUser->rol, $roleHierarchy[$authUser->rol] ?? [])) {
            return response()->json(['error' => 'No tienes permisos para eliminar este usuario'], 403);
        }

        $targetUser->delete();

        return response()->json(['message' => 'Usuario eliminado exitosamente']);
    }
    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['error' => 'La contraseña actual es incorrecta.'], 401);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json(['message' => 'Contraseña actualizada correctamente.']);
    }

}

