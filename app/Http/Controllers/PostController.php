<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Http;

class PostController extends Controller
{
    public function store()
    {

        $response = Http::withHeaders([
            'accept' => 'application/json',
            'authorization' => 'Bearer ' . auth()->user()->accessToken->access_token
        ])->post(env('API_URL') . '/api/v1/posts', [
            'name' => 'Este es un nombre de prueba',
            'slug' => 'Este-es-un-nombre-de-prueba',
            'extract' => 'asdfasdfasdf',
            'body' => 'asdfasdfasdf',
            'category_id' => '1',
        ]);

        return $response->json();
    }
}
