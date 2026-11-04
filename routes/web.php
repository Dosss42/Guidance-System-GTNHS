<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\BackupController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\UserStatusController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Counselor\AppointmentController;
use App\Http\Controllers\Counselor\CounselingSessionController;
use App\Http\Controllers\Counselor\CurrentSchoolYearController;
use App\Http\Controllers\Counselor\DashboardController as CounselorDashboardController;
use App\Http\Controllers\Counselor\DocumentController;
use App\Http\Controllers\Counselor\FollowUpController;
use App\Http\Controllers\Counselor\GuidanceCaseController;
use App\Http\Controllers\Counselor\ParentCoordinationController;
use App\Http\Controllers\Counselor\ReferralController;
use App\Http\Controllers\Counselor\ReportController;
use App\Http\Controllers\Counselor\SchoolYearController;
use App\Http\Controllers\Counselor\SectionController;
use App\Http\Controllers\Counselor\StudentController;
use App\Http\Controllers\Counselor\StudentEnrollmentController;
use App\Http\Controllers\Counselor\StudentLookupController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/', HomeController::class)->name('home');
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/account', [AccountController::class, 'show'])->name('account.show');
    Route::put('/account/password', [AccountController::class, 'updatePassword'])
        ->middleware('throttle:6,1')
        ->name('account.password.update');

    Route::middleware('role:administrator')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', AdminDashboardController::class)->name('dashboard');
        Route::resource('users', UserController::class)->only(['index', 'store', 'update']);
        Route::patch('/users/{user}/status', UserStatusController::class)->name('users.status');
        Route::get('/audit-logs', AuditLogController::class)->name('audit-logs.index');

        Route::get('/backups', [BackupController::class, 'index'])->name('backups.index');
        Route::post('/backups', [BackupController::class, 'store'])->middleware('throttle:3,10')->name('backups.store');
        Route::patch('/backups/{name}', [BackupController::class, 'update'])
            ->where('name', 'gtnhs-guidance-backup-[0-9]{8}-[0-9]{6}(-[a-z-]+)?\.zip')
            ->middleware('throttle:10,1')
            ->name('backups.verify');
    });

    Route::middleware('role:guidance_counselor')->prefix('counselor')->name('counselor.')->group(function () {
        Route::get('/', CounselorDashboardController::class)->name('dashboard');

        Route::resource('students', StudentController::class)->except(['destroy']);
        Route::post('/students/{student}/enrollments', [StudentEnrollmentController::class, 'store'])->name('students.enrollments.store');
        Route::patch('/students/{student}/enrollments/{enrollment}', [StudentEnrollmentController::class, 'update'])
            ->scopeBindings()
            ->name('students.enrollments.update');

        Route::get('/school-years', [SchoolYearController::class, 'index'])->name('school-years.index');
        Route::post('/school-years', [SchoolYearController::class, 'store'])->name('school-years.store');
        Route::patch('/school-years/{school_year}', [SchoolYearController::class, 'update'])->name('school-years.update');
        Route::patch('/school-years/{school_year}/current', CurrentSchoolYearController::class)->name('school-years.current');

        Route::post('/sections', [SectionController::class, 'store'])->name('sections.store');
        Route::patch('/sections/{section}', [SectionController::class, 'update'])->name('sections.update');

        Route::get('/student-lookup', StudentLookupController::class)->name('student-lookup');

        Route::resource('appointments', AppointmentController::class)->except(['show', 'destroy']);
        Route::resource('referrals', ReferralController::class)->except(['edit', 'destroy']);
        Route::resource('cases', GuidanceCaseController::class)->except(['edit', 'destroy']);

        Route::post('/cases/{case}/sessions', [CounselingSessionController::class, 'store'])->name('cases.sessions.store');
        Route::get('/sessions/{session}/notes', [CounselingSessionController::class, 'notes'])
            ->middleware('throttle:30,1')
            ->name('sessions.notes');

        Route::get('/follow-ups', [FollowUpController::class, 'index'])->name('follow-ups.index');
        Route::post('/cases/{case}/follow-ups', [FollowUpController::class, 'store'])->name('cases.follow-ups.store');
        Route::patch('/follow-ups/{followUp}', [FollowUpController::class, 'update'])->name('follow-ups.update');

        Route::get('/documents', [DocumentController::class, 'index'])->name('documents.index');
        Route::get('/documents/create', [DocumentController::class, 'create'])->name('documents.create');
        Route::post('/documents', [DocumentController::class, 'store'])->middleware('throttle:20,1')->name('documents.store');
        Route::get('/documents/{document}/download', [DocumentController::class, 'download'])->middleware('throttle:60,1')->name('documents.download');

        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/export', [ReportController::class, 'export'])->middleware('throttle:10,1')->name('reports.export');

        Route::get('/coordination', [ParentCoordinationController::class, 'index'])->name('coordination.index');
        Route::get('/coordination/create', [ParentCoordinationController::class, 'create'])->name('coordination.create');
        Route::post('/coordination', [ParentCoordinationController::class, 'store'])->name('coordination.store');
    });
});
