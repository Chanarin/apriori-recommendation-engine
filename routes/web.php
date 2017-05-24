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

/*
|--------------------------------------------------------------------------
| Transaction RESTful API endpoints
|--------------------------------------------------------------------------
*/

$app->get('/transactions/{id}', 'TransactionController@show');

$app->get('/redis_keys/{id}/transactions', 'RedisKeyTransactionController@transactions');
$app->post('/redis_keys/{id}/transactions', 'RedisKeyTransactionController@store');

/*
|--------------------------------------------------------------------------
| RedisKey RESTful API endpoints
|--------------------------------------------------------------------------
*/

$app->get('/redis_keys/{id}', 'RedisKeyController@show');

$app->post('/users/{id}/redis_keys', 'UserRedisKeyController@store');
$app->get('/users/{id}/redis_keys', 'UserRedisKeyController@index');
$app->put('/users/{id}/redis_keys/{redis_key_id}', 'UserRedisKeyController@update');
$app->patch('/users/{id}/redis_keys/{redis_key_id}', 'UserRedisKeyController@update');
$app->delete('users/{id}/redis_keys/{redis_key_id}', 'UserRedisKeyController@destroy');

/*
|--------------------------------------------------------------------------
| User RESTful API endpoints
|--------------------------------------------------------------------------
*/

$app->post('/users', 'UserController@store');
$app->get('/users', 'UserController@index');
$app->get('/users/{id}', 'UserController@show');
$app->patch('/users/{id}', 'UserController@update');
$app->put('/users/{id}', 'UserController@update');
$app->patch('/users/{id}/credentials', 'UserController@credentials');
$app->put('/users/{id}/credentials', 'UserController@credentials');
$app->delete('/users/{id}','UserController@destroy');

/*
|--------------------------------------------------------------------------
| OAuth2.0 RESTful API access token endpoint
|--------------------------------------------------------------------------
*/

$app->post('/oauth/access_token', function() use($app){
    return response()->json($app->make('oauth2-server.authorizer')->issueAccessToken());
});