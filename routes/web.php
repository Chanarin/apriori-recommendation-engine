<?php

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

$app->get('/transactions', 'TransactionController@index');

$app->post('/transactions', 'TransactionController@store');

$app->post('/oauth/access_token', function() use($app){
    return response()->json($app->make('oauth2-server.authorizer')->issueAccessToken());
});