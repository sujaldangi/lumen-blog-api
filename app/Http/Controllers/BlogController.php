<?php

namespace App\Http\Controllers;
use App\Models\Posts;
use App\Models\Likes;
use App\Models\Comments;
use App\Models\Rating;
use Illuminate\Support\Facades\Validator;
use Google\Cloud\Language\LanguageClient;
use Google_Client;
use Google_Service_Gmail;
use Google_Service_Gmail_Message;
use Google_Service_Gmail_MessagePart;
use Google_Service_Gmail_MessagePartHeader;
use Exception;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    protected $languageClient;
    protected $postType = 'blog';
    //
    public function __construct()
    {
        $this->languageClient = new LanguageClient();
    }

    public function createBlog(Request $request)
    {
        try{
            
            $rules = [
                'post_title' => 'required|string|max:255',
                'post_description' => 'required|string|max:255',
                'post_content' => 'required|string',
                'featured_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
                'author_id' => 'required',
                'category' => 'nullable',
                'status' => 'required|in:published,archived,draft',
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return $this->sendError($validator->errors(),422);
            }


            if ($request->hasFile('featured_image')) {
                $image = $request->file('featured_image');
                $imagePath = $image->store('featured_images', 'public');
                $validatedData = $validator->validated();
                $validatedData['featured_image'] = 'storage/'. $imagePath;
               
            } else {
                
                $validatedData = $validator->validated();
            }
            $validatedData['post_type'] = $this->postType;
            
            // $annotation = $this->languageClient->analyzeSentiment($validatedData['post_content']);
            // $score = $annotation->sentiment()['score'];
            // // dd($score);
            // $threshold = 0.2;
            // if ($score < $threshold) {
            //     return $this->sendResponse([], 'Content not passed due to negativity or potential profanity.');
            // }
           
            $post = Posts::create($validatedData);
            
            return $this->sendResponse($post,'Blog post created successfully');
        } catch (\Exception $e) {
            return $this->sendResponse([], 'Error occurred: ' . $e->getMessage());
        }

    }

    public function viewBlog(Request $request)
    {
        try{
            if($request->has('category'))
            {
                $postData = Posts::where('category',$request->input('category'))->where('status','published')->get();

            }else
            {
                // $postData = Posts::all()->where('status','published')->get();
                $postData = Posts::where('status', 'published')->get();

            }
            foreach ($postData as $post){
                $post->post_likes = Likes::where('post_id', $post->id)->count();
                $post->post_comments = Comments::where('post_id', $post->id)->get();
                $post->post_comments_count = Comments::where('post_id', $post->id)->count();
                $post->post_ratings = Rating::where('post_id', $post->id)->get();
                if ($post->post_ratings->count() > 0) {
                    $totalRating = $post->post_ratings->sum('rating');
                    $post->average_rating = $totalRating / $post->post_ratings->count();
                } else {
                    $post->average_rating = 0;
                }
            }
            return $this->sendResponse($postData,'Blog posts fetched successfully');
        } catch (\Exception $e) {
            return $this->sendResponse([], 'Error occurred: ' . $e->getMessage());
        }
    }

    public function deleteBlog(Request $request)
    {
        try{
            if($request->has('post_id'))
            {
                $blogId = $request->input('post_id');
                $blog = Posts::where('id', $blogId)->where('status', 'published')->first();

                if (!$blog) {
                    return $this->sendError('Blog not found', 404);
                }
                $postData = Posts::where('id',$request->input('post_id'))->update(['status'=>'archived']);

            }else
            {
                return $this->sendError('id not given',422);
            }
            return $this->sendResponse($postData,'Blog post archived successfully');
        } catch (\Exception $e) {
            return $this->sendResponse([], 'Error occurred: ' . $e->getMessage());
        }
    }

    public function likeBlog(Request $request)
    {
        try{
            $userId = $request->input('user_id');
            $blogId = $request->input('post_id');
            if (empty($userId) || empty($blogId)) {
                return $this->sendError('User id or blog id not provided', 422);
            }
            $blog = Posts::where('id', $blogId)->where('status', 'published')->first();

            if (!$blog) {
                return $this->sendError('Blog not found or blog archieved', 404);
            }

            $like = Likes::where('user_id', $userId)->where('post_id', $blogId)->first();
            if ($like) {
                $like->delete();
                return $this->sendResponse([], 'Blog unliked successfully');
            } else {
                Likes::create([
                    'user_id' => $userId,
                    'post_id' => $blogId,
                ]);
                return $this->sendResponse([], 'Blog liked successfully');
            }
    

        } catch (\Exception $e) {
            return $this->sendResponse([], 'Error occurred: ' . $e->getMessage());
        }
    }

    public function commentBlog(Request $request)
    {
        try{
            $userId = $request->input('user_id');
            $blogId = $request->input('post_id');
            $blogComment = $request->input('post_comment');
            if (empty($userId) || empty($blogId)) {
                return $this->sendError('User id or blog id not provided', 422);
            }
            $blog = Posts::where('id', $blogId)->where('status', 'published')->first();

            if (!$blog) {
                return $this->sendError('Blog not found or blog archieved', 404);
            }

            
            Comments::create([
                'user_id' => $userId,
                'post_id' => $blogId,
                'post_comment' => $blogComment,
            ]);
            return $this->sendResponse([], 'Comment added to the blog');
            
    

        } catch (\Exception $e) {
            return $this->sendResponse([], 'Error occurred: ' . $e->getMessage());
        }
    }

    public function deleteComment(Request $request)
    {
        try{
            $commentId = $request->input('comment_id');
            $comment = Comments::find($commentId);
            if (!$comment) {
                return $this->sendResponse([], 'Comment not found', 404);
            }
            $comment->delete();
            return $this->sendResponse([], 'Comment deleted successfully');
        } catch (\Exception $e) {
            return $this->sendResponse([], 'Error occurred: ' . $e->getMessage());
        }
    }

    public function rateBlog(Request $request)
    {
        try{
            $rules = [
                'rating' => 'required|integer|between:1,5',
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return $this->sendError($validator->errors(),422);
            }

            
            $userId = $request->input('user_id');
            $blogId = $request->input('post_id');
            $rating = $request->input('rating');

            if (empty($userId) || empty($blogId)) {
                return $this->sendError('User id or blog id not provided', 422);
            }

            $blog = Posts::where('id', $blogId)->where('status', 'published')->first();

            if (!$blog) {
                return $this->sendError('Blog not found or blog archieved', 404);
            }

            $ratingCheck = Rating::where('user_id', $userId)->where('post_id', $blogId)->first();
            if ($ratingCheck) {
                
                return $this->sendResponse([], 'You have already rated this blog.');
            } else {
                Rating::create([
                    'user_id' => $userId,
                    'post_id' => $blogId,
                    'rating' => $rating,
                ]);
                return $this->sendResponse([], 'Blog rated successfully');
            }

        } catch (\Exception $e) {
            return $this->sendResponse([], 'Error occurred: ' . $e->getMessage());
        }
    }

    public function updateBlog(Request $request)
    {
        try{
            $authorId = $request->input('author_id');
            $blogId = $request->input('post_id');
            
            if (empty($authorId) || empty($blogId)) {
                return $this->sendError('User id or blog id not provided', 422);
            }

            $blog = Posts::where('id', $blogId)->where('status', 'published')->first();

            if (!$blog) {
                return $this->sendError('Blog not found or blog archieved', 404);
            }

            if ($blog->author_id !== $authorId) {
                return $this->sendError('Unauthorized: Author ID does not match the post', 403);
            }

            $rules = [
                'post_title' => 'nullable|string|max:255',
                'post_description' => 'nullable|string|max:255',
                'post_content' => 'nullable|string',
                'featured_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
                'author_id' => 'nullable',
                'category' => 'nullable',
                'status' => 'nullable|in:published,archived,draft',
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return $this->sendError($validator->errors(), 422);
            }

            if ($request->hasFile('featured_image')) {
                $image = $request->file('featured_image');
                $imagePath = $image->store('featured_images', 'public');
                $validatedData = $validator->validated();
                $validatedData['featured_image'] = 'storage/' . $imagePath;
            } else {
                $validatedData = $validator->validated();
            }
    
            $blog->update($validatedData);
    
            return $this->sendResponse($blog, 'Blog post updated successfully');

        } catch (\Exception $e) {
            return $this->sendResponse([], 'Error occurred: ' . $e->getMessage());
        }
    }

    public function sendMail(Request $request)
    {
        try {
            // Validate the email
            $rules = [
                'to_email' => 'required|email',
                'subject' => 'required|string',
                'body' => 'required|string',
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return $this->sendError($validator->errors(), 422);
            }

            $toEmail = $request->input('to_email');
            $subject = $request->input('subject');
            $body = $request->input('body');

            // Authenticate with Google API
            $client = new Google_Client();
            $client->setAuthConfig('/home/ashok/Downloads/client_secret_110002800197-ndvm7lsrqrgrq4mme5t7nl183g0u8cek.apps.googleusercontent.com.json');
            $client->addScope(Google_Service_Gmail::GMAIL_SEND);
            $client->setAccessType('offline');

            // Check for stored token
            $tokenPath = '/home/ashok/Downloads/client_secret_110002800197-ndvm7lsrqrgrq4mme5t7nl183g0u8cek.apps.googleusercontent.com.json';
            if (file_exists($tokenPath)) {
                $accessToken = json_decode(file_get_contents($tokenPath), true);
                $client->setAccessToken($accessToken);
            } else {
                // Redirect to OAuth URL for authorization if no token is stored
                return $this->sendError('OAuth token not available', 401);
            }

            // Send the email if access is valid
            if ($client->isAccessTokenExpired()) {
                return $this->sendError('Access token expired', 401);
            }

            $gmailService = new Google_Service_Gmail($client);

            // Create the email
            $message = new Google_Service_Gmail_Message();
            $rawMessageString = $this->createMessage($toEmail, $subject, $body);
            $rawMessage = base64url_encode($rawMessageString);
            $message->setRaw($rawMessage);

            // Send the message
            $gmailService->users_messages->send('me', $message);

            return $this->sendResponse([], 'Email sent successfully.');
        } catch (Exception $e) {
            return $this->sendError('Error sending email: ' . $e->getMessage(), 500);
        }
    }

    // Helper function to create the raw email message in MIME format
    private function createMessage($to, $subject, $body)
    {
        $headers = [
            'To' => $to,
            'Subject' => $subject,
            'Content-Type' => 'text/html; charset=UTF-8',
        ];

        $rawMessage = "Content-Type: text/html; charset=UTF-8\r\n";
        foreach ($headers as $key => $value) {
            $rawMessage .= "$key: $value\r\n";
        }

        $rawMessage .= "\r\n" . $body;
        return $rawMessage;
    }

    // Base64 URL encoding
    function base64url_encode($data)
    {
        return strtr(base64_encode($data), '+/', '-_');
    }


    
}
