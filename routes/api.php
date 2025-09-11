<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PlantController;
use App\Http\Controllers\PlantUserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Auth routes
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/register', [AuthController::class, 'register'])->name('register');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum')->name('logout');
Route::post('/me', [AuthController::class, 'me'])->middleware('auth:sanctum')->name('me');


// Plant routes
Route::prefix('plants')->group(function () {
    Route::get('/', [PlantController::class, 'index'])->name('plants.index');
    Route::post('/', [PlantController::class, 'store'])->name('plants.store');
    Route::get('/{common_name}', [PlantController::class, 'show'])->name('plants.show');
    Route::put('/{common_name}', [PlantController::class, 'update'])->name('plants.update');
    Route::delete('/{common_name}', [PlantController::class, 'destroy'])->name('plants.destroy');
});

// User_Plant routes (routes where the user interact with plants)
Route::prefix('user/plant')->group(function () {
    Route::post('/', [PlantUserController::class, 'addPlantUser'])->name('user.plant.addPlantUser')->middleware('auth:sanctum');
    Route::delete('/{id}', [PlantUserController::class, 'deletePlantUser'])->name('user.plant.deletePlantUser')->middleware('auth:sanctum');
});
Route::get('/user/plants', [PlantUserController::class, 'getPlantsUser'])->name('user.plant.getPlantsUser')->middleware('auth:sanctum');