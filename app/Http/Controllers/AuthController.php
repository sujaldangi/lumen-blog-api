<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    // Register a new user and generate JWT token
    public function register(Request $request)
    {
        try {
            // Validate input data
            $this->validate($request, [
                'name' => 'required|string',
                'email' => 'required|email|unique:users',
                'password' => 'required|min:6',
            ]);

            // Create user and generate token
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            $token = JWTAuth::fromUser($user);

            return response()->json([
                'token' => $token,
                'user' => $user,
            ], 201);
        } catch (\Exception $e) {
            return $this->sendResponse([], 'Error occurred: ' . $e->getMessage());
        }
    }

    // Login user and generate JWT token
    public function login(Request $request)
    {
        try {
            // Validate input data
            $this->validate($request, [
                'email' => 'required|email',
                'password' => 'required',
            ]);

            // Attempt to authenticate and generate token
            if (!$token = JWTAuth::attempt($request->only(['email', 'password']))) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            // Return token and user info
            return response()->json([
                'token' => $token,
                'user' => auth()->user(),
            ]);
        } catch (\Exception $e) {
            return $this->sendResponse([], 'Error occurred: ' . $e->getMessage());
        }
    }

    // Get authenticated user info
    public function me()
    {
        return response()->json(Auth::user());
    }

    // Logout user and invalidate token
    public function logout()
    {
        try {
            Auth::logout();

            return response()->json(['message' => 'Successfully logged out']);
        } catch (\Exception $e) {
            return $this->sendResponse([], 'Error occurred: ' . $e->getMessage());
        }
    }
}
