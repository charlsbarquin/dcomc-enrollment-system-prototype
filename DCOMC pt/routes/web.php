<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminAccountController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AdminFeedbackController;
use App\Http\Controllers\AdminRoleSwitchController;
use App\Http\Controllers\AdminSystemController;
use App\Http\Controllers\AdminAuditLogController;
use App\Http\Controllers\AdminActivityLogController;
use App\Http\Controllers\AdminCodeEditorController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\DeanManageProfessorController;
use App\Http\Controllers\DeanScheduleByScopeController;
use App\Http\Controllers\DeanSchedulingController;
use App\Http\Controllers\FinanceMonitoringController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RegistrarController;
use App\Http\Controllers\RegistrarOperationsController;
use App\Http\Controllers\CorScopeController;
use App\Http\Controllers\RegistrarSettingsController;
use App\Http\Controllers\RegistrarScheduleController;
use App\Http\Controllers\BlockManagementController;
use App\Http\Controllers\CorArchiveController;
use App\Http\Controllers\CorViewController;
use App\Http\Controllers\IrregularCorArchiveController;
use App\Http\Controllers\StudentServicesController;
use App\Http\Controllers\StaffAccessController;
use App\Http\Controllers\UnifastAccessController;
use App\Http\Controllers\RegistrarAccessController;
use App\Http\Controllers\SchoolYearSelectorController;
use Illuminate\Support\Facades\Auth;

// --- LOGIN PORTALS ---
Route::get('/', function () { return view('auth.login-student'); })->name('login');
Route::get('/dcomc-login', function () { return view('auth.login-dcomc'); })->name('login.dcomc');
Route::get('/admin-login', function () { return view('auth.login-admin'); })->name('login.admin');

