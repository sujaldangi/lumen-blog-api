<?php

namespace App\Http\Controllers;

use App\Services\FirebaseNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class NotificationController extends Controller
{
    protected $firebaseNotificationService;

    public function __construct(FirebaseNotificationService $firebaseNotificationService)
    {
        $this->firebaseNotificationService = $firebaseNotificationService;
    }

    public function sendNotification(Request $request)
    {
        $rules = [
            'token' => 'required|string',
            'title' => 'required|string',
            'body' => 'required|string',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return $this->sendError($validator->errors(),422);
        }
     

        return $this->firebaseNotificationService->sendNotification(
            $request->token,
            $request->title,
            $request->body
        );
    }
}
