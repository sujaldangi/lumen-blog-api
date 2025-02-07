<?php

namespace App\Http\Controllers;

use Google_Client;
use Google_Service_Gmail;
use Google_Service_Gmail_Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class GmailController extends Controller
{
    // Step 1: Create Google Client
    private function createGoogleClient()
    {
        $client = new Google_Client();
        $client->setClientId(env('GOOGLE_CLIENT_ID'));
        $client->setClientSecret(env('GOOGLE_CLIENT_SECRET'));
        $client->setRedirectUri(env('GOOGLE_REDIRECT_URI'));
        $client->addScope(Google_Service_Gmail::GMAIL_SEND);
        return $client;
    }

    // Step 2: Redirect to Google OAuth consent screen
    public function redirectToGoogle()
    {
        $client = $this->createGoogleClient();
        $authUrl = $client->createAuthUrl();
        return redirect()->to($authUrl);
    }

    // Step 3: Handle Google OAuth callback
    public function handleGoogleCallback(Request $request)
    {
        $client = $this->createGoogleClient();
        $client->authenticate($request->get('code'));

        // Save the access token in the session
        Session::put('google_access_token', $client->getAccessToken());

        return redirect('/api/gmail/send');
    }

    // Step 4: Send email using Gmail API
    public function sendEmail()
    {
        if (!Session::has('google_access_token')) {
            return redirect('/api/gmail/redirect');
        }

        $client = $this->createGoogleClient();
        $client->setAccessToken(Session::get('google_access_token'));

        if ($client->isAccessTokenExpired()) {
            return redirect('/api/gmail/redirect');
        }

        $service = new Google_Service_Gmail($client);

        // Create the email
        $message = new \Swift_Message();
        $message->setSubject('Test Email from Lumen API');
        $message->setFrom(['sahibdangi1@gmail.com']);
        $message->setTo(['sujaldangi29@gmail.com']);
        $message->setBody('This is a test email.');

        $rawMessage = $this->createMessage($message);

        // Send the email
        $message = new Google_Service_Gmail_Message();
        $message->setRaw($rawMessage);
        $service->users_messages->send('me', $message);

        return response()->json(['message' => 'Email sent successfully!']);
    }

    // Helper method to create a raw email message
    private function createMessage($message)
    {
        $strRawMessage = "To: " . implode(", ", $message->getTo()) . "\r\n";
        $strRawMessage .= "From: " . $message->getFrom() . "\r\n";
        $strRawMessage .= "Subject: " . $message->getSubject() . "\r\n";
        $strRawMessage .= "MIME-Version: 1.0\r\n";
        $strRawMessage .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $strRawMessage .= "\r\n" . $message->getBody();
        return base64_encode($strRawMessage);
    }
}
