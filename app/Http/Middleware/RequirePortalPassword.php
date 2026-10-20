<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Puts the whole portal behind one shared HTTP Basic Auth password.
 *
 * A stopgap until customers have their own login. It fails closed: outside local
 * and testing environments a missing password means nobody gets in.
 */
class RequirePortalPassword
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $username = (string) config('portal.basic_auth.username');
        $password = (string) config('portal.basic_auth.password');

        if ($password === '') {
            return app()->environment('local', 'testing') ? $next($request) : $this->challenge();
        }

        $isValid = hash_equals($username, (string) $request->getUser())
            && hash_equals($password, (string) $request->getPassword());

        return $isValid ? $next($request) : $this->challenge();
    }

    private function challenge(): Response
    {
        return response('Inloggen vereist.', Response::HTTP_UNAUTHORIZED, [
            'WWW-Authenticate' => 'Basic realm="Voerfit", charset="UTF-8"',
        ]);
    }
}
