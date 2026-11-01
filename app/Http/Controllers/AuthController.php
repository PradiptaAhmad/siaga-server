<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if ($user === null || ! Hash::check($credentials['password'], $user->password)) {
            return $this->errorResponse(
                message: 'The provided credentials are incorrect.',
                code: 401,
            );
        }

        Auth::guard('api')->setUser($user);

        $randomToken = Str::random(46);
        $userToken   = $user->createToken($randomToken)->plainTextToken;

        $cookie = cookie(
            name: config('sanctum.token_prefix').'token',
            value: $userToken,
            minutes: 60 * 24 * 7, // 7 days   // Isi '.domain.com' jika satu root domain
            secure: false,      // Wajib true untuk SameSite=None
            httpOnly: true,    // Mencegah akses dari document.cookie
            raw: false,
            sameSite: 'lax'   // Wajib 'none'
        );

        return $this->successResponse(
            data: $user->toArray(),
            responseMessage: 'Successfully logged in',

        )->withCookie($cookie);
    }

    public function user(Request $request): JsonResponse
    {
        return $this->successResponse(
            data: $request->user(),
            responseMessage: 'Successfully retrieved user data',
        );
    }

    public function logout(Request $request): JsonResponse
    {
        $user = Auth::guard('api')->user();

        if ($user instanceof User) {
            $user->tokens()->delete();
        }

        return $this->successResponse(
            responseMessage: 'Successfully logged out',
        );
    }
}
