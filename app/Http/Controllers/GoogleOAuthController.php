<?php

use Google_Client;
use Google_Service_Gmail;

class GoogleOAuthController extends Controller
{
    public function authorize()
    {
        $client = new Google_Client();
        $client->setAuthConfig(storage_path('app/credentials.json'));
        $client->addScope(Google_Service_Gmail::GMAIL_SEND);
        $client->setAccessType('offline');

        // Redirect to the OAuth URL
        if ($client->getAccessToken()) {
            file_put_contents(storage_path('app/token.json'), json_encode($client->getAccessToken()));
            return 'Token stored successfully';
        } else {
            return redirect($client->createAuthUrl());
        }
    }

    public function handleRedirect(Request $request)
    {
        $client = new Google_Client();
        $client->setAuthConfig(storage_path('app/credentials.json'));
        $client->addScope(Google_Service_Gmail::GMAIL_SEND);
        $client->setAccessType('offline');

        $authCode = $request->input('code');
        $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);

        // Store token
        file_put_contents(storage_path('app/token.json'), json_encode($accessToken));

        return 'Authorization complete';
    }
}

