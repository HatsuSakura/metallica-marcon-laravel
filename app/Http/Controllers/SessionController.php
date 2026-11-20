<?php

namespace App\Http\Controllers;

use App\Support\Session\SafeReturnPath;
use Illuminate\Http\Request;

class SessionController extends Controller
{
    public function status(Request $request)
    {
        $user = $request->user();
        return response()->json($user ? [
            'authenticated' => true,
            'userId' => $user->id,
            'csrfFingerprint' => hash('sha256', $request->session()->token()),
        ] : ['authenticated' => false], $user ? 200 : 401, ['Cache-Control' => 'no-store, private', 'Vary' => 'Cookie']);
    }

    public function login(Request $request, SafeReturnPath $paths)
    {
        $request->session()->put('url.intended', $paths->resolve($request->query('return_to')));
        return redirect()->route('login', ['expired' => 1]);
    }
}