// --- ADMIN ROUTES ---
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/admin/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
    Route::get('/admin/analytics', [AnalyticsController::class, 'index'])->name('admin.analytics');
    Route::get('/admin/analytics/print', [AnalyticsController::class, 'printAnalytics'])->name('admin.analytics.print');
    Route::get('/admin/analytics/export', [AnalyticsController::class, 'exportCsv'])->name('admin.analytics.export');
    Route::get('/admin/accounts', [AdminAccountController::class, 'index'])->name('admin.accounts');
    Route::get('/admin/accounts/create-student', [AdminAccountController::class, 'createStudent'])->name('admin.accounts.create.student');
    Route::post('/admin/accounts/store-student', [AdminAccountController::class, 'storeStudent'])->name('admin.accounts.store.student');
    Route::post('/admin/accounts/store-dcomc', [AdminAccountController::class, 'storeDcomc'])->name('admin.accounts.store.dcomc');
    Route::post('/admin/accounts', [AdminAccountController::class, 'store'])->name('admin.accounts.store');
    Route::put('/admin/accounts/{id}', [AdminAccountController::class, 'update'])->name('admin.accounts.update');
    Route::delete('/admin/accounts/{id}', [AdminAccountController::class, 'destroy'])->name('admin.accounts.destroy');
    Route::get('/admin/student-status', [AdminAccountController::class, 'studentStatus'])->name('admin.student-status');
    Route::patch('/admin/student-status/{id}/record', [AdminAccountController::class, 'updateStudentStatusRecord'])->name('admin.student-status.update-record');
    Route::patch('/admin/student-status/{id}/enroll', [AdminAccountController::class, 'enrollApplication'])->name('admin.student-status.enroll');
    Route::patch('/admin/student-status/{id}/reject', [AdminAccountController::class, 'rejectApplication'])->name('admin.student-status.reject');
    Route::patch('/admin/student-status/{id}/needs-correction', [AdminAccountController::class, 'markNeedsCorrection'])->name('admin.student-status.needs-correction');
    Route::delete('/admin/student-status/{id}', [AdminAccountController::class, 'deleteApplication'])->name('admin.student-status.delete');
    Route::get('/admin/blocks', [RegistrarOperationsController::class, 'blocks'])->name('admin.blocks');
    Route::delete('/admin/blocks/{id}', [RegistrarOperationsController::class, 'deleteBlock'])->name('admin.blocks.delete');
    Route::get('/admin/block-change-requests', [RegistrarOperationsController::class, 'blockChangeRequests'])->name('admin.block-change-requests');
    Route::patch('/admin/block-change-requests/{id}/approve', [RegistrarOperationsController::class, 'approveBlockChange'])->name('admin.block-change-requests.approve');
    Route::patch('/admin/block-change-requests/{id}/reject', [RegistrarOperationsController::class, 'rejectBlockChange'])->name('admin.block-change-requests.reject');
    Route::get('/admin/reports', [ReportController::class, 'index'])->name('admin.reports');
    Route::get('/admin/reports/print', [ReportController::class, 'printReport'])->name('admin.reports.print');
    Route::get('/admin/reports/export', [ReportController::class, 'export'])->name('admin.reports.export');
    Route::get('/admin/settings/staff-access', [StaffAccessController::class, 'index'])->name('admin.settings.staff-access');
    Route::post('/admin/settings/staff-access', [StaffAccessController::class, 'update'])->name('admin.settings.staff-access.update');
    Route::get('/admin/settings/staff-access/users/{user}', [StaffAccessController::class, 'showUser'])->name('admin.settings.staff-access.user');
    Route::post('/admin/settings/staff-access/users/{user}', [StaffAccessController::class, 'updateUser'])->name('admin.settings.staff-access.user.update');

    Route::get('/admin/settings/registrar-access', [RegistrarAccessController::class, 'index'])->name('admin.settings.registrar-access');
    Route::post('/admin/settings/registrar-access', [RegistrarAccessController::class, 'update'])->name('admin.settings.registrar-access.update');

    // Admin: all registrar-style settings (same controllers as registrar)
    Route::get('/admin/settings/school-years', [RegistrarSettingsController::class, 'schoolYears'])->name('admin.settings.school-years');
    Route::post('/admin/settings/school-years/generate', [RegistrarSettingsController::class, 'generateSchoolYear'])->name('admin.settings.school-years.generate');
    Route::post('/admin/settings/academic-calendar', [RegistrarSettingsController::class, 'storeAcademicCalendar'])->name('admin.settings.academic-calendar.store');
    Route::delete('/admin/settings/school-years/clear', [RegistrarSettingsController::class, 'clearSchoolYears'])->name('admin.settings.school-years.clear');

    Route::get('/admin/settings/semesters', [RegistrarSettingsController::class, 'semesters'])->name('admin.settings.semesters');
    Route::post('/admin/settings/semesters', [RegistrarSettingsController::class, 'storeSemester'])->name('admin.settings.semesters.store');
    Route::patch('/admin/settings/semesters/{id}/toggle', [RegistrarSettingsController::class, 'toggleSemester'])->name('admin.settings.semesters.toggle');

    Route::get('/admin/settings/year-levels', [RegistrarSettingsController::class, 'yearLevels'])->name('admin.settings.year-levels');
    Route::post('/admin/settings/year-levels', [RegistrarSettingsController::class, 'storeYearLevel'])->name('admin.settings.year-levels.store');
    Route::patch('/admin/settings/year-levels/{id}/toggle', [RegistrarSettingsController::class, 'toggleYearLevel'])->name('admin.settings.year-levels.toggle');

    Route::get('/admin/settings/blocks', [RegistrarSettingsController::class, 'blocks'])->name('admin.settings.blocks');
    Route::post('/admin/settings/blocks', [RegistrarSettingsController::class, 'storeBlock'])->name('admin.settings.blocks.store');
    Route::patch('/admin/settings/blocks/{id}/toggle', [RegistrarSettingsController::class, 'toggleBlock'])->name('admin.settings.blocks.toggle');

    Route::get('/admin/settings/subjects', [RegistrarSettingsController::class, 'subjects'])->name('admin.settings.subjects');
    Route::post('/admin/settings/subjects', [RegistrarSettingsController::class, 'storeSubject'])->name('admin.settings.subjects.store');
    Route::post('/admin/settings/subjects/push', [RegistrarSettingsController::class, 'pushSubjectsToDeanSchedule'])->name('admin.settings.subjects.push');
    Route::patch('/admin/settings/subjects/{id}/toggle', [RegistrarSettingsController::class, 'toggleSubject'])->name('admin.settings.subjects.toggle');
    Route::post('/admin/settings/raw-subjects', [RegistrarSettingsController::class, 'storeRawSubject'])->name('admin.settings.raw-subjects.store');
    Route::patch('/admin/settings/raw-subjects/{id}/toggle', [RegistrarSettingsController::class, 'toggleRawSubject'])->name('admin.settings.raw-subjects.toggle');

    Route::get('/admin/settings/fees', [RegistrarSettingsController::class, 'fees'])->name('admin.settings.fees');
    Route::post('/admin/settings/fees', [RegistrarSettingsController::class, 'storeFee'])->name('admin.settings.fees.store');
    Route::post('/admin/settings/fees/table', [RegistrarSettingsController::class, 'updateFeeTable'])->name('admin.settings.fees.table');
    Route::post('/admin/settings/fees/copy-from-raw', [RegistrarSettingsController::class, 'copyFeeFromRaw'])->name('admin.settings.fees.copy-from-raw');
    Route::patch('/admin/settings/fees/{id}/toggle', [RegistrarSettingsController::class, 'toggleFee'])->name('admin.settings.fees.toggle');
    Route::delete('/admin/settings/fees/{id}', [RegistrarSettingsController::class, 'destroyFee'])->name('admin.settings.fees.destroy');
    Route::post('/admin/settings/raw-fees', [RegistrarSettingsController::class, 'storeRawFee'])->name('admin.settings.raw-fees.store');

    Route::get('/admin/settings/professors', [RegistrarSettingsController::class, 'professors'])->name('admin.settings.professors');
    Route::post('/admin/settings/professors', [RegistrarSettingsController::class, 'storeProfessor'])->name('admin.settings.professors.store');
    Route::get('/admin/settings/rooms', [RegistrarSettingsController::class, 'rooms'])->name('admin.settings.rooms');
    Route::post('/admin/settings/rooms', [RegistrarSettingsController::class, 'storeRoom'])->name('admin.settings.rooms.store');

    Route::get('/admin/settings/unifast-access', [UnifastAccessController::class, 'index'])->name('admin.settings.unifast-access');
    Route::post('/admin/settings/unifast-access', [UnifastAccessController::class, 'update'])->name('admin.settings.unifast-access.update');
    Route::get('/admin/settings/unifast-access/users/{user}', [UnifastAccessController::class, 'showUser'])->name('admin.settings.unifast-access.user');
    Route::post('/admin/settings/unifast-access/users/{user}', [UnifastAccessController::class, 'updateUser'])->name('admin.settings.unifast-access.user.update');

    Route::get('/admin/settings/cor-scopes', [CorScopeController::class, 'index'])->name('admin.settings.cor-scopes.index');
    Route::get('/admin/settings/cor-scopes/create', [CorScopeController::class, 'create'])->name('admin.settings.cor-scopes.create');
    Route::post('/admin/settings/cor-scopes', [CorScopeController::class, 'store'])->name('admin.settings.cor-scopes.store');
    Route::get('/admin/settings/cor-scopes/{id}/edit', [CorScopeController::class, 'edit'])->name('admin.settings.cor-scopes.edit');
    Route::put('/admin/settings/cor-scopes/{id}', [CorScopeController::class, 'update'])->name('admin.settings.cor-scopes.update');
    Route::delete('/admin/settings/cor-scopes/{id}', [CorScopeController::class, 'destroy'])->name('admin.settings.cor-scopes.destroy');

    Route::post('/admin/role-switch', [AdminRoleSwitchController::class, 'start'])->name('admin.role-switch.start');

    Route::get('/admin/feedback', [AdminFeedbackController::class, 'index'])->name('admin.feedback.index');
    Route::get('/admin/feedback/create', [FeedbackController::class, 'create'])->name('admin.feedback.create');
    Route::post('/admin/feedback', [FeedbackController::class, 'store'])->name('admin.feedback.store');
    Route::delete('/admin/feedback/{feedback}', [AdminFeedbackController::class, 'destroy'])->name('admin.feedback.destroy');

    // System monitoring / maintenance (admin-only)
    Route::get('/admin/system', [AdminSystemController::class, 'overview'])->name('admin.system.overview');
    Route::get('/admin/system/backup', [AdminSystemController::class, 'backup'])->name('admin.system.backup');
    Route::post('/admin/system/backup/toggle', [AdminSystemController::class, 'toggleBackup'])->name('admin.system.backup.toggle');
    Route::get('/admin/system/backup/download', [AdminSystemController::class, 'downloadBackup'])->name('admin.system.backup.download');
    Route::get('/admin/system/logs', [AdminSystemController::class, 'logs'])->name('admin.system.logs');
    Route::post('/admin/system/logs/clear', [AdminSystemController::class, 'clearLogs'])->name('admin.system.logs.clear');

    Route::get('/admin/system/failed-jobs', [AdminSystemController::class, 'failedJobs'])->name('admin.system.failed-jobs');
    Route::post('/admin/system/failed-jobs/{id}/retry', [AdminSystemController::class, 'retryFailedJob'])->name('admin.system.failed-jobs.retry');
    Route::delete('/admin/system/failed-jobs/{id}', [AdminSystemController::class, 'forgetFailedJob'])->name('admin.system.failed-jobs.forget');

    Route::get('/admin/system/maintenance', [AdminSystemController::class, 'maintenance'])->name('admin.system.maintenance');
    Route::post('/admin/system/maintenance/down', [AdminSystemController::class, 'down'])->name('admin.system.maintenance.down');
    Route::post('/admin/system/maintenance/up', [AdminSystemController::class, 'up'])->name('admin.system.maintenance.up');

    // Admin in-browser code editor (restricted)
    Route::get('/admin/system/editor', [AdminCodeEditorController::class, 'index'])->name('admin.system.editor');
    Route::get('/admin/system/editor/api/tree', [AdminCodeEditorController::class, 'tree'])->name('admin.system.editor.api.tree');
    Route::get('/admin/system/editor/api/file', [AdminCodeEditorController::class, 'readFile'])->name('admin.system.editor.api.file');
    Route::post('/admin/system/editor/api/file', [AdminCodeEditorController::class, 'writeFile'])->name('admin.system.editor.api.file.write');
    Route::post('/admin/system/editor/api/create', [AdminCodeEditorController::class, 'create'])->name('admin.system.editor.api.create');
    Route::post('/admin/system/editor/api/rename', [AdminCodeEditorController::class, 'rename'])->name('admin.system.editor.api.rename');
    Route::post('/admin/system/editor/api/delete', [AdminCodeEditorController::class, 'delete'])->name('admin.system.editor.api.delete');

    Route::get('/admin/audit-logs', [AdminAuditLogController::class, 'index'])->name('admin.audit-logs.index');
    Route::get('/admin/activity-logs', [AdminActivityLogController::class, 'index'])->name('admin.activity-logs.index');
});

Route::middleware(['auth'])->group(function () {
    Route::delete('/admin/role-switch', [AdminRoleSwitchController::class, 'stop'])->name('admin.role-switch.stop');
    // School year selector: staff roles can set session filter (registrar, admin, dean, unifast, staff)
    Route::post('/set-school-year', [SchoolYearSelectorController::class, 'set'])->name('set-school-year');
});

