<?php

namespace App\Http\Controllers;

use Laravel\Socialite\Facades\Socialite;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    // Redirect the user to Google for authentication
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    // Handle the callback from Google
    public function handleGoogleCallback(Request $request)
    {
        try {
            // Get the user's information from Google
            $googleUser = Socialite::driver('google')->user();

           
            // $googleUser->getId();
            // $googleUser->getName();
            // $googleUser->getEmail();
            if (!$googleUser) {
                return response()->json(['error' => 'Unable to retrieve Google user data'], 400);
            }
            // dd($googleUser);

            
            auth()->login($user);

            return response()->json(['user' => $user]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to authenticate with Google'], 400);
        }
    }
}
