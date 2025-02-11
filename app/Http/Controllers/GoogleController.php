<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Google_Client;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\File;



class GoogleController extends Controller
{
    public function redirectToGoogle()
    {
        $client = new Google_Client();
        $client->setClientId(env('GOOGLE_CLIENT_ID'));
        $client->setClientSecret(env('GOOGLE_CLIENT_SECRET'));
        $client->setRedirectUri(env('GOOGLE_REDIRECT_URI'));
        $client->addScope(\Google_Service_Gmail::GMAIL_SEND);

        $authUrl = $client->createAuthUrl();
        // return redirect()->to($authUrl);
        return response()->json(['auth_url' => $authUrl]);
    }

    public function handleOAuthCallback(Request $request)
    {
        $code = $request->get('code');
        if (!$code) {
            return response()->json(['error' => 'Authorization code is missing'], 400);
        }

        $client = new Google_Client();
        $client->setClientId(env('GOOGLE_CLIENT_ID'));
        $client->setClientSecret(env('GOOGLE_CLIENT_SECRET'));
        $client->setRedirectUri(env('GOOGLE_REDIRECT_URI'));

        $accessToken = $client->fetchAccessTokenWithAuthCode($code);

        if (isset($accessToken['error'])) {
            return response()->json(['error' => $accessToken['error']], 400);
        }

        File::put(storage_path('google_tokens.json'), json_encode($accessToken));


        return response()->json(['message' => 'Authentication successful']);
    }
}
