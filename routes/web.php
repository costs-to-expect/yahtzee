<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get(
    '/',
    [\App\Http\Controllers\Authentication::class, 'signIn']
)->name('sign-in.view');

Route::post(
    '/sign-in',
    [\App\Http\Controllers\Authentication::class, 'signInProcess']
)->name('sign-in.process');

Route::get(
    '/sign-out',
    [\App\Http\Controllers\Authentication::class, 'signOut']
)->name('sign-out');

Route::group(
    [
        'middleware' => [
            'auth'
        ]
    ],
    static function() {
        Route::get('/home', static function () {
            return view('home');
        })->name('home');

        Route::get('/new-game', static function () {
            return view('new-game');
        })->name('new-game');

        Route::get('/game', static function () {
            return view('game');
        })->name('game');
    }
);
