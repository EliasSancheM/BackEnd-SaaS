<?php

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::view('/docs', 'docs.swagger');

Route::get('/docs/openapi.yaml', function () {
    return response()->file(base_path('docs/openapi.yaml'), [
        'Content-Type' => 'application/yaml',
    ]);
});

Route::get('/docs/token', function (Request $request) {
    if (! app()->isLocal()) {
        abort(404);
    }

    $token = $request->session()->get('swagger_token');

    if (filled($token)) {
        return response()->json([
            'token' => $token,
        ]);
    }

    $user = User::query()->where('email', 'test@example.com')->firstOrFail();

    return response()->json([
        'token' => $user->createToken('swagger')->plainTextToken,
    ]);
});
