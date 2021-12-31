<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     *
     * @param  \App\Http\Requests\Auth\LoginRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string'
        ]);

        $response = Http::withHeaders([
            'Accept' => 'application/json'
        ])
            ->post(env('API_URL') . '/api/v1/login', [
                'email' => $request->email,
                'password' => $request->password
            ]);

        if ($response->status() === 404) {
            return back()->withErrors('These credentials do not match our records');
        }

        $service = $response->json();

        $user = User::updateOrCreate([
            'email' => $request->email,
        ], $service['data']);

        if (!$user->accessToken()->count()) {
            $response = Http::withHeaders([
                'Accept' => 'application/json'
            ])
                ->post(env('API_URL') . '/oauth/token', [
                    'grant_type' => 'password',
                    'client_id' => '953b4437-584a-4fa0-9b0d-c126da02fdc9',
                    'client_secret' => '8UTLLL7pQ4igQ57x9P9Mn9FRMNRHxEEvEbJwIP8u',
                    'username' => $request->email,
                    'password' => $request->password,
                ]);

            $access_token = $response->json();

            $user->accessToken()->create([
                'service_id' => $service['data']['id'],
                'access_token' => $access_token['access_token'],
                'refresh_token' => $access_token['refresh_token'],
                'expires_at' => now()->addSecond($access_token['expires_in'])
            ]);
        }

        Auth::login($user, $request->remember);

        return redirect()->intended(RouteServiceProvider::HOME);

        // $request->authenticate();

        // $request->session()->regenerate();

        // return redirect()->intended(RouteServiceProvider::HOME);
    }

    /**
     * Destroy an authenticated session.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