// --- REGISTRAR ROUTES ---
Route::middleware(['auth', 'role:registrar'])->group(function () {
    Route::get('/registrar/dashboard', [AdminDashboardController::class, 'index'])->name('registrar.dashboard');
    Route::get('/registrar/analytics', [AnalyticsController::class, 'index'])->name('registrar.analytics');
    Route::get('/registrar/analytics/print', [AnalyticsController::class, 'printAnalytics'])->name('registrar.analytics.print');
    Route::get('/registrar/analytics/export', [AnalyticsController::class, 'exportCsv'])->name('registrar.analytics.export');
    Route::get('/registrar/student-status', [AdminAccountController::class, 'studentStatus'])->name('registrar.student-status');
    Route::patch('/registrar/student-status/{id}/record', [AdminAccountController::class, 'updateStudentStatusRecord'])->name('registrar.student-status.update-record');
    Route::patch('/registrar/student-status/{id}/enroll', [AdminAccountController::class, 'enrollApplication'])->name('registrar.student-status.enroll');
    Route::patch('/registrar/student-status/{id}/reject', [AdminAccountController::class, 'rejectApplication'])->name('registrar.student-status.reject');
    Route::patch('/registrar/student-status/{id}/needs-correction', [AdminAccountController::class, 'markNeedsCorrection'])->name('registrar.student-status.needs-correction');
    Route::delete('/registrar/student-status/{id}', [AdminAccountController::class, 'deleteApplication'])->name('registrar.student-status.delete');
    Route::get('/registrar/reports', [ReportController::class, 'index'])->name('registrar.reports');
    Route::get('/registrar/reports/print', [ReportController::class, 'printReport'])->name('registrar.reports.print');
    Route::get('/registrar/reports/export', [ReportController::class, 'export'])->name('registrar.reports.export');
    
    // --- SPLIT REGISTRATION ROUTES ---
    Route::get('/registrar/registration/manual', [RegistrarController::class, 'manualRegistration'])->name('registrar.registration.manual');
    Route::post('/registrar/registration/manual', [RegistrarController::class, 'storeManualRegistration'])->name('registrar.registration.manual.store');
    Route::post('/registrar/registration/manual/import', [RegistrarController::class, 'importManualRegistration'])->name('registrar.registration.manual.import');
    Route::get('/registrar/registration/manual/import/template', [RegistrarController::class, 'downloadImportTemplate'])->name('registrar.registration.manual.import.template');
    Route::get('/registrar/registration/builder', [RegistrarController::class, 'formBuilder'])->name('registrar.registration.builder');
    Route::get('/registrar/registration/builder/new', [RegistrarController::class, 'createForm'])->name('registrar.registration.create-form');
    Route::get('/registrar/registration/builder/{id}', [RegistrarController::class, 'editForm'])->name('registrar.registration.edit-form');
    Route::get('/registrar/registration/responses', [RegistrarController::class, 'responses'])->name('registrar.registration.responses');
    Route::get('/registrar/registration/responses/folder/{id}', [RegistrarController::class, 'responseFolder'])->name('registrar.registration.responses.folder');
    
    // API Routes for Form Builder & Settings
    Route::get('/registrar/registration/get-form/{id}', [RegistrarController::class, 'getForm'])->name('registrar.get-form');
    Route::post('/registrar/registration/save-form', [RegistrarController::class, 'saveForm'])->name('registrar.save-form');
    Route::delete('/registrar/registration/delete-form/{id}', [RegistrarController::class, 'deleteForm'])->name('registrar.delete-form');
    
    // Global Toggle and Form Deployment Mapping
    Route::post('/registrar/registration/toggle-global', [RegistrarController::class, 'toggleGlobalEnrollment'])->name('registrar.toggle-global');
    Route::post('/registrar/registration/deploy-form', [RegistrarController::class, 'deployForm'])->name('registrar.deploy-form');
    Route::patch('/registrar/registration/responses/{id}/approve', [RegistrarController::class, 'approveResponse'])->name('registrar.responses.approve');
    Route::patch('/registrar/registration/responses/{id}/reject', [RegistrarController::class, 'rejectResponse'])->name('registrar.responses.reject');
    
    Route::get('/registrar/students', [RegistrarController::class, 'students'])->name('registrar.students');
    Route::get('/registrar/irregularities', [RegistrarController::class, 'irregularities'])->name('registrar.irregularities');
    Route::get('/registrar/irregularities/{user}/schedule', [RegistrarController::class, 'irregularSchedule'])->name('registrar.irregularities.schedule');
    Route::delete('/registrar/irregularities/schedule/record/{record}', [RegistrarController::class, 'removeIrregularScheduleSubject'])->name('registrar.irregularities.schedule.remove-subject');
    Route::get('/registrar/irregular-cor-archive', [IrregularCorArchiveController::class, 'index'])->name('registrar.irregular-cor-archive.index');
    Route::get('/registrar/irregular-cor-archive/{date}/{deployedBy}', [IrregularCorArchiveController::class, 'show'])->name('registrar.irregular-cor-archive.show')->where('date', '[0-9]{4}-[0-9]{2}-[0-9]{2}');
    Route::get('/registrar/cor-archive', [CorArchiveController::class, 'index'])->name('registrar.cor.archive.index');
    Route::get('/registrar/cor-archive/program/{programId}', [CorArchiveController::class, 'program'])->name('registrar.cor.archive.program');
    Route::get('/registrar/cor-archive/program/{programId}/year/{yearLevel}', [CorArchiveController::class, 'year'])->where('yearLevel', '[^/]+')->name('registrar.cor.archive.year');
    Route::get('/registrar/cor-archive/{programId}/{yearLevel}/{semester}', [CorArchiveController::class, 'show'])->where(['yearLevel' => '[^/]+', 'semester' => '[^/]+'])->name('registrar.cor.archive.show');
    Route::post('/registrar/cor-archive/delete-block', [CorArchiveController::class, 'deleteBlockArchive'])->name('registrar.cor.archive.delete-block');
    Route::get('/registrar/blocks', [RegistrarOperationsController::class, 'blocks'])->name('registrar.blocks');
    Route::get('/registrar/block-change-requests', [RegistrarOperationsController::class, 'blockChangeRequests'])->name('registrar.block-change-requests');
    Route::patch('/registrar/block-change-requests/{id}/approve', [RegistrarOperationsController::class, 'approveBlockChange'])->name('registrar.block-change-requests.approve');
    Route::patch('/registrar/block-change-requests/{id}/reject', [RegistrarOperationsController::class, 'rejectBlockChange'])->name('registrar.block-change-requests.reject');

    // --- ADVANCED BLOCK MANAGEMENT (Block Explorer, Transfer, Rebalance, Promotion) ---
    Route::get('/registrar/block-explorer', [BlockManagementController::class, 'blockExplorer'])->name('registrar.block-explorer');
    Route::get('/registrar/students-explorer', [BlockManagementController::class, 'studentsExplorer'])->name('registrar.students-explorer');
    Route::get('/registrar/students-explorer/students', [BlockManagementController::class, 'allStudents'])->name('registrar.students-explorer.students');
    Route::get('/registrar/block-explorer/tree', [BlockManagementController::class, 'tree'])->name('registrar.block-explorer.tree');
    Route::get('/registrar/block-explorer/blocks/{block}/students', [BlockManagementController::class, 'blockStudents'])->name('registrar.block-explorer.block-students');
    Route::get('/registrar/block-explorer/blocks/{block}/print-all-cor', [CorViewController::class, 'printBlockCors'])->name('registrar.block-explorer.print-all-cor');
    Route::get('/registrar/block-explorer/blocks/{block}/print-master-list', [BlockManagementController::class, 'printBlockMasterList'])->name('registrar.block-explorer.print-master-list');
    Route::get('/registrar/students/{user}/cor', [CorViewController::class, 'viewStudentCor'])->name('registrar.students.cor');
    Route::get('/registrar/students-explorer/students/{user}/block-assignments', [BlockManagementController::class, 'blockAssignments'])->name('registrar.students-explorer.block-assignments');
    Route::post('/registrar/block-explorer/assign-irregular', [BlockManagementController::class, 'assignIrregularToBlock'])->name('registrar.block-explorer.assign-irregular');
    Route::delete('/registrar/block-explorer/assign-irregular', [BlockManagementController::class, 'removeIrregularFromBlock'])->name('registrar.block-explorer.remove-irregular');
    Route::post('/registrar/blocks/transfer', [BlockManagementController::class, 'transfer'])->name('registrar.blocks.transfer');
    Route::post('/registrar/blocks/rebalance', [BlockManagementController::class, 'rebalance'])->name('registrar.blocks.rebalance');
    Route::post('/registrar/blocks/promotion', [BlockManagementController::class, 'promotion'])->name('registrar.blocks.promotion');
    Route::get('/registrar/blocks/transfer-log', [BlockManagementController::class, 'transferLog'])->name('registrar.blocks.transfer-log');

    // --- REGISTRAR SETTINGS ---
    Route::get('/registrar/settings/school-years', [RegistrarSettingsController::class, 'schoolYears'])->name('registrar.settings.school-years');
    Route::post('/registrar/settings/school-years/generate', [RegistrarSettingsController::class, 'generateSchoolYear'])->name('registrar.settings.school-years.generate');
    Route::post('/registrar/settings/academic-calendar', [RegistrarSettingsController::class, 'storeAcademicCalendar'])->name('registrar.settings.academic-calendar.store');
    Route::delete('/registrar/settings/school-years/clear', [RegistrarSettingsController::class, 'clearSchoolYears'])->name('registrar.settings.school-years.clear');

    Route::get('/registrar/settings/semesters', [RegistrarSettingsController::class, 'semesters'])->name('registrar.settings.semesters');
    Route::post('/registrar/settings/semesters', [RegistrarSettingsController::class, 'storeSemester'])->name('registrar.settings.semesters.store');
    Route::patch('/registrar/settings/semesters/{id}/toggle', [RegistrarSettingsController::class, 'toggleSemester'])->name('registrar.settings.semesters.toggle');

    Route::get('/registrar/settings/year-levels', [RegistrarSettingsController::class, 'yearLevels'])->name('registrar.settings.year-levels');
    Route::post('/registrar/settings/year-levels', [RegistrarSettingsController::class, 'storeYearLevel'])->name('registrar.settings.year-levels.store');
    Route::patch('/registrar/settings/year-levels/{id}/toggle', [RegistrarSettingsController::class, 'toggleYearLevel'])->name('registrar.settings.year-levels.toggle');

    Route::get('/registrar/settings/blocks', [RegistrarSettingsController::class, 'blocks'])->name('registrar.settings.blocks');
    Route::post('/registrar/settings/blocks', [RegistrarSettingsController::class, 'storeBlock'])->name('registrar.settings.blocks.store');
    Route::patch('/registrar/settings/blocks/{id}/toggle', [RegistrarSettingsController::class, 'toggleBlock'])->name('registrar.settings.blocks.toggle');

    Route::get('/registrar/settings/subjects', [RegistrarSettingsController::class, 'subjects'])->name('registrar.settings.subjects');
    Route::post('/registrar/settings/subjects', [RegistrarSettingsController::class, 'storeSubject'])->name('registrar.settings.subjects.store');
    Route::post('/registrar/settings/subjects/push', [RegistrarSettingsController::class, 'pushSubjectsToDeanSchedule'])->name('registrar.settings.subjects.push');
    Route::patch('/registrar/settings/subjects/{id}/toggle', [RegistrarSettingsController::class, 'toggleSubject'])->name('registrar.settings.subjects.toggle');
    Route::post('/registrar/settings/raw-subjects', [RegistrarSettingsController::class, 'storeRawSubject'])->name('registrar.settings.raw-subjects.store');
    Route::patch('/registrar/settings/raw-subjects/{id}/toggle', [RegistrarSettingsController::class, 'toggleRawSubject'])->name('registrar.settings.raw-subjects.toggle');

    Route::get('/registrar/settings/fees', [RegistrarSettingsController::class, 'fees'])->name('registrar.settings.fees');
    Route::post('/registrar/settings/fees', [RegistrarSettingsController::class, 'storeFee'])->name('registrar.settings.fees.store');
    Route::post('/registrar/settings/fees/table', [RegistrarSettingsController::class, 'updateFeeTable'])->name('registrar.settings.fees.table');
    Route::post('/registrar/settings/fees/copy-from-raw', [RegistrarSettingsController::class, 'copyFeeFromRaw'])->name('registrar.settings.fees.copy-from-raw');
    Route::patch('/registrar/settings/fees/{id}/toggle', [RegistrarSettingsController::class, 'toggleFee'])->name('registrar.settings.fees.toggle');
    Route::delete('/registrar/settings/fees/{id}', [RegistrarSettingsController::class, 'destroyFee'])->name('registrar.settings.fees.destroy');
    Route::post('/registrar/settings/raw-fees', [RegistrarSettingsController::class, 'storeRawFee'])->name('registrar.settings.raw-fees.store');
    Route::get('/registrar/settings/professors', [RegistrarSettingsController::class, 'professors'])->name('registrar.settings.professors');
    Route::post('/registrar/settings/professors', [RegistrarSettingsController::class, 'storeProfessor'])->name('registrar.settings.professors.store');
    Route::get('/registrar/settings/rooms', [RegistrarSettingsController::class, 'rooms'])->name('registrar.settings.rooms');
    Route::post('/registrar/settings/rooms', [RegistrarSettingsController::class, 'storeRoom'])->name('registrar.settings.rooms.store');

    Route::get('/registrar/settings/staff-access', [StaffAccessController::class, 'index'])->name('registrar.settings.staff-access');
    Route::post('/registrar/settings/staff-access', [StaffAccessController::class, 'update'])->name('registrar.settings.staff-access.update');
    Route::get('/registrar/settings/staff-access/users/{user}', [StaffAccessController::class, 'showUser'])->name('registrar.settings.staff-access.user');
    Route::post('/registrar/settings/staff-access/users/{user}', [StaffAccessController::class, 'updateUser'])->name('registrar.settings.staff-access.user.update');

    Route::get('/registrar/settings/unifast-access', [UnifastAccessController::class, 'index'])->name('registrar.settings.unifast-access');
    Route::post('/registrar/settings/unifast-access', [UnifastAccessController::class, 'update'])->name('registrar.settings.unifast-access.update');
    Route::get('/registrar/settings/unifast-access/users/{user}', [UnifastAccessController::class, 'showUser'])->name('registrar.settings.unifast-access.user');
    Route::post('/registrar/settings/unifast-access/users/{user}', [UnifastAccessController::class, 'updateUser'])->name('registrar.settings.unifast-access.user.update');

    // --- REGISTRAR SCHEDULE FORMS ---
    Route::get('/registrar/cor-scopes', [CorScopeController::class, 'index'])->name('registrar.cor-scopes.index');
    Route::get('/registrar/cor-scopes/create', [CorScopeController::class, 'create'])->name('registrar.cor-scopes.create');
    Route::post('/registrar/cor-scopes', [CorScopeController::class, 'store'])->name('registrar.cor-scopes.store');
    Route::get('/registrar/cor-scopes/{id}/edit', [CorScopeController::class, 'edit'])->name('registrar.cor-scopes.edit');
    Route::put('/registrar/cor-scopes/{id}', [CorScopeController::class, 'update'])->name('registrar.cor-scopes.update');
    Route::delete('/registrar/cor-scopes/{id}', [CorScopeController::class, 'destroy'])->name('registrar.cor-scopes.destroy');

    Route::get('/registrar/program-schedule', [RegistrarScheduleController::class, 'programSchedule'])->name('registrar.program-schedule.index');
    Route::post('/registrar/program-schedule/save', [RegistrarScheduleController::class, 'saveProgramSchedule'])->name('registrar.program-schedule.save');
    Route::get('/registrar/schedule/subjects', [RegistrarScheduleController::class, 'subjectsForScope'])->name('registrar.schedule.subjects');
    Route::get('/registrar/schedule/forms', function () { return redirect()->route('registrar.irregularities', ['tab' => 'create-schedule']); })->name('registrar.schedule.templates.index');
    Route::get('/registrar/schedule/forms/{id}/edit', [RegistrarScheduleController::class, 'edit'])->name('registrar.schedule.templates.edit');
    Route::post('/registrar/schedule/forms', [RegistrarScheduleController::class, 'store'])->name('registrar.schedule.templates.store');
    Route::patch('/registrar/schedule/forms/{id}', [RegistrarScheduleController::class, 'update'])->name('registrar.schedule.templates.update');
    Route::post('/registrar/schedule/forms/{id}/subjects', [RegistrarScheduleController::class, 'updateSubjects'])->name('registrar.schedule.templates.subjects');
    Route::delete('/registrar/schedule/forms/{id}', [RegistrarScheduleController::class, 'destroy'])->name('registrar.schedule.templates.destroy');
    Route::get('/registrar/schedule/forms/students-search', [RegistrarScheduleController::class, 'studentsSearch'])->name('registrar.schedule.templates.students-search');
    Route::get('/registrar/schedule/forms/subjects-for-program', [RegistrarScheduleController::class, 'subjectsForProgram'])->name('registrar.schedule.templates.subjects-for-program');
    Route::get('/registrar/schedule/forms/subjects-for-all-programs', [RegistrarScheduleController::class, 'subjectsForAllPrograms'])->name('registrar.schedule.templates.subjects-for-all-programs');
    Route::get('/registrar/schedule/forms/slots-for-scope', [RegistrarScheduleController::class, 'slotsForScope'])->name('registrar.schedule.templates.slots-for-scope');
    Route::post('/registrar/schedule/forms/{id}/conflicts', [RegistrarScheduleController::class, 'conflicts'])->name('registrar.schedule.templates.conflicts');
    Route::post('/registrar/schedule/forms/{id}/deploy', [RegistrarScheduleController::class, 'deploy'])->name('registrar.schedule.templates.deploy');
    Route::post('/registrar/schedule/forms/{id}/undeploy', [RegistrarScheduleController::class, 'undeploy'])->name('registrar.schedule.templates.undeploy');

    Route::get('/registrar/feedback', [FeedbackController::class, 'create'])->name('registrar.feedback');
    Route::post('/registrar/feedback', [FeedbackController::class, 'store'])->name('registrar.feedback.store');
});

