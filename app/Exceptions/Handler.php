<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
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

        // Sessão/CSRF expirado (HTTP 419): em vez da tela crua "Page Expired",
        // volta para o login com um aviso amigável e o e-mail preenchido.
        // O Laravel converte o TokenMismatchException em HttpException(419)
        // antes dos renderables, por isso casamos pelo status 419.
        $this->renderable(function (HttpException $e, Request $request) {
            if ($e->getStatusCode() === 419 && ! $request->expectsJson()) {
                return redirect()
                    ->route('login.create')
                    ->withInput($request->except('_token', 'senha', 'password'))
                    ->withErrors(['login' => 'Sua sessão expirou. Por favor, faça login novamente.']);
            }
        });
    }
}
