<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Autoriza o acesso ao Log Viewer (opcodesio/log-viewer) via HTTP Basic Auth,
 * usando credenciais dedicadas (LOG_VIEWER_AUTH_USER / LOG_VIEWER_AUTH_PASSWORD)
 * em vez do gate de super admin — funciona igual em qualquer ambiente.
 *
 * Comportamento quando as credenciais NÃO estão configuradas:
 *   - fora de produção (local/dev): acesso liberado, sem prompt;
 *   - em produção: acesso bloqueado (fail-closed) para nunca expor logs por
 *     esquecimento de configurar as credenciais.
 */
class LogViewerBasicAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = config('log-viewer.basic_auth.user');
        $password = config('log-viewer.basic_auth.password');

        if (blank($user) || blank($password)) {
            return app()->isProduction()
                ? $this->unauthorized()
                : $next($request);
        }

        [$givenUser, $givenPassword] = $this->credentials($request);

        if (hash_equals((string) $user, $givenUser)
            && hash_equals((string) $password, $givenPassword)) {
            return $next($request);
        }

        return $this->unauthorized();
    }

    /**
     * Extrai usuário/senha do header Authorization: Basic. Não usamos
     * $request->getUser()/getPassword() porque dependem de PHP_AUTH_USER, que o
     * nginx + php-fpm não populam de forma confiável — parseamos o header direto.
     *
     * @return array{0: string, 1: string}
     */
    protected function credentials(Request $request): array
    {
        $header = (string) $request->header('Authorization', '');

        if (stripos($header, 'basic ') !== 0) {
            return ['', ''];
        }

        $decoded = base64_decode(substr($header, 6), true);

        if ($decoded === false || ! str_contains($decoded, ':')) {
            return ['', ''];
        }

        return explode(':', $decoded, 2);
    }

    protected function unauthorized(): Response
    {
        return response('Unauthorized.', Response::HTTP_UNAUTHORIZED, [
            'WWW-Authenticate' => 'Basic realm="Log Viewer"',
        ]);
    }
}