// --- OTHER DASHBOARD ROUTES ---
Route::middleware(['auth', 'role:staff'])->group(function () {
    Route::get('/staff/dashboard', [AdminDashboardController::class, 'index'])->name('staff.dashboard');
    Route::get('/staff/student-status', [AdminAccountController::class, 'studentStatus'])->name('staff.student-status');

    // Admission
    Route::get('/staff/registration/manual', [RegistrarController::class, 'manualRegistration'])->name('staff.registration.manual');
    Route::post('/staff/registration/manual', [RegistrarController::class, 'storeManualRegistration'])->name('staff.registration.manual.store');
    Route::post('/staff/registration/manual/import', [RegistrarController::class, 'importManualRegistration'])->name('staff.registration.manual.import');
    Route::get('/staff/registration/manual/import/template', [RegistrarController::class, 'downloadImportTemplate'])->name('staff.registration.manual.import.template');
    Route::get('/staff/registration/builder', [RegistrarController::class, 'formBuilder'])->name('staff.registration.builder');
    Route::get('/staff/registration/builder/new', [RegistrarController::class, 'createForm'])->name('staff.registration.create-form');
    Route::get('/staff/registration/builder/{id}', [RegistrarController::class, 'editForm'])->name('staff.registration.edit-form');
    Route::get('/staff/registration/responses', [RegistrarController::class, 'responses'])->name('staff.registration.responses');
    Route::get('/staff/registration/responses/folder/{id}', [RegistrarController::class, 'responseFolder'])->name('staff.registration.responses.folder');
    Route::get('/staff/registration/get-form/{id}', [RegistrarController::class, 'getForm'])->name('staff.get-form');
    Route::post('/staff/registration/save-form', [RegistrarController::class, 'saveForm'])->name('staff.save-form');
    Route::delete('/staff/registration/delete-form/{id}', [RegistrarController::class, 'deleteForm'])->name('staff.delete-form');
    Route::post('/staff/registration/toggle-global', [RegistrarController::class, 'toggleGlobalEnrollment'])->name('staff.toggle-global');
    Route::post('/staff/registration/deploy-form', [RegistrarController::class, 'deployForm'])->name('staff.deploy-form');
    Route::patch('/staff/registration/responses/{id}/approve', [RegistrarController::class, 'approveResponse'])->middleware('staff.feature:staff_admission_responses')->name('staff.responses.approve');
    Route::patch('/staff/registration/responses/{id}/reject', [RegistrarController::class, 'rejectResponse'])->middleware('staff.feature:staff_admission_responses')->name('staff.responses.reject');

    // Student Records
    Route::get('/staff/students-explorer', [BlockManagementController::class, 'studentsExplorer'])->name('staff.students-explorer');
    Route::get('/staff/students-explorer/students', [BlockManagementController::class, 'allStudents'])->name('staff.students-explorer.students');
    Route::get('/staff/students-explorer/students/{user}/block-assignments', [BlockManagementController::class, 'blockAssignments'])->name('staff.students-explorer.block-assignments');

    Route::get('/staff/block-explorer', [BlockManagementController::class, 'blockExplorer'])->name('staff.block-explorer');
    Route::get('/staff/block-explorer/tree', [BlockManagementController::class, 'tree'])->name('staff.block-explorer.tree');
    Route::get('/staff/block-explorer/blocks/{block}/students', [BlockManagementController::class, 'blockStudents'])->name('staff.block-explorer.block-students');
    Route::get('/staff/block-explorer/blocks/{block}/print-all-cor', [CorViewController::class, 'printBlockCors'])->name('staff.block-explorer.print-all-cor');
    Route::get('/staff/block-explorer/blocks/{block}/print-master-list', [BlockManagementController::class, 'printBlockMasterList'])->name('staff.block-explorer.print-master-list');
    Route::get('/staff/students/{user}/cor', [CorViewController::class, 'viewStudentCor'])->name('staff.students.cor');
    Route::post('/staff/block-explorer/assign-irregular', [BlockManagementController::class, 'assignIrregularToBlock'])->name('staff.block-explorer.assign-irregular');
    Route::delete('/staff/block-explorer/assign-irregular', [BlockManagementController::class, 'removeIrregularFromBlock'])->name('staff.block-explorer.remove-irregular');

    Route::post('/staff/blocks/transfer', [BlockManagementController::class, 'transfer'])->name('staff.blocks.transfer');
    Route::post('/staff/blocks/rebalance', [BlockManagementController::class, 'rebalance'])->name('staff.blocks.rebalance');
    Route::post('/staff/blocks/promotion', [BlockManagementController::class, 'promotion'])->name('staff.blocks.promotion');
    Route::get('/staff/blocks/transfer-log', [BlockManagementController::class, 'transferLog'])->name('staff.blocks.transfer-log');

    // Staff Blocks main page uses the same Block Management table view as registrar.
    Route::get('/staff/blocks', [RegistrarOperationsController::class, 'blocks'])->name('staff.blocks');
    Route::get('/staff/block-change-requests', [RegistrarOperationsController::class, 'blockChangeRequests'])->name('staff.block-change-requests');
    Route::patch('/staff/block-change-requests/{id}/approve', [RegistrarOperationsController::class, 'approveBlockChange'])->middleware('staff.feature:staff_student_records_block_requests')->name('staff.block-change-requests.approve');
    Route::patch('/staff/block-change-requests/{id}/reject', [RegistrarOperationsController::class, 'rejectBlockChange'])->middleware('staff.feature:staff_student_records_block_requests')->name('staff.block-change-requests.reject');

    Route::get('/staff/irregularities', [RegistrarController::class, 'irregularities'])->name('staff.irregularities');
    Route::get('/staff/irregularities/{user}/schedule', [RegistrarController::class, 'irregularSchedule'])->name('staff.irregularities.schedule');
    Route::delete('/staff/irregularities/schedule/record/{record}', [RegistrarController::class, 'removeIrregularScheduleSubject'])->name('staff.irregularities.schedule.remove-subject');

    Route::get('/staff/irregular-cor-archive', [IrregularCorArchiveController::class, 'index'])->name('staff.irregular-cor-archive.index');
    Route::get('/staff/irregular-cor-archive/{date}/{deployedBy}', [IrregularCorArchiveController::class, 'show'])->name('staff.irregular-cor-archive.show')->where('date', '[0-9]{4}-[0-9]{2}-[0-9]{2}');

    Route::get('/staff/cor-archive', [CorArchiveController::class, 'index'])->name('staff.cor.archive.index');
    Route::get('/staff/cor-archive/program/{programId}', [CorArchiveController::class, 'program'])->name('staff.cor.archive.program');
    Route::get('/staff/cor-archive/program/{programId}/year/{yearLevel}', [CorArchiveController::class, 'year'])->where('yearLevel', '[^/]+')->name('staff.cor.archive.year');
    Route::get('/staff/cor-archive/{programId}/{yearLevel}/{semester}', [CorArchiveController::class, 'show'])->where(['yearLevel' => '[^/]+', 'semester' => '[^/]+'])->name('staff.cor.archive.show');
    Route::post('/staff/cor-archive/delete-block', [CorArchiveController::class, 'deleteBlockArchive'])->name('staff.cor.archive.delete-block');

    // Schedule
    Route::get('/staff/program-schedule', [RegistrarScheduleController::class, 'programSchedule'])->name('staff.program-schedule.index');
    Route::post('/staff/program-schedule/save', [RegistrarScheduleController::class, 'saveProgramSchedule'])->name('staff.program-schedule.save');

    // Reports
    Route::get('/staff/analytics', [AnalyticsController::class, 'index'])->name('staff.analytics');
    Route::get('/staff/analytics/print', [AnalyticsController::class, 'printAnalytics'])->name('staff.analytics.print');
    Route::get('/staff/analytics/export', [AnalyticsController::class, 'exportCsv'])->name('staff.analytics.export');

    Route::get('/staff/reports', [ReportController::class, 'index'])->name('staff.reports');
    Route::get('/staff/reports/print', [ReportController::class, 'printReport'])->name('staff.reports.print');
    Route::get('/staff/reports/export', [ReportController::class, 'export'])->name('staff.reports.export');

    Route::patch('/staff/student-status/{id}/record', [AdminAccountController::class, 'updateStudentStatusRecord'])->name('staff.student-status.update-record');

    Route::get('/staff/feedback', [FeedbackController::class, 'create'])->name('staff.feedback');
    Route::post('/staff/feedback', [FeedbackController::class, 'store'])->name('staff.feedback.store');
});

