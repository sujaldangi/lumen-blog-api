<?php
 
namespace App\Http\Controllers;
 
use Illuminate\Http\Request;
use Google_Client;
use Google_Service_Gmail;
use Google_Service_Gmail_Message;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\File;
use App\Services\GmailService;
 
 
class EmailController extends Controller
{
    protected $gmailService;
    public function __construct(GmailService $gmailService)
    {
        $this->gmailService = $gmailService;
    }
    
    public function sendEmailToUser(Request $request)
    {
        // Get the email and message from the request
        $email = $request->input('email');
        $subject = $request->input('subject', 'New Blog Notification');
        $messageText = $request->input('message', 'Hello, this is a notification regarding your blog.');
 
        try {
            $tokens = json_decode(File::get(storage_path('google_tokens.json')), true);
 
            if (!$tokens) {
                return response()->json(['error' => 'Access token is missing or expired. Please authenticate first.'], 401);
            }
 
            $accessToken = $tokens['access_token'];
            // dd($accessToken);
            if (!$accessToken) {
                return response()->json(['error' => 'Access token is missing or expired. Please authenticate first.'], 401);
            }
 
            // Create Google Client and set the access token
            $client = new Google_Client();
            $client->setClientId(env('GOOGLE_CLIENT_ID'));
            $client->setClientSecret(env('GOOGLE_CLIENT_SECRET'));
            $client->setAccessToken($accessToken);
 
            // Check if the access token is expired, and refresh it if necessary
            // if ($client->isAccessTokenExpired()) {
            //     $refreshToken = session('google_refresh_token');
            //     if ($refreshToken) {
            //         $client->fetchAccessTokenWithRefreshToken($refreshToken);
            //         session(['google_access_token' => $client->getAccessToken()]);
            //     } else {
            //         return response()->json(['error' => 'Refresh token missing. Please re-authenticate.'], 401);
            //     }
            // }
 
            // Initialize Gmail Service
            $gmailService = new Google_Service_Gmail($client);
 
            // Create and send the email message
            $message = new Google_Service_Gmail_Message();
            $rawMessage = $this->createMessage($email, $subject, $messageText);
            $message->setRaw($rawMessage);
 
            $gmailService->users_messages->send('me', $message);
 
            return response()->json(['message' => 'Email sent successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to send email', 'details' => $e->getMessage()], 500);
        }
    }
 
    public function sendEmail(Request $request)
    {
        $to = $request->input('to');
        $subject = $request->input('subject');
        $body = $request->input('body');

        $result = $this->gmailService->sendEmail($to, $subject, $body);

        return response()->json(['message' => $result]);
    }

    private function createMessage($to, $subject, $messageText)
    {
        $message = "To: " . $to . "\r\n";
        $message .= "Subject: " . $subject . "\r\n";
        $message .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $message .= "Content-Transfer-Encoding: 7bit\r\n";
        $message .= "\r\n" . $messageText;
 
        // Encode the message
        return base64_encode($message);
    }
 
}