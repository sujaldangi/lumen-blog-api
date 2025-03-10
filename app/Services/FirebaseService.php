<?php

namespace App\Services;

use Kreait\Firebase\Factory;
use Carbon\Carbon;


class FirebaseService
{
    protected $database;
    protected $auth;

    public function __construct()
    {
        // Initialize Firebase
        $factory = (new Factory)
            ->withServiceAccount(env('FIREBASE_CREDENTIALS_PATH')) 
            ->withDatabaseUri(env('FIREBASE_DATABASE_URL')); 

      
        $this->database = $factory->createDatabase();
        $this->auth = $factory->createAuth();
        
    }

    public function startChat($senderId, $receiverId)
    {
        $participantsData = [
            'participants' => [
                '0' => $senderId,
                '1' => $receiverId,
            ],
            'type' => 'individual',
        ];
      
        $customKey = $senderId.$receiverId;
        $this->database->getReference('chat/' . $customKey)
        ->set($participantsData);
        $response = ['success' => true, 'data' => [], 'message' => 'chat started say hi!'];
        
        return response()->json($response, 200);
        
       

    }

    public function startGroupChat($groupName, $createdBy, $participants)
    {
        $uniqueGroupId = uniqid();
        $participantsData = [
            'group_name' => $groupName,
            'participants' => $participants,
            'type' => 'group',
            'created_by' => $createdBy,
        ];
        // $participantsData = [
        //     'participants' => [],
        //     'type' => 'group',
        //     'created_by' => $createdBy,
        // ];
        
        
        // foreach ($participants as $key => $participant) {
        //     $participantsData['participants'][$key] = $participant;
        // }
      
        // $customKey = $groupName;
        $this->database->getReference('chat/' . $uniqueGroupId)
        ->set($participantsData);
       
        $response = ['success' => true, 'data' => ['group_id' => $uniqueGroupId], 'message' => 'Group created'];
        // dd($response);
        return response()->json($response, 200);
        
       

    }

    // Store message in Firebase Realtime Database
    public function storeMessage($senderId, $receiverId,$message)
    {
        $chatKey = $senderId . $receiverId;
        $chatRef = $this->database->getReference('chat/' . $chatKey);
        $chatData = $chatRef->getSnapshot()->getValue();
        if ($chatData === null) {
            $this->startChat($senderId, $receiverId);
        }
        $messageData = [
            'sender_id' => $senderId,
            'content' => $message,
            'timestamp' => Carbon::now()->toDateTimeString(),
        ];
        $this->database->getReference('chat/' . $chatKey . '/messages')
        ->push($messageData);
        $response = ['success' => true, 'data' => [], 'message' => 'message sent'];
        
        return response()->json($response, 200);
       
    }

    public function storeGroupMessage($senderId, $message, $groupName)
    {
        $groupRef = $this->database->getReference('chat/' . $groupName);
        $groupData = $groupRef->getSnapshot()->getValue();
        if ($groupData === null) {
            return $this->error('Group not found',403);
        }

        if (!in_array($senderId, $groupData['participants'])) {
            return $this->error('Sender is not a participant',403);
        }
        $messageData = [
            'sender_id' => $senderId,
            'content' => $message,
            'timestamp' => Carbon::now()->toDateTimeString(),
        ];

        $this->database->getReference('chat/'.$groupName. '/messages')
            ->push($messageData);

        $response = ['success' => true, 'data' => [], 'message' => 'message sent'];
        
        return response()->json($response, 200);
        
    }

    public function getChatsForUser($userId)
    {
        // Reference to the chats in Firebase
        $chatsRef = $this->database->getReference('chat');
        
        // Retrieve all chats
        $chatsData = $chatsRef->getSnapshot()->getValue();
        // dd($chatsData);
        // If no chats are found, return null
        if ($chatsData === null) {
            return null;
        }

        $userChats = [];
        // dd($chatsData);

        foreach ($chatsData as $chatKey => $chatData) {
            // Check if the user is a participant in the chat
            if (in_array($userId, $chatData['participants'])) {
                $userChats[] = [
                    'chat_key' => $chatKey,
                    'participants' => $chatData['participants'],
                    'group_name' => $chatData['group_name'] ?? null,
                    'messages' => $chatData['messages'] ?? null,
                    'type' => $chatData['type'] ?? null,
                    'created_by' => $chatData['created_by'] ?? null, // For group chats
                ];
            }
        }

        return $userChats;
    }

   
}
