<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    /**
     * Display the login view.
     */
    public function showLoginForm(): View
    {
        return view('auth.login');
    }

    /**
     * Handle login request.
     */
    public function login(LoginRequest $request): RedirectResponse
    {
        // Check if account is suspended or banned
        $user = \App\Models\User\User::where('email', $request->string('email'))->first();
        
        if ($user && in_array($user->account_status, ['suspended', 'banned'], true)) {
            throw ValidationException::withMessages([
                'email' => 'This account is currently ' . $user->account_status . '. Please contact support.',
            ]);
        }

        $request->authenticate();
        $request->session()->regenerate();

        return redirect()->intended(
            $request->user()->isAdmin()
                ? route('admin.dashboard')
                : route('dashboard')
        );
    }

    /**
     * Handle logout request.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
