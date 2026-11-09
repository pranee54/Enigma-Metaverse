<?php

use App\Http\Controllers\Web\AttendanceController;
use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\EmployeeController;
use App\Http\Controllers\Web\ExitController;
use App\Http\Controllers\Web\ExportController;
use App\Http\Controllers\Web\LoanController;
use App\Http\Controllers\Web\PayrollController;
use App\Http\Controllers\Web\PayslipController;
use App\Http\Controllers\Web\RevisionController;
use App\Http\Controllers\Web\SettingsController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/payslips', [PayslipController::class, 'index'])->name('payslips.index');
    Route::get('/payslips/{payslip}', [PayslipController::class, 'show'])->name('payslips.show');

    Route::middleware('role:hr|finance|auditor')->group(function () {
        Route::get('/', DashboardController::class)->name('dashboard');

        Route::get('/employees', [EmployeeController::class, 'index'])->name('employees.index');

        Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
        Route::get('/attendance/guide', [AttendanceController::class, 'guide'])->name('attendance.guide');
        Route::get('/attendance/template', [AttendanceController::class, 'template'])->name('attendance.template');
        Route::get('/attendance/{batch}', [AttendanceController::class, 'show'])->name('attendance.show');

        Route::get('/payroll', [PayrollController::class, 'index'])->name('payroll.index');
        Route::get('/payroll/{payroll}', [PayrollController::class, 'show'])->name('payroll.show');

        Route::get('/loans', [LoanController::class, 'index'])->name('loans.index');
        Route::get('/revisions', [RevisionController::class, 'index'])->name('revisions.index');
        Route::get('/exits', [ExitController::class, 'index'])->name('exits.index');
    });

    Route::middleware('role:hr')->group(function () {
        Route::get('/employees/create', [EmployeeController::class, 'create'])->name('employees.create');
        Route::post('/employees', [EmployeeController::class, 'store'])->name('employees.store');
        Route::get('/employees/{employee}/edit', [EmployeeController::class, 'edit'])->name('employees.edit');
        Route::put('/employees/{employee}', [EmployeeController::class, 'update'])->name('employees.update');
        Route::put('/employees/{employee}/salary-structure', [EmployeeController::class, 'updateSalaryStructure'])->name('employees.salary');

        Route::post('/attendance/import', [AttendanceController::class, 'import'])->name('attendance.import');
        Route::post('/attendance/{batch}/approve', [AttendanceController::class, 'approve'])->name('attendance.approve');
        Route::delete('/attendance/{batch}', [AttendanceController::class, 'destroy'])->name('attendance.destroy');

        Route::post('/payroll', [PayrollController::class, 'store'])->name('payroll.store');
        Route::post('/payroll/{payroll}/calculate', [PayrollController::class, 'calculate'])->name('payroll.calculate');
        Route::post('/payroll/{payroll}/validate', [PayrollController::class, 'validateRun'])->name('payroll.validate');
        Route::post('/payroll/{payroll}/submit-review', [PayrollController::class, 'submitReview'])->name('payroll.submit');

        Route::post('/revisions', [RevisionController::class, 'store'])->name('revisions.store');
        Route::post('/exits', [ExitController::class, 'store'])->name('exits.store');
        Route::post('/exits/{exit}/settlement', [ExitController::class, 'settlement'])->name('exits.settlement');
    });

    // show after /create so "create" is not captured as an id
    Route::middleware('role:hr|finance|auditor')->group(function () {
        Route::get('/employees/{employee}', [EmployeeController::class, 'show'])->name('employees.show');
    });

    Route::middleware('role:hr|finance')->group(function () {
        Route::post('/loans', [LoanController::class, 'store'])->name('loans.store');

        Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::put('/settings/configs', [SettingsController::class, 'updateConfigs'])->name('settings.configs');
        Route::get('/settings/statutory/create', [SettingsController::class, 'createStatutory'])->name('settings.statutory.create');
        Route::post('/settings/statutory', [SettingsController::class, 'storeStatutory'])->name('settings.statutory');
        Route::get('/settings/statutory/{statutory}/edit', [SettingsController::class, 'editStatutory'])->name('settings.statutory.edit');
        Route::put('/settings/statutory/{statutory}', [SettingsController::class, 'updateStatutory'])->name('settings.statutory.update');
        Route::delete('/settings/statutory/{statutory}', [SettingsController::class, 'destroyStatutory'])->name('settings.statutory.destroy');
    });

    Route::middleware('role:finance|auditor')->group(function () {
        Route::get('/exports', [ExportController::class, 'index'])->name('exports.index');
        Route::get('/exports/{export}/download', [ExportController::class, 'download'])->name('exports.download');
    });

    Route::middleware('role:finance')->group(function () {
        Route::post('/payroll/{payroll}/approve', [PayrollController::class, 'approve'])->name('payroll.approve');
        Route::post('/payroll/{payroll}/finalise', [PayrollController::class, 'finalise'])->name('payroll.finalise');
        Route::post('/payroll/{payroll}/mark-paid', [PayrollController::class, 'markPaid'])->name('payroll.paid');
        Route::post('/payroll/{payroll}/reconcile', [PayrollController::class, 'reconcile'])->name('payroll.reconcile');

        Route::post('/loans/{loan}/approve', [LoanController::class, 'approve'])->name('loans.approve');
        Route::post('/revisions/{revision}/approve', [RevisionController::class, 'approve'])->name('revisions.approve');

        Route::post('/exports/journal', [ExportController::class, 'journal'])->name('exports.journal');
        Route::post('/exports/bank', [ExportController::class, 'bank'])->name('exports.bank');
    });
});