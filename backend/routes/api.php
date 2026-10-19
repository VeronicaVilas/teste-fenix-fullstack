<?php

use App\Http\Controllers\AttemptController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\StudentDashboardController;
use App\Http\Controllers\StudentExamController;
use App\Http\Controllers\TeacherController;
use Illuminate\Support\Facades\Route;

Route::get('teachers', [TeacherController::class, 'index']);
Route::get('students', [StudentController::class, 'index']);

Route::prefix('teachers/{teacher}')->group(function () {
    Route::get('exams', [ExamController::class, 'index']);
    Route::post('exams', [ExamController::class, 'store']);
    Route::get('exams/{exam}', [ExamController::class, 'show'])->whereNumber('exam');
    Route::put('exams/{exam}', [ExamController::class, 'update'])->whereNumber('exam');
    Route::delete('exams/{exam}', [ExamController::class, 'destroy'])->whereNumber('exam');
    Route::patch('exams/{exam}/publish', [ExamController::class, 'publish'])->whereNumber('exam');
    Route::get('dashboard', [DashboardController::class, 'summary']);
    Route::get('dashboard/exam-averages', [DashboardController::class, 'examAverages']);
    Route::get('dashboard/exams-ranking', [DashboardController::class, 'examsRanking']);
});

Route::prefix('students/{student}')->group(function () {
    Route::get('dashboard', [StudentDashboardController::class, 'summary']);
    Route::get('exams', [StudentExamController::class, 'index']);
    Route::get('exams/{exam}', [StudentExamController::class, 'show'])->whereNumber('exam');
    Route::post('exams/{exam}/attempt', [AttemptController::class, 'store'])->whereNumber('exam');
    Route::get('exams/{exam}/attempt', [AttemptController::class, 'show'])->whereNumber('exam');
});
