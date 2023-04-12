<?php

namespace Solvrtech\Logbook\Middleware;

use Closure;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\RedirectResponse;
use Solvrtech\Logbook\LogbookConfig;

class LogbookMiddleware
{
    use LogbookConfig;

    /**
     * Handle an incoming request.
     * 
     * @param  Request  $request
     * @param  Closure  $next
     * 
     * @return Response|RedirectResponse|JsonResponse
     */
    public function handle(Request $request, Closure $next): Response|RedirectResponse|JsonResponse
    {
        $expectedKey = $request->header('logbook-key');

        try {
            $actualKey = $this->getAPIKey();
        } catch (Exception $e) {
            abort(401);
        }

        if (null === $expectedKey || $expectedKey !== $actualKey)
            abort(401);

        return $next($request);
    }
}
