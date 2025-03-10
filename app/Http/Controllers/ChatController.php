<?php

namespace App\Http\Controllers;

use App\Services\FirebaseService;
use App\Services\FirebaseNotificationService;
use Illuminate\Support\Facades\Validator;
use App\Events\MessageSent;

use Illuminate\Http\Request;

class ChatController extends Controller
{
    protected $firebaseService;
    protected $firebaseNotificationService;

    public function __construct(FirebaseService $firebaseService,FirebaseNotificationService $firebaseNotificationService)
    {
        $this->firebaseService = $firebaseService;
        $this->firebaseNotificationService = $firebaseNotificationService;
    }

    public function startChat(Request $request)
    {
        $rules = [
            'receiver_id' => 'required|string',
            'sender_id' => 'required|string',
        ];

        $validator = Validator::make($request->all(), $rules);
        $validated = $validator->validated();
       
        if ($validator->fails()) {
            return $this->sendError($validator->errors(),422);
        }
      

        $this->firebaseService->startChat(
            $validated['sender_id'],
            $validated['receiver_id']
        );
        return $this->sendResponse($post,'Chat started say hi!');

    }

    public function startGroupChat(Request $request)
    {
        $rules = [
            'group_name' => 'required|string',
            'created_by' => 'required|string',
            'participants' => 'required|array',
        ];

        $validator = Validator::make($request->all(), $rules);
        $validated = $validator->validated();
       
        if ($validator->fails()) {
            return $this->sendError($validator->errors(),422);
        }
        $validated['participants'][] =  $validated['created_by'];
      

        return $this->firebaseService->startGroupChat(
            $validated['group_name'],
            $validated['created_by'],
            $validated['participants'],
        );
       
    }

    // Store a new message in Firebase
    public function sendMessage(Request $request)
    {
        $rules = [
            'sender_id' => 'required|string',
            'message' => 'required|string',
            'chat_type' => 'required|string', // 'individual' or 'group'
            'receiver_id' => 'nullable|string', // Only required for individual chat
            'group_id' => 'nullable|string',  // Only required for group chat
        ];

        $validator = Validator::make($request->all(), $rules);
        $validated = $validator->validated();
        if ($validator->fails()) {
            return $this->sendError($validator->errors(),422);
        }

        if ($validated['chat_type'] == 'individual') {
            $response = $this->firebaseService->storeMessage(
                $validated['sender_id'],
                $validated['receiver_id'],
                $validated['message']
            );
            $this->sendNotification('ejKrKRgwkRI5uoCbzDypq3:APA91bE4j6W5Pq4FsSV5Oo57zZAAG8DnfNejAnS_1kiaF57lmrsfFukzKaAFNHt6BdjQxzgqzGvc7mxyGBSTa7jtKKFCSnREIXrykA6Bi5yAgZB1ZMPyVYE','new message',$validated['message']);
            $a = event(new MessageSent($validated['message']));
            \Log::info( $a);
            return $response;
        } elseif ($validated['chat_type'] == 'group') {
            $response = $this->firebaseService->storeGroupMessage(
                $validated['sender_id'],
                $validated['message'],
                $validated['group_id'],
            );
            $this->sendNotification('ejKrKRgwkRI5uoCbzDypq3:APA91bE4j6W5Pq4FsSV5Oo57zZAAG8DnfNejAnS_1kiaF57lmrsfFukzKaAFNHt6BdjQxzgqzGvc7mxyGBSTa7jtKKFCSnREIXrykA6Bi5yAgZB1ZMPyVYE','new message',$validated['message']);
            event(new MessageSent($validated['message']));
            

            return $response;
        }
    }

    public function getChats(Request $request)
    {
        $userId = $request->user_id; // Assuming user_id is passed in the request
    
        // Validate that the user_id is provided
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|string',
        ]);
        
        if ($validator->fails()) {
            return $this->sendError($validator->errors(), 422);
        }
    
        // Fetch chats that involve the given user
        $chats = $this->firebaseService->getChatsForUser($userId);
    
        if ($chats === null) {
            return $this->sendResponse([],'No chats found');
        }
        return $this->sendResponse($chats,'Chats Retrieved');
    }

    public function sendNotification($token,$title,$body)
    {
        
        return $this->firebaseNotificationService->sendNotification(
            $token,
            $title,
            $body
        );
    }

    


}
