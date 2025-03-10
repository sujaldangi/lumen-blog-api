<?php

namespace App\Services;

use Google_Client;
use Google_Service_Gmail;
use Google_Service_Gmail_Message;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class GmailService
{
    private $client;
    private $gmailService;
    private $adminEmail;

    public function __construct()
    {
        // Initialize Google Client
        $this->client = new Google_Client();
        $this->client->setAuthConfig(env('GOOGLE_APPLICATION_CREDENTIALS'));

        $this->client->addScope(Google_Service_Gmail::GMAIL_SEND);
        $this->client->setSubject(env('ADMIN_EMAIL'));

        $this->gmailService = new Google_Service_Gmail($this->client);
        $this->adminEmail = env('ADMIN_EMAIL');
    }

    // Function to send an email
    public function sendEmail($to, $subject, $body)
    {
        try {
            // Create the MIME message
            $mimeMessage = $this->createMimeMessage($to, $subject, $body);

            // Send email using Gmail API
            $message = new Google_Service_Gmail_Message();
            $message->setRaw($mimeMessage);
            $this->gmailService->users_messages->send('me', $message);

            return 'Email sent successfully';
        } catch (\Exception $e) {
            return 'Error sending email: ' . $e->getMessage();
        }
    }

    // Function to create MIME message
    private function createMimeMessage($to, $subject, $body)
    {
        $headers = [
            'From' => $this->adminEmail,
            'To' => $to,
            'Subject' => $subject,
            'Content-Type' => 'text/html; charset=UTF-8',
        ];

        // Create a raw MIME message
        $rawMessage = implode("\r\n", array_map(
            function ($key, $value) {
                return $key . ": " . $value;
            },
            array_keys($headers),
            $headers
        )) . "\r\n\r\n" . $body;

        // Encode the message in base64
        return base64_encode($rawMessage);
    }
}
