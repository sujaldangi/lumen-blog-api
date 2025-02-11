<?php

namespace App\Services;

use Kreait\Firebase\Factory;
use Kreait\Firebase\ServiceAccount;

class FirebaseNotificationService
{
    protected $firebase;

    public function __construct()
    {
        $firebaseCredentials = env('FIREBASE_CREDENTIALS_PATH', null);

        if (!$firebaseCredentials || !file_exists($firebaseCredentials)) {
            throw new \Exception("Firebase credentials file not found at: " . $firebaseCredentials);
        }
    
        $this->firebase = (new Factory)->withServiceAccount($firebaseCredentials);
       
    }

    public function sendNotification($token, $title, $body)
    {
        $messaging = $this->firebase->createMessaging();

        $message = [
            'token' => $token,
            'notification' => [
                'title' => $title,
                'body' => $body,
            ],
        ];

        try {
            $messaging->send($message);
            return response()->json(['message' => 'Notification sent successfully!']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