Route::middleware(['auth', 'role:student', 'ensure.student.profile'])->group(function () {
    Route::get('/student/dashboard', function () {
        $user = Auth::user();
        $user->load('block');
        
        $enrollmentOpen = \Illuminate\Support\Facades\Cache::get('global_enrollment_active', false);
        $availableForm = null;
        $existingApplication = null;
        $latestApplication = \App\Models\FormResponse::with('enrollmentForm')
            ->where('user_id', $user->id)
            ->latest()
            ->first();
        $availableBlocks = \App\Models\Block::query()
            ->where('is_active', true)
            ->where('year_level', $user->year_level)
            ->where('semester', $user->semester)
            ->where(function ($query) use ($user) {
                $gender = strtolower((string) ($user->gender ?? ''));
                $query->whereNull('gender_group')
                    ->orWhere('gender_group', 'mixed')
                    ->orWhereRaw('LOWER(gender_group) = ?', [$gender]);
            })
            ->orderBy('code')
            ->get();
        $pendingBlockChangeRequest = \App\Models\BlockChangeRequest::query()
            ->where('student_id', $user->id)
            ->where('status', 'pending')
            ->latest()
            ->first();
        $hasSchedule = $user->block_id
            ? \App\Models\ClassSchedule::query()->where('block_id', $user->block_id)->exists()
            : false;
        $latestAssessment = \App\Models\Assessment::query()
            ->where('user_id', $user->id)
            ->latest()
            ->first();
        
        if ($enrollmentOpen && $user->year_level && $user->semester) {
            $availableForm = \App\Models\EnrollmentForm::where('is_active', true)
                ->where('assigned_year', $user->year_level)
                ->where('assigned_semester', $user->semester)
                ->whereNotNull('incoming_year_level')
                ->whereNotNull('incoming_semester')
                ->first();

            if ($availableForm) {
                $existingApplication = \App\Models\FormResponse::where('enrollment_form_id', $availableForm->id)
                    ->where('user_id', $user->id)
                    ->first();
            }
        }
        
        return view('dashboards.student', compact('enrollmentOpen', 'availableForm', 'existingApplication', 'latestApplication', 'availableBlocks', 'pendingBlockChangeRequest', 'hasSchedule', 'latestAssessment'));
    })->name('student.dashboard');
    
    Route::get('/student/enrollment-form', function () {
        $user = Auth::user();
        
        $enrollmentOpen = \Illuminate\Support\Facades\Cache::get('global_enrollment_active', false);
        
        if (!$enrollmentOpen) {
            return redirect()->route('student.dashboard')->with('error', 'Enrollment is currently closed.');
        }
        
        $form = \App\Models\EnrollmentForm::where('is_active', true)
            ->where('assigned_year', $user->year_level)
            ->where('assigned_semester', $user->semester)
            ->whereNotNull('incoming_year_level')
            ->whereNotNull('incoming_semester')
            ->first();
        
        if (!$form) {
            return redirect()->route('student.dashboard')->with('error', 'No enrollment form available.');
        }

        $existingApplication = \App\Models\FormResponse::where('enrollment_form_id', $form->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existingApplication && ($existingApplication->process_status ?? 'pending') !== 'needs_correction') {
            return redirect()->route('student.dashboard')->with('error', 'You already submitted an application. Please wait for status update.');
        }
        
        $availableBlocks = \App\Models\Block::query()
            ->where('is_active', true)
            ->where('year_level', $user->year_level)
            ->where('semester', $user->semester)
            ->where('shift', $user->shift ?: 'day')
            ->where(function ($query) use ($user) {
                $gender = strtolower((string) ($user->gender ?? ''));
                $query->whereNull('gender_group')
                    ->orWhere('gender_group', 'mixed')
                    ->orWhereRaw('LOWER(gender_group) = ?', [$gender]);
            })
            ->orderBy('code')
            ->get();

        $majorsByProgram = \App\Models\Major::majorsByProgram();

        return view('dashboards.student-enrollment-form', compact('form', 'availableBlocks', 'existingApplication', 'majorsByProgram'));
    })->name('student.enrollment-form');
    
    Route::get('/student/profile', function () {
        $user = Auth::user();
        if ($user->profile_completed) {
            return redirect()->route('student.profile.edit');
        }
        $yearLevels = \App\Models\AcademicYearLevel::where('is_active', true)->orderBy('name')->pluck('name');
        $semesters = \App\Models\AcademicSemester::where('is_active', true)->orderBy('name')->pluck('name');
        return view('dashboards.student-profile', compact('user', 'yearLevels', 'semesters'));
    })->name('student.profile');
    
    Route::post('/student/profile', function (\Illuminate\Http\Request $request) {
        $yearLevelOptions = \App\Models\AcademicYearLevel::where('is_active', true)->pluck('name')->all();
        $semesterOptions = \App\Models\AcademicSemester::where('is_active', true)->pluck('name')->all();

        $validated = $request->validate([
            'school_id' => ['required', 'string', \Illuminate\Validation\Rule::unique('users', 'school_id')->ignore(Auth::id())],
            'last_name' => 'required|string',
            'first_name' => 'required|string',
            'middle_name' => 'nullable|string',
            'gender' => 'required|in:Male,Female',
            'date_of_birth' => 'required|date',
            'place_of_birth' => 'required|string',
            'civil_status' => 'required|string',
            'citizenship' => 'required|string',
            'phone' => 'required|string',
            'college' => 'required|string',
            'course' => 'required|string',
            'year_level' => ['required', 'string', \Illuminate\Validation\Rule::in($yearLevelOptions)],
            'semester' => ['required', 'string', \Illuminate\Validation\Rule::in($semesterOptions)],
            'student_type' => 'required|in:Student,Freshman,Regular,Shifter,Transferee,Returnee,Irregular',
            'shift' => 'nullable|in:day,night',
            'is_freshman' => 'boolean',
            'high_school' => 'nullable|string',
            'hs_graduation_date' => 'nullable|date',
            'father_name' => 'required|string',
            'father_occupation' => 'required|string',
            'mother_name' => 'required|string',
            'mother_occupation' => 'required|string',
            'monthly_income' => 'required|string',
            'num_family_members' => 'required|integer',
            'dswd_household_no' => 'required|string',
            'num_siblings' => 'required|integer',
            'purok_zone' => 'required|string',
            'house_number' => 'required|string',
            'street' => 'required|string',
            'barangay' => 'required|string',
            'municipality' => 'required|string',
            'province' => 'required|string',
            'zip_code' => 'required|string',
        ]);
        
        $validated['profile_completed'] = true;
        $validated['units_enrolled'] = 0;
        $validated['semester'] = $request->input('semester');
        $studentType = $validated['student_type'] ?? null;
        $validated['status_color'] = match ($studentType) {
            'Irregular' => 'yellow',
            'Returnee' => 'blue',
            'Transferee' => 'green',
            default => null,
        };
        $user = Auth::user();
        $user->update($validated);
        if ($user->role === 'student' && !empty($validated['school_id'] ?? '')) {
            $user->update(['email' => $validated['school_id']]);
        }
        
        return redirect()->route('student.dashboard')->with('success', 'Profile completed successfully!');
    })->name('student.profile.update');
    
    Route::get('/student/profile/edit', function () {
        $yearLevels = \App\Models\AcademicYearLevel::where('is_active', true)->orderBy('name')->pluck('name');
        $semesters = \App\Models\AcademicSemester::where('is_active', true)->orderBy('name')->pluck('name');
        $schoolYears = \App\Models\SchoolYear::orderByDesc('start_year')->pluck('label');
        return view('dashboards.student-profile-edit', compact('yearLevels', 'semesters', 'schoolYears'));
    })->name('student.profile.edit');
    
    Route::post('/student/profile/edit', function (\Illuminate\Http\Request $request) {
        $validated = $request->validate([
            'last_name' => 'required|string',
            'first_name' => 'required|string',
            'middle_name' => 'nullable|string',
            'gender' => 'required|in:Male,Female',
            'date_of_birth' => 'required|date',
            'civil_status' => 'required|string',
            'phone' => 'required|string',
            'father_name' => 'required|string',
            'father_occupation' => 'required|string',
            'mother_name' => 'required|string',
            'mother_occupation' => 'required|string',
            'monthly_income' => 'required|string',
            'num_siblings' => 'required|integer',
            'house_number' => 'required|string',
            'street' => 'required|string',
            'barangay' => 'required|string',
            'municipality' => 'required|string',
            'province' => 'required|string',
            'zip_code' => 'required|string',
            'boarding_house_number' => 'nullable|string',
            'boarding_street' => 'nullable|string',
            'boarding_barangay' => 'nullable|string',
            'boarding_municipality' => 'nullable|string',
            'boarding_province' => 'nullable|string',
            'boarding_phone' => 'nullable|string',
            'student_type' => 'nullable|in:Student,Freshman,Regular,Shifter,Transferee,Returnee,Irregular',
            'shift' => 'nullable|in:day,night',
        ]);

        $studentType = $validated['student_type'] ?? null;
        $validated['status_color'] = match ($studentType) {
            'Irregular' => 'yellow',
            'Returnee' => 'blue',
            'Transferee' => 'green',
            default => null,
        };
        
        Auth::user()->update($validated);
        
        return redirect()->route('student.dashboard')->with('success', 'Profile updated successfully!');
    })->name('student.profile.edit.update');
    
    Route::post('/student/submit-enrollment', function (\Illuminate\Http\Request $request) {
        $request->validate([
            'form_id' => 'required|exists:enrollment_forms,id',
            'preferred_block_id' => 'nullable|exists:blocks,id',
            'response_id' => 'nullable|exists:form_responses,id',
            'answers' => 'required|array'
        ]);

        $form = \App\Models\EnrollmentForm::find($request->form_id);
        if ($form) {
            $answerErrors = $form->validateAnswers($request->answers ?? []);
            if (! empty($answerErrors)) {
                return redirect()->back()->withErrors(['answers' => implode(' ', $answerErrors)])->withInput();
            }
        }

        $existing = \App\Models\FormResponse::where('enrollment_form_id', $request->form_id)
            ->where('user_id', Auth::id())
            ->first();

        if ($existing && ($existing->process_status ?? 'pending') !== 'needs_correction') {
            return redirect()->route('student.dashboard')->with('error', 'You already submitted this application.');
        }

        if ($existing && ($existing->process_status ?? '') === 'needs_correction') {
            $existing->update([
                'preferred_block_id' => $request->preferred_block_id,
                'answers' => $request->answers,
                'approval_status' => 'pending',
                'process_status' => 'pending',
                'process_notes' => 'Resubmitted by student after correction.',
                'reviewed_by' => null,
                'reviewed_at' => null,
            ]);
        } else {
            \App\Models\FormResponse::create([
                'enrollment_form_id' => $request->form_id,
                'user_id' => Auth::id(),
                'preferred_block_id' => $request->preferred_block_id,
                'answers' => $request->answers,
                'approval_status' => 'pending',
                'process_status' => 'pending',
            ]);
        }
        
        return redirect()->route('student.dashboard')->with('success', 'Enrollment form submitted successfully!');
    })->name('student.submit-enrollment');
    Route::post('/student/block-change-request', [StudentServicesController::class, 'requestBlockChange'])->name('student.block-change-request');
    Route::get('/student/cor', [StudentServicesController::class, 'cor'])->name('student.cor');

    Route::get('/student/feedback', [FeedbackController::class, 'create'])->name('student.feedback');
    Route::post('/student/feedback', [FeedbackController::class, 'store'])->name('student.feedback.store');
});

