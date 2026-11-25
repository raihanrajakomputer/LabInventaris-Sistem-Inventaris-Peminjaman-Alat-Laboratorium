<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSessionAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        $authUser = $request->session()->get('auth_user');

        if (! is_array($authUser) || empty($authUser['id']) || empty($authUser['role'])) {
            return redirect()
                ->route('login')
                ->with('info', 'Silakan login terlebih dahulu untuk mengakses aplikasi.');
        }

        return $next($request);
    }
}
