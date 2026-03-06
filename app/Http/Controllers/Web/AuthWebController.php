<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Throwable;

class AuthWebController extends Controller
{
    public function showLogin(Request $request): View
    {
        $request->session()->regenerateToken();

        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        try {
            if (! Auth::attempt($credentials, $request->boolean('remember'))) {
                return back()->withErrors([
                    'email' => 'Email atau password tidak valid.',
                ])->onlyInput('email');
            }
        } catch (QueryException|Throwable $exception) {
            report($exception);

            return back()->withErrors([
                'email' => 'Koneksi database sedang bermasalah. Silakan coba lagi beberapa saat.',
            ])->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->route('ui.dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