Route::middleware(['auth', 'role:unifast'])->group(function () {
    Route::get('/unifast/dashboard', [AdminDashboardController::class, 'index'])->name('unifast.dashboard');
    Route::get('/unifast/student-status', [AdminAccountController::class, 'studentStatus'])->name('unifast.student-status');
    Route::get('/unifast/assessments', [FinanceMonitoringController::class, 'unifastIndex'])->name('unifast.assessments');
    Route::get('/unifast/assessments/export', [FinanceMonitoringController::class, 'exportUnifastCsv'])->name('unifast.assessments.export');
    Route::patch('/unifast/assessments/{id}/eligibility', [FinanceMonitoringController::class, 'updateUnifastEligibility'])->name('unifast.assessments.eligibility');

    Route::get('/unifast/settings/fees', [RegistrarSettingsController::class, 'fees'])->middleware('unifast.feature:unifast_fees')->name('unifast.settings.fees');
    Route::post('/unifast/settings/fees', [RegistrarSettingsController::class, 'storeFee'])->middleware('unifast.feature:unifast_fees')->name('unifast.settings.fees.store');
    Route::post('/unifast/settings/fees/table', [RegistrarSettingsController::class, 'updateFeeTable'])->middleware('unifast.feature:unifast_fees')->name('unifast.settings.fees.table');
    Route::post('/unifast/settings/fees/copy-from-raw', [RegistrarSettingsController::class, 'copyFeeFromRaw'])->middleware('unifast.feature:unifast_fees')->name('unifast.settings.fees.copy-from-raw');
    Route::patch('/unifast/settings/fees/{id}/toggle', [RegistrarSettingsController::class, 'toggleFee'])->middleware('unifast.feature:unifast_fees')->name('unifast.settings.fees.toggle');
    Route::delete('/unifast/settings/fees/{id}', [RegistrarSettingsController::class, 'destroyFee'])->middleware('unifast.feature:unifast_fees')->name('unifast.settings.fees.destroy');
    Route::post('/unifast/settings/raw-fees', [RegistrarSettingsController::class, 'storeRawFee'])->middleware('unifast.feature:unifast_fees')->name('unifast.settings.raw-fees.store');

    Route::get('/unifast/analytics', [AnalyticsController::class, 'index'])->name('unifast.analytics');
    Route::get('/unifast/analytics/print', [AnalyticsController::class, 'printAnalytics'])->name('unifast.analytics.print');
    Route::get('/unifast/analytics/export', [AnalyticsController::class, 'exportCsv'])->name('unifast.analytics.export');

    Route::get('/unifast/reports', [ReportController::class, 'index'])->middleware('unifast.feature:unifast_reports')->name('unifast.reports');
    Route::get('/unifast/reports/print', [ReportController::class, 'printReport'])->middleware('unifast.feature:unifast_reports')->name('unifast.reports.print');
    Route::get('/unifast/reports/export', [ReportController::class, 'export'])->middleware('unifast.feature:unifast_reports')->name('unifast.reports.export');

    Route::get('/unifast/feedback', [FeedbackController::class, 'create'])->name('unifast.feedback');
    Route::post('/unifast/feedback', [FeedbackController::class, 'store'])->name('unifast.feedback.store');
});

