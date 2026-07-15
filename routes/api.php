<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\SubscriptionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware('auth:sanctum')->prefix('auth')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);
    Route::post('/profile/update', [AuthController::class, 'updateProfile']);
});

Route::get('/categories', [CategoryController::class, 'index']);

Route::middleware(['auth:sanctum', 'role:admin'])->prefix('categories')->group(function () {
    Route::post('/', [CategoryController::class, 'store']);
    Route::put('/{category}', [CategoryController::class, 'update']);
    Route::delete('/{category}', [CategoryController::class, 'destroy']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/news/feed', [NewsController::class, 'feed']);
    Route::get('/news/saved/list', [NewsController::class, 'saved']);
    Route::post('/news/{news}/save', [NewsController::class, 'save']);
    Route::delete('/news/{news}/save', [NewsController::class, 'unsave']);
    Route::get('/subscriptions', [SubscriptionController::class, 'mine']);
    Route::post('/branches/{branch}/subscribe', [SubscriptionController::class, 'subscribe']);
    Route::delete('/branches/{branch}/subscribe', [SubscriptionController::class, 'unsubscribe']);
    Route::get('/news', [NewsController::class, 'index']);
    Route::get('/news/{news}', [NewsController::class, 'show']);
    Route::get('/branches', [BranchController::class, 'index']);
    Route::get('/branches/{branch}', [BranchController::class, 'show']);
});

Route::middleware(['auth:sanctum', 'role:admin'])->prefix('branches')->group(function () {
    Route::post('/', [BranchController::class, 'store']);
});

Route::middleware(['auth:sanctum', 'role:branch'])->group(function () {
    Route::get('/news/mine/list', [NewsController::class, 'mine']);
    Route::post('/news', [NewsController::class, 'store']);
    Route::put('/news/{news}', [NewsController::class, 'update']);
    Route::post('/news/{news}/update', [NewsController::class, 'update']);
    Route::delete('/news/{news}', [NewsController::class, 'destroy']);
});
