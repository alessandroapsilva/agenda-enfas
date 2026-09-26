<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequirePermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        abort_unless(
            $user && $user->canAccess($permission),
            403,
            'Você não possui permissão para acessar este recurso.'
        );

        return $next($request);
    }
}
