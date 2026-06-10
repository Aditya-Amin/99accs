<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Convert an authentication exception into a response.
     *
     * Filament/Livewire requests POST to /livewire/update. When the session has
     * expired mid-session, Laravel's default guest redirect records that
     * POST-only URL as the "intended" destination — so after re-login the user
     * is sent there via GET, producing:
     *   "The GET method is not supported for route livewire/update. Supported
     *    methods: POST."
     *
     * For Livewire requests we redirect to login but pin "intended" to the page
     * the request actually came from (the referer), never the Livewire endpoint.
     *
     * @param  \Illuminate\Http\Request  $request
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if ($request->hasHeader('X-Livewire') && ! $this->shouldReturnJson($request, $exception)) {
            $login = $exception->redirectTo() ?? route('login');

            $referer = $request->headers->get('referer');
            if ($referer && $referer !== $request->fullUrl() && $request->hasSession()) {
                // Land back on the real page after login, not /livewire/update.
                $request->session()->put('url.intended', $referer);
            }

            return redirect()->to($login);
        }

        return parent::unauthenticated($request, $exception);
    }
}
