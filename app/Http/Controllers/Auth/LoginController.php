<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    /**
     * Attempt to log the user into the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function attemptLogin(Request $request)
    {
        $credentials = $this->credentials($request);

        // Check if user exists and has active status - only look for active users
        $user = User::where('email', $credentials['email'])
                   ->where(function($query) {
                       $query->where('delete_status', 'active')
                             ->orWhereNull('delete_status');
                   })
                   ->first();

        if (!$user) {
            return false;
        }

        // Debug: Log the authentication attempt
        Log::info('Login attempt', [
            'email' => $credentials['email'],
            'user_found' => $user ? true : false,
            'delete_status' => $user ? $user->delete_status : null,
            'password_check' => $user ? Hash::check($credentials['password'], $user->password) : false
        ]);

        // Check password manually and only authenticate if user is active
        if (Hash::check($credentials['password'], $user->password)) {
            // Manually log in the user since we've already verified their credentials
            $this->guard()->login($user, $request->boolean('remember'));
            return true;
        }

        return false;
    }

    /**
     * Get the failed login response instance.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function sendFailedLoginResponse(Request $request)
    {
        throw ValidationException::withMessages([
            'email' => [trans('auth.failed')],
        ]);
    }

    /**
     * Send the response after the user was authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function sendLoginResponse(Request $request)
    {
        $request->session()->regenerate();

        $this->clearLoginAttempts($request);

        if ($response = $this->authenticated($request, $this->guard()->user())) {
            return $response;
        }

        return redirect()->intended($this->redirectPath());
    }

    /**
     * The user has been authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $user
     * @return mixed
     */
    protected function authenticated(Request $request, $user)
    {
        // Store the session ID for single-session enforcement
        $this->updateUserSession($user, $request);

        return redirect()->route('welcome');
    }

    /**
     * Update user's session information for single-session enforcement.
     *
     * @param  mixed  $user
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    protected function updateUserSession($user, Request $request)
    {
        $user->update([
            'session_id' => session()->getId(),
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);

        Log::info('User session updated for single-session enforcement', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'session_id' => substr(session()->getId(), 0, 10) . '...',
            'ip' => $request->ip(),
        ]);
    }

    /**
     * Log the user out of the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function logout(Request $request)
    {
        $user = Auth::user();

        // Clear the session ID from the database
        if ($user) {
            $user->update(['session_id' => null]);

            Log::info('User logged out, session cleared', [
                'user_id' => $user->id,
                'user_email' => $user->email,
            ]);
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
