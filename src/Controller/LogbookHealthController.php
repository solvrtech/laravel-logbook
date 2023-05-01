<?php

namespace Solvrtech\Logbook\Controller;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Solvrtech\Logbook\Service\LogbookHealthService;

class LogbookHealthController
{
    public function __invoke(Request $request, LogbookHealthService $service): JsonResponse
    {
        return response()->json($service->getResults());
    }
}
