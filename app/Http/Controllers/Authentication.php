<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Api\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * @author Dean Blackborough <dean@g3d-development.com>
 * @copyright Dean Blackborough (Costs to Expect) 2018-2022
 * https://github.com/costs-to-expect/yahtzee/blob/main/LICENSE
 */
class Authentication extends Controller
{
    public function createPassword(Request $request)
    {
        return view(
            'create-password',
            [
                'parameters' => session()->get('authentication.parameters'),
                'errors' => session()->get('authentication.errors'),
                'failed' => session()->get('authentication.failed'),
            ]
        );
    }

    public function createPasswordProcess(Request $request)
    {
        $api = new Service();

        $response = $api->createPassword(
            $request->only(['token', 'email', 'password', 'password_confirmation'])
        );

        if ($response['status'] === 204) {
            return redirect()->route('registration-complete');
        }

        if ($response['status'] === 422) {
            return redirect()->route('create-password.view')
                ->withInput()
                ->with('authentication.errors', $response['fields']);
        }

        return redirect()->route('create-password.view')
            ->with('authentication.failed', $response['content']);
    }

    public function register()
    {
        return view(
            'register',
            [
                'errors' => session()->get('authentication.errors'),
                'failed' => session()->get('authentication.failed'),
            ]
        );
    }

    public function registerProcess(Request $request)
    {
        $api = new Service();

        $response = $api->register(
            $request->only(['name','email'])
        );

        if ($response['status'] === 201) {
            return redirect()->route('create-password.view')
                ->with('authentication.parameters', $response['content']['uris']['create-password']['parameters']);
        }

        if ($response['status'] === 422) {
            return redirect()->route('register.view')
                ->withInput()
                ->with('authentication.errors', $response['fields']);
        }

        return redirect()->route('register.view')
            ->with('authentication.failed', $response['content']);
    }

    public function registrationComplete()
    {
        return view(
            'registration-complete',
            [
            ]
        );
    }

    public function signIn()
    {
        return view(
            'sign-in',
            [
                'errors' => session()->get('authentication.errors')
            ]
        );
    }

    public function signInProcess(Request $request)
    {
        $credentials = $request->only(['email', 'password']);

        if (Auth::attempt($credentials, $request->input('remember_me') !== null)) {
            return redirect()->route('home');
        }

        return redirect()->route('sign-in.view')
            ->withInput()
            ->with(
                'authentication.errors',
                Auth::errors()
            );
    }

    public function signOut(): RedirectResponse
    {
        Auth::guard()->logout();

        return redirect()->route('sign-in.view');
    }
}
