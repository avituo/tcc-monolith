<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

class ExperimentAuthenticationController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'csrf_token' => csrf_token(),
        ]);
    }
}
