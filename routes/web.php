<?php

use App\Http\Controllers\Authentication;
use App\Http\Controllers\Game;
use App\Http\Controllers\Index;
use App\Http\Controllers\Player;
use Illuminate\Support\Facades\Route;

Route::get(
    '/',
    [Authentication::class, 'signIn']
)->name('sign-in.view');

Route::post(
    '/sign-in',
    [Authentication::class, 'signInProcess']
)->name('sign-in.process');

Route::get(
    '/sign-out',
    [Authentication::class, 'signOut']
)->name('sign-out');

Route::group(
    [
        'middleware' => [
            'auth'
        ]
    ],
    static function() {
        Route::get(
            '/home',
            [Index::class, 'home']
        )->name('home');


        Route::get(
            '/new-game',
            [Game::class, 'newGame']
        )->name('game.create.view');

        Route::post(
            '/new-game',
            [Game::class, 'newGameProcess']
        )->name('game.create.process');

        Route::get(
            '/game/{game_id}/player/{player_id}/score-sheet',
            [Game::class, 'scoreSheet']
        )->name('game.score-sheet');

        Route::get(
            '/games',
            [Game::class, 'index']
        )->name('games');

        Route::post(
            '/game/score-upper',
            [Game::class, 'scoreUpper']
        )->name('game.score-upper');

        Route::post(
            '/game/score-lower',
            [Game::class, 'scoreLower']
        )->name('game.score-lower');

        Route::get(
            '/add-players-to-game/{game_id}',
            [Game::class, 'addPlayersToGame']
        )->name('game.add-players.view');

        Route::post(
            '/add-players-to-game/{game_id}',
            [Game::class, 'addPlayersToGameProcess']
        )->name('game.add-players.process');


        Route::get(
            '/players',
            [Player::class, 'index']
        )->name('players');

        Route::get(
            '/new-player',
            [Player::class, 'newPlayer']
        )->name('player.create.view');

        Route::post(
            '/new-player',
            [Player::class, 'newPlayerProcess']
        )->name('player.create.process');
    }
);
