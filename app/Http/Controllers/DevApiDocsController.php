<?php

namespace App\Http\Controllers;

use App\Support\OpenApiDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class DevApiDocsController extends Controller
{
    public function index(): View
    {
        return view('dev.api-docs');
    }

    public function document(OpenApiDocument $document): JsonResponse
    {
        return response()->json($document->build(), headers: [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
