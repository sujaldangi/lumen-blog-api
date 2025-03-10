<?php

namespace App\Http\Controllers;
use App\Models\Posts;
use App\Models\Likes;
use App\Models\Comments;
use App\Models\Rating;
use Illuminate\Support\Facades\Validator;
use Exception;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    protected $postType = 'blog';
    // Create a new blog post
    public function createBlog(Request $request)
    {
        try {

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
            $validatedData['post_type'] = $this->postType;

            $post = Posts::create($validatedData);

            return $this->sendResponse($post, 'Blog post created successfully');
        } catch (\Exception $e) {
            return $this->sendResponse([], 'Error occurred: ' . $e->getMessage());
        }

    }
    // View blog posts, with optional category filtering
    public function viewBlog(Request $request)
    {
        try {
            if ($request->has('category')) {
                $postData = Posts::where('category', $request->input('category'))->where('status', 'published')->get();

            } else {
                $postData = Posts::where('status', 'published')->get();

            }
            foreach ($postData as $post) {
                // Attach likes, comments, ratings to each post
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
            return $this->sendResponse($postData, 'Blog posts fetched successfully');
        } catch (\Exception $e) {
            return $this->sendResponse([], 'Error occurred: ' . $e->getMessage());
        }
    }
    // Archive a blog post
    public function deleteBlog(Request $request)
    {
        try {
            if ($request->has('post_id')) {
                $blogId = $request->input('post_id');
                $blog = Posts::where('id', $blogId)->where('status', 'published')->first();

                if (!$blog) {
                    return $this->sendError('Blog not found', 404);
                }
                $postData = Posts::where('id', $request->input('post_id'))->update(['status' => 'archived']);

            } else {
                return $this->sendError('id not given', 422);
            }
            return $this->sendResponse($postData, 'Blog post archived successfully');
        } catch (\Exception $e) {
            return $this->sendResponse([], 'Error occurred: ' . $e->getMessage());
        }
    }
    // Like or unlike a blog post
    public function likeBlog(Request $request)
    {
        try {
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
    // Add a comment to a blog post
    public function commentBlog(Request $request)
    {
        try {
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
    // Delete a comment from a blog post
    public function deleteComment(Request $request)
    {
        try {
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
    // Rate a blog post
    public function rateBlog(Request $request)
    {
        try {
            $rules = [
                'rating' => 'required|integer|between:1,5',
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return $this->sendError($validator->errors(), 422);
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
    // Update an existing blog post
    public function updateBlog(Request $request)
    {
        try {
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
    // Search for blog posts by keyword
    public function searchBlogs(Request $request)
    {
        try {
            $rules = [
                'search' => 'required|string',
            ];
            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return $this->sendError($validator->errors(), 422);
            }

            $searchKeyword = $request->input('search');

            $matchingBlogs = Posts::where('status', 'published')
                ->where(function ($query) use ($searchKeyword) {
                    $query->where('post_title', 'like', '%' . $searchKeyword . '%')
                        ->orWhere('post_description', 'like', '%' . $searchKeyword . '%')
                        ->orWhere('post_content', 'like', '%' . $searchKeyword . '%')
                        ->orWhere('category', 'like', '%' . $searchKeyword . '%');
                })
                ->get();

            if ($matchingBlogs->isEmpty()) {
                return $this->sendResponse([], 'No matching blogs found.');
            }
            foreach ($matchingBlogs as $post) {
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
            return $this->sendResponse($matchingBlogs, 'Matching blogs fetched successfully');



        } catch (Exception $e) {
            return $this->sendError('Error sending email: ' . $e->getMessage(), 500);
        }
    }
    // View archived (deleted) blog posts
    public function viewDeletedBlog(Request $request)
    {
        try {

            $postData = Posts::where('status', 'archived')->get();
            foreach ($postData as $post) {
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
            return $this->sendResponse($postData, 'Blog posts fetched successfully');
        } catch (\Exception $e) {
            return $this->sendResponse([], 'Error occurred: ' . $e->getMessage());
        }
    }
    // Restore a deleted (archived) blog post
    public function retrieveDeletedBlog(Request $request)
    {
        try {

            $postId = $request->input('post_id');

            if (empty($postId)) {
                return $this->sendError('Post ID is required.', 422);
            }

            $archivedPost = Posts::where('id', $postId)->where('status', 'archived')->first();

            if (!$archivedPost) {
                return $this->sendError('Archived post not found.', 404);
            }
            $archivedPost->status = 'published';
            $archivedPost->save();

            return $this->sendResponse($archivedPost, 'Archived post restored successfully.');

        } catch (\Exception $e) {
            return $this->sendResponse([], 'Error occurred: ' . $e->getMessage());
        }
    }
}
