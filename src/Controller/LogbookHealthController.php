<?php

namespace Solvrtech\Logbook\Controller;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Solvrtech\Logbook\Service\LogbookHealthService;

class LogbookHealthController
{
    public function __invoke(Request $request, LogbookHealthService $service): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'checks' => 'required|array',
        ]);

        if ($validator->fails())
            abort(403);

        $validated = $validator->validated();

        return response()->json($service->getResults(
            $validated['checks']
        ));
    }
}
