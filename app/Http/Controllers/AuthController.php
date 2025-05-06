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
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
            }
        try {
            $user = User::create([
                'name' => $request->get('name'),
                'rol' => $request->get('rol'),
                'email' => $request->get('email'),
                'numero_trabajador' => $request->get('numero_trabajador'),
                'password' => Hash::make($request->get('password')),
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
        return response()->json(
        Auth::user(),
    );
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

}

