<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Impide que el navegador guarde en cache las pantallas autenticadas.
 * Sin esto, tras cerrar sesion el boton "atras" puede mostrar contenido
 * protegido servido desde el cache del navegador en lugar de pedirlo al
 * servidor (que redirigiria al login).
 */
class NoCacheHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        return $response;
    }
}
