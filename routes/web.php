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

    $router->post('/send-email', 'BlogController@sendMail');
   


});

Route::get('google/authorize', 'GoogleOAuthController@authorize');
Route::get('google/handle-redirect', 'GoogleOAuthController@handleRedirect');
