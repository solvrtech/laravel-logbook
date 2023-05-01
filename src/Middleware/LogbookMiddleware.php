<?php

namespace Solvrtech\Logbook\Middleware;

use Closure;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Solvrtech\Logbook\LogbookConfig;

class LogbookMiddleware
{
    use LogbookConfig;

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     *
     * @return Response|RedirectResponse|JsonResponse
     */
    public function handle(Request $request, Closure $next): Response|RedirectResponse|JsonResponse
    {
        $givenKey = $request->header('x-logbook-key');

        try {
            $logbookKey = $this->getAPIKey();
        } catch (Exception $e) {
            abort(401);
        }

        if (null === $givenKey || $givenKey !== $logbookKey)
            abort(401);

        return $next($request);
    }
}
