<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Log;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var string[]
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var string[]
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Override the parent's prepareException so TokenMismatchException stays
     * as itself instead of being silently converted to a generic 419
     * HttpException. The parent's match() on line 644 of the framework's
     * Handler.php happens BEFORE renderViaCallbacks(), which means a
     * renderable() closure typehinted with TokenMismatchException would
     * never match. Keeping the original class lets our redirect closure
     * (and reportable logging) fire as intended.
     */
    protected function prepareException(Throwable $e)
    {
        if ($e instanceof TokenMismatchException) {
            return $e;
        }
        return parent::prepareException($e);
    }

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        // TokenMismatchException sits in the framework's internalDontReport
        // list, so removing it here lets our reportable callback below
        // actually fire. (We never want to call Log::warning on every
        // exception, just on 419s where forensic data matters.)
        $this->stopIgnoring(TokenMismatchException::class);

        // Log 419s explicitly so future incidents leave forensic evidence.
        // Laravel's default handler converts TokenMismatchException into a
        // 419 response without writing it to the log, which makes intermittent
        // CSRF issues nearly impossible to diagnose after the fact.
        $this->reportable(function (TokenMismatchException $e) {
            $request = request();
            Log::warning('CSRF token mismatch (419)', [
                'url'        => $request->fullUrl(),
                'method'     => $request->method(),
                'ip'         => $request->ip(),
                'has_token'  => $request->hasHeader('X-CSRF-TOKEN') || $request->filled('_token'),
                // has_cookie reads the DECRYPTED bag, so it goes false both when
                // the browser sent nothing and when EncryptCookies threw the
                // cookie away because it could not decrypt it. Those are opposite
                // problems - a scope/SameSite issue versus a stale cookie from an
                // older APP_KEY - and telling them apart needs the raw header.
                'has_cookie' => $request->hasCookie(config('session.cookie')),
                'raw_cookie' => str_contains((string) $request->header('Cookie'), config('session.cookie') . '='),
                'raw_cookie_count' => substr_count((string) $request->header('Cookie'), config('session.cookie') . '='),
                'user_agent' => substr((string) $request->header('User-Agent'), 0, 200),
            ]);
        });

        // Break the "refresh = 419 forever" cycle: when a POST hits CSRF
        // mismatch, redirect the user back to the previous page (or to
        // /login for auth flows) with a friendly flash message — so the URL
        // becomes GET-able and refresh stops resubmitting the stale POST.
        $this->renderable(function (TokenMismatchException $e, $request) {
            // Don't hijack AJAX / API calls — they should keep getting the
            // structured 419 JSON so their client can refetch the token.
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Session expired. Please refresh and try again.',
                    'code'    => 'CSRF_TOKEN_MISMATCH',
                ], 419);
            }

            $target = $request->routeIs('login') || $request->is('login')
                ? route('login')
                : url()->previous();

            return redirect($target)
                ->withInput($request->except($this->dontFlash))
                ->with('warning', 'Your session expired. Please try again.');
        });
    }
}
