<?php

namespace App\Http\Controllers;

use App\Support\OpenApiDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class DevApiDocsController extends Controller
{
    public function index(OpenApiDocument $document): Response
    {
        $fingerprint = hash('sha256', json_encode($document->build(), JSON_THROW_ON_ERROR));
        return response()->view('dev.api-docs', [
            'specUrl' => route('dev.api-docs.openapi', [], false).'?v='.$fingerprint,
        ])->header('Cache-Control', 'private, no-store');
    }

    public function document(OpenApiDocument $document): JsonResponse
    {
        return response()->json($document->build(), headers: [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
