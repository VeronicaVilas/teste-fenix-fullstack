<?php

use App\Http\Controllers\ExamController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\TeacherController;
use Illuminate\Support\Facades\Route;

/*
 * API routes are loaded with the "api" middleware group and the "api/v1"
 * prefix configured in bootstrap/app.php. Do not repeat the prefix here.
 */

Route::get('teachers', [TeacherController::class, 'index']);
Route::get('students', [StudentController::class, 'index']);

Route::prefix('teachers/{teacher}')->group(function () {
    Route::post('exams', [ExamController::class, 'store']);
});
