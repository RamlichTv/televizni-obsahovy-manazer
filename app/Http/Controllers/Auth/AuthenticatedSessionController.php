<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Create a new resource.
     *
     * @return View
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Store a newly created resource.
     *
     * @param LoginRequest $request
     * @return RedirectResponse
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $this->logEvent(
            typeName: 'user_logged_in',
            message: 'User "' . ($request->user()?->username ?? 'unknown') . '" logged in.',
            context: [
                'action' => 'auth.login',
                'ip' => (string) $request->ip(),
                'user_agent' => (string) $request->userAgent(),
            ],
            userId: $request->user()?->id,
        );

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Delete the current resource.
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user !== null) {
            $this->logEvent(
                typeName: 'user_logged_out',
                message: 'User "' . $user->username . '" logged out.',
                context: [
                    'action' => 'auth.logout',
                    'ip' => (string) $request->ip(),
                    'user_agent' => (string) $request->userAgent(),
                ],
                userId: $user->id,
            );
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}


