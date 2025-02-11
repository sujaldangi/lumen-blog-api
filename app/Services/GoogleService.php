<?php
namespace App\Services;

use Google_Client;
use Google_Service_Gmail;
use Google_Service_Gmail_Message;

class GoogleService
{
    protected $client;
    protected $gmailService;

    public function __construct()
    {
        $this->client = new Google_Client();
        $this->client->setClientId(env('GOOGLE_CLIENT_ID'));
        $this->client->setClientSecret(env('GOOGLE_CLIENT_SECRET'));
        $this->client->setRedirectUri(env('GOOGLE_REDIRECT_URI'));
        $this->client->addScope(Google_Service_Gmail::GMAIL_SEND);

        // If you already have a refresh token saved or obtained
        // $this->client->setAccessToken($refreshToken);

        
        
        // Refresh the token if necessary
        if ($this->client->isAccessTokenExpired()) {
            // Handle refresh token or OAuth flow to get a new access token
        }

        $this->gmailService = new Google_Service_Gmail($this->client);
    }

    public function sendEmail($to, $subject, $messageText)
    {
        $message = new \Google_Service_Gmail_Message();
        $rawMessage = $this->createMessage($to, $subject, $messageText);
        $message->setRaw($rawMessage);
        return $this->gmailService->users_messages->send('me', $message);
    }

    private function createMessage($to, $subject, $messageText)
    {
        $message = "To: " . $to . "\r\n";
        $message .= "Subject: " . $subject . "\r\n";
        $message .= "\r\n" . $messageText;
        return base64_encode($message);
    }
}
