<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\HabitTemplate;


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->get('/templates', function () {
    return HabitTemplate::orderBy('category')->orderBy('sort_order')->get();
});