Route::middleware(['auth', 'role:dean'])->group(function () {
    Route::get('/dean/dashboard', [AdminDashboardController::class, 'index'])->name('dean.dashboard');
    Route::get('/dean/student-status', [AdminAccountController::class, 'studentStatus'])->name('dean.student-status');
    Route::get('/dean/analytics', [AnalyticsController::class, 'index'])->name('dean.analytics');
    Route::get('/dean/analytics/print', [AnalyticsController::class, 'printAnalytics'])->name('dean.analytics.print');
    Route::get('/dean/analytics/export', [AnalyticsController::class, 'exportCsv'])->name('dean.analytics.export');
    Route::get('/dean/reports', [ReportController::class, 'index'])->name('dean.reports');
    Route::get('/dean/reports/print', [ReportController::class, 'printReport'])->name('dean.reports.print');
    Route::get('/dean/reports/export', [ReportController::class, 'export'])->name('dean.reports.export');
    Route::get('/dean/scheduling', [DeanSchedulingController::class, 'index'])->name('dean.scheduling');
    Route::get('/dean/room-utilization', [DeanSchedulingController::class, 'roomUtilization'])->name('dean.room-utilization');
    Route::get('/dean/scheduling/available-rooms', [DeanSchedulingController::class, 'availableRooms'])->name('dean.scheduling.available-rooms');
    Route::post('/dean/scheduling', [DeanSchedulingController::class, 'store'])->name('dean.scheduling.store');
    Route::post('/dean/scheduling/subjects', [DeanSchedulingController::class, 'storeSubject'])->name('dean.scheduling.subjects.store');
    Route::post('/dean/scheduling/rooms', [DeanSchedulingController::class, 'storeRoom'])->name('dean.scheduling.rooms.store');

    Route::middleware(['dean.department'])->group(function () {
        Route::get('/dean/schedule', [DeanScheduleByScopeController::class, 'scheduleByScope'])->name('dean.schedule.by-scope');
        Route::post('/dean/schedule/slots', [DeanScheduleByScopeController::class, 'saveScopeScheduleSlots'])->name('dean.schedule.slots.save');
        Route::post('/dean/schedule/deploy-cor', [DeanScheduleByScopeController::class, 'deployCor'])->name('dean.schedule.deploy-cor');
        Route::post('/dean/schedule/fetch-cor', [DeanScheduleByScopeController::class, 'fetchCor'])->name('dean.schedule.fetch-cor');
    });
    Route::get('/dean/cor-archive', [CorArchiveController::class, 'index'])->name('cor.archive.index');
    Route::get('/dean/cor-archive/program/{programId}', [CorArchiveController::class, 'program'])->name('cor.archive.program');
    Route::get('/dean/cor-archive/program/{programId}/year/{yearLevel}', [CorArchiveController::class, 'year'])->where('yearLevel', '[^/]+')->name('cor.archive.year');
    Route::get('/dean/cor-archive/{programId}/{yearLevel}/{semester}/{deployedBlock?}', [CorArchiveController::class, 'show'])->where(['yearLevel' => '[^/]+', 'semester' => '[^/]+', 'deployedBlock' => '[0-9]+'])->name('cor.archive.show');
    Route::post('/dean/cor-archive/delete-block', [CorArchiveController::class, 'deleteBlockArchive'])->name('cor.archive.delete-block');
    Route::get('/dean/settings/professors', [RegistrarSettingsController::class, 'professors'])->name('dean.settings.professors');
    Route::post('/dean/settings/professors', [RegistrarSettingsController::class, 'storeProfessor'])->name('dean.settings.professors.store');
    Route::get('/dean/settings/rooms', [RegistrarSettingsController::class, 'rooms'])->name('dean.settings.rooms');
    Route::post('/dean/settings/rooms', [RegistrarSettingsController::class, 'storeRoom'])->name('dean.settings.rooms.store');

    Route::get('/dean/manage-professor', [DeanManageProfessorController::class, 'index'])->name('dean.manage-professor.index');
    Route::post('/dean/manage-professor/toggle-all-subjects', [DeanManageProfessorController::class, 'toggleAllProfessorsAllSubjects'])->name('dean.manage-professor.toggle-all-subjects');
    Route::get('/dean/manage-professor/teaching-load', [DeanManageProfessorController::class, 'teachingLoad'])->name('dean.manage-professor.teaching-load');
    Route::get('/dean/manage-professor/{professor}', [DeanManageProfessorController::class, 'show'])->name('dean.manage-professor.show');
    Route::get('/dean/manage-professor/{professor}/view-profile-data', [DeanManageProfessorController::class, 'viewProfileData'])->name('dean.manage-professor.view-profile-data');
    Route::get('/dean/manage-professor/{professor}/assignments-data', [DeanManageProfessorController::class, 'assignmentsData'])->name('dean.manage-professor.assignments-data');
    Route::post('/dean/manage-professor/{professor}/assignments', [DeanManageProfessorController::class, 'storeAssignments'])->name('dean.manage-professor.assignments.store');
    Route::patch('/dean/manage-professor/{professor}/max-units', [DeanManageProfessorController::class, 'updateMaxUnits'])->name('dean.manage-professor.max-units');
    Route::patch('/dean/manage-professor/{professor}/schedule-selection-limit', [DeanManageProfessorController::class, 'updateScheduleSelectionLimit'])->name('dean.manage-professor.schedule-selection-limit');

    Route::get('/dean/feedback', [FeedbackController::class, 'create'])->name('dean.feedback');
    Route::post('/dean/feedback', [FeedbackController::class, 'store'])->name('dean.feedback.store');
});

require __DIR__.'/auth.php';