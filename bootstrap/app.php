<?php

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Reconhece HTTPS encaminhado pelo tunel local e pela rede Docker.
        $middleware->trustProxies(
            at: ['127.0.0.1', '::1', '10.0.0.0/8', '172.16.0.0/12', '192.168.0.0/16'],
            headers: Request::HEADER_X_FORWARDED_PROTO,
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->report(function (Throwable $e): void {
            if (getenv('VERCEL')) {
                // Registra somente a origem do erro, sem SQL, senhas ou dados pessoais.
                error_log(sprintf('[Laravel] %s at %s:%d', $e::class, basename($e->getFile()), $e->getLine()));
            }
        });
        $exceptions->render(function (QueryException $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }
            $state = $e->errorInfo[0] ?? (string) $e->getCode();
            if (in_array($state, ['23505', '23000'])) {
                return response()->json(['message' => 'Não foi possível salvar: valor duplicado ou vínculo inválido. Confira os campos.'], 409);
            }
            if ($state === '23503') {
                return response()->json(['message' => 'Existem registros vinculados ou um vínculo deixou de existir. Atualize a lista.'], 409);
            }

            return response()->json(['message' => 'Não foi possível acessar os dados. Verifique o serviço do banco e tente novamente.'], 503);
        });
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
