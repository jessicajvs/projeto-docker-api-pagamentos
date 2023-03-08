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
| https://lumen.laravel.com/docs/10.x/routing 
|
*/

/*
$router->get('/', function () use ($router) {
    $router->app->version();
});*/

//Route`s Payments 
$router->group(['prefix' => 'payments', 'middleware' => 'auth'], function() use ($router) {
    $router->post('create', 'PaymentsController@create');
    $router->put('refreshStatusTransactions', 'PaymentsController@refreshStatusTransactions');
    $router->get('getBy', 'PaymentsController@getBy');
});
