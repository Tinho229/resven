<?php

use App\Http\Controllers\Api\User\EquipementController;
use App\Http\Controllers\Api\User\ReservationController;
use App\Http\Controllers\Api\User\SalleController;
use App\Http\Controllers\Api\Responsable\ReservationController as ResponsableReservationController;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\Admin\EquipementController as AdminEquipementController;
use App\Http\Controllers\Api\Admin\ImageController as AdminImageController;
use App\Http\Controllers\Api\Admin\ReservationController as AdminReservationController;
use App\Http\Controllers\Api\Admin\SalleController as AdminSalleController;
use App\Http\Controllers\Api\Admin\UserController as AdminUserController;
use App\Http\Controllers\Api\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Routes publiques
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// Routes protégées par authentification Sanctum
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'me']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Espace d'administration (accessible uniquement avec le rôle 'admin')
    Route::middleware('admin')->prefix('admin')->group(function () {
        Route::get('dashboard', [AdminDashboardController::class, 'index']);
        Route::get('users/role/{role}', [AdminUserController::class, 'byRole']);
        Route::apiResource('users', AdminUserController::class);
        Route::apiResource('salles', AdminSalleController::class);
        Route::get('images/salle/{salleId}', [AdminImageController::class, 'bySalle']);
        Route::apiResource('images', AdminImageController::class);
        Route::apiResource('equipements', AdminEquipementController::class);

        // Gestion des réservations (actions spécifiques & CRUD complet)
        Route::patch('reservations/{id}/confirm', [AdminReservationController::class, 'confirmer']);
        Route::patch('reservations/{id}/reject', [AdminReservationController::class, 'rejeter']);
        Route::patch('reservations/{id}/terminate', [AdminReservationController::class, 'terminer']);
        Route::apiResource('reservations', AdminReservationController::class);
    });
});

Route::get('/salles', [SalleController::class, 'index']);
Route::get('/salles/{salle}', [SalleController::class, 'show']);
Route::get('/salles/{salle}/disponibilites', [SalleController::class, 'disponibilites']);

Route::get('/equipements', [EquipementController::class, 'index']);
Route::get('/equipements/{equipement}', [EquipementController::class, 'show']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/reservations', [ReservationController::class, 'index']);
    Route::post('/reservations', [ReservationController::class, 'store']);
    Route::get('/reservations/{reservation}', [ReservationController::class, 'show']);
    Route::match(['put', 'patch'], '/reservations/{reservation}', [ReservationController::class, 'update']);
    Route::delete('/reservations/{reservation}', [ReservationController::class, 'destroy']);
});

Route::middleware(['auth:sanctum', 'role:responsable'])->prefix('responsable')->group(function () {
    Route::get('/reservations', [ResponsableReservationController::class, 'index']);
    Route::post('/reservations', [ResponsableReservationController::class, 'store']);
    Route::patch('/reservations/{reservation}/confirmer', [ResponsableReservationController::class, 'confirmer']);
    Route::patch('/reservations/{reservation}/rejeter', [ResponsableReservationController::class, 'rejeter']);
    Route::patch('/reservations/{reservation}/annuler', [ResponsableReservationController::class, 'annuler']);
    Route::patch('/reservations/{reservation}/terminer', [ResponsableReservationController::class, 'terminer']);
});


