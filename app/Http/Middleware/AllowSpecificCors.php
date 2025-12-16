<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AllowSpecificCors
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Якщо це локальне середовище розробки:
        if (app()->environment('local')) {

            // Перевірка на запит OPTIONS (preflight request)
            if ($request->isMethod('OPTIONS')) {
                return response('', 200)
                    ->header('Access-Control-Allow-Origin', 'http://localhost:5173')
                    ->header('Access-Control-Allow-Credentials', 'true')
                    ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS')
                    // !!! ВИПРАВЛЕННЯ: Додаємо X-XSRF-TOKEN до дозволених заголовків
                    ->header('Access-Control-Allow-Headers', 'Content-Type, X-Inertia, X-CSRF-TOKEN, X-XSRF-TOKEN, X-Requested-With, Accept, Authorization, Origin');
            }

            $response->headers->set('Access-Control-Allow-Origin', 'http://localhost:5173');
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
            $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');

            // !!! ВИПРАВЛЕННЯ: Додаємо X-XSRF-TOKEN до дозволених заголовків
            $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, X-Inertia, X-CSRF-TOKEN, X-XSRF-TOKEN, X-Requested-With, Accept, Authorization, Origin');
        }

        return $response;
    }
}
