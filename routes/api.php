<?php

use App\Http\Controllers\LocationController;
use App\Http\Controllers\CategoryController;
use Illuminate\Support\Facades\Route;

Route::get('/provinces', [LocationController::class, 'provinces']);
Route::get('/cities/{provinceId}', [LocationController::class, 'cities']);

// Category API routes
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{id}', [CategoryController::class, 'show']);
Route::get('/categories/{parentId}/subcategories', [CategoryController::class, 'subcategories']);

