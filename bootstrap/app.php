<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        /*
         * ENFAS Agenda V11
         *
         * Webhook Meta/WhatsApp é uma chamada externa
         * server-to-server e não utiliza sessão/CSRF.
         *
         * A autenticidade continua protegida por
         * X-Hub-Signature-256 no controller.
         */
        $middleware->validateCsrfTokens(
            except: [
                'webhooks/meta/whatsapp',
            ],
        );


        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
