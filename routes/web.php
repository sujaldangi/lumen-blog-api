<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->group(['prefix' => 'api'], function () use ($router) {
    $router->post('/create-blog', 'BlogController@createBlog');
    $router->get('/get-blogs', 'BlogController@viewBlog');
    $router->get('/delete-blog', 'BlogController@deleteBlog');
    $router->get('/like-blog', 'BlogController@likeBlog');
    $router->delete('/delete-comment', 'BlogController@deleteComment');
    $router->get('/rate-blog', 'BlogController@rateBlog');
    $router->put('/update-blog', 'BlogController@updateBlog');

    $router->post('/send-email', 'BlogController@sendEmailToUser');
    $router->get('/search-blog', 'BlogController@searchBlogs');
    $router->get('/get-deleted-blogs', 'BlogController@viewDeletedBlog');
    $router->post('/retrieve-deleted-blogs', 'BlogController@retrieveDeletedBlog');

    
    // // Google OAuth 2.0 Routes
    // $router->get('/auth/google', function () {
    //     $client = new Google_Client();
    //     $client->setClientId(env('GOOGLE_CLIENT_ID'));
    //     $client->setClientSecret(env('GOOGLE_CLIENT_SECRET'));
    //     $client->setRedirectUri(env('GOOGLE_REDIRECT_URI'));
    //     $client->addScope(Google_Service_Gmail::GMAIL_SEND);

    //     return redirect($client->createAuthUrl());
    // });

    // // Callback route after Google OAuth 2.0 authentication
    // $router->get('/callback', function () {
    //     $client = new Google_Client();
    //     $client->setClientId(env('GOOGLE_CLIENT_ID'));
    //     $client->setClientSecret(env('GOOGLE_CLIENT_SECRET'));
    //     $client->setRedirectUri(env('GOOGLE_REDIRECT_URI'));

    //     $code = request('code');
    //     $token = $client->fetchAccessTokenWithAuthCode($code);

    //     // Save the access token to session or your database for later use
    //     session(['google_access_token' => $token]);

    //     return redirect('/');
    // });


});

$router->get('/google/redirect', 'GoogleController@redirectToGoogle'); // Initiate OAuth flow
$router->get('/google/callback', 'GoogleController@handleOAuthCallback');
$router->post('/send-email', 'EmailController@sendEmailToUser');
$router->post('/send-notification', 'NotificationController@sendNotification');


