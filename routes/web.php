<?php

use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AdminMessageController;
use App\Http\Controllers\AjkProgramController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ClaimController;
use App\Http\Controllers\FinancialController;
use App\Http\Controllers\GuruController;
use App\Http\Controllers\GuruCourseController;
use App\Http\Controllers\GuruSalaryInformationController;
use App\Http\Controllers\KelasController;
use App\Http\Controllers\KpiController;
use App\Http\Controllers\LeaveNoticeController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\N8nSettingController;
use App\Http\Controllers\ImpersonationController;
use App\Http\Controllers\PemarkahanController;
use App\Http\Controllers\PastiController;
use App\Http\Controllers\PastiInformationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProgramController;
use App\Http\Controllers\ProgramParticipationController;
use App\Http\Controllers\ProgramTitleOptionController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'));

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::post('/locale', [LocaleController::class, 'update'])->name('locale.update');

    Route::middleware('role:master_admin')->group(function () {
        Route::resource('/users/admins', AdminUserController::class)
            ->except(['show'])
            ->names('users.admins')
            ->parameters(['admins' => 'users_admin']);
        Route::post('/program-title-options', [ProgramTitleOptionController::class, 'store'])->name('program-title-options.store');
        Route::put('/program-title-options/{programTitleOption}', [ProgramTitleOptionController::class, 'update'])->name('program-title-options.update');
        Route::delete('/program-title-options/{programTitleOption}', [ProgramTitleOptionController::class, 'destroy'])->name('program-title-options.destroy');
        Route::post('/pemarkahan/title-options', [PemarkahanController::class, 'storeTitleOption'])->name('pemarkahan.title-options.store');
        Route::post('/kewangan/jenis-transaksi', [FinancialController::class, 'storeType'])->name('financial.types.store');
        Route::put('/kewangan/jenis-transaksi/{financialTransactionType}', [FinancialController::class, 'updateType'])->name('financial.types.update');
        Route::delete('/kewangan/jenis-transaksi/{financialTransactionType}', [FinancialController::class, 'destroyType'])->name('financial.types.destroy');
        Route::get('/settings/n8n', [N8nSettingController::class, 'edit'])->name('n8n-settings.edit');
        Route::put('/settings/n8n', [N8nSettingController::class, 'update'])->name('n8n-settings.update');
    });

    Route::middleware('role:master_admin|admin')->group(function () {
        Route::resource('/users/gurus', GuruController::class)
            ->except(['show'])
            ->names('users.gurus')
            ->parameters(['gurus' => 'users_guru']);
        Route::post('/users/gurus/{users_guru}/impersonate', [ImpersonationController::class, 'start'])->name('users.gurus.impersonate');
        Route::resource('/pasti', PastiController::class)->except(['show']);
        Route::resource('/kelas', KelasController::class)->except(['show']);
        Route::post('/kelas/{kela}/student-count', [KelasController::class, 'updateStudentCount'])->name('kelas.student-count.update');
        Route::post('/maklumat-pasti/request-all', [PastiInformationController::class, 'requestAllUpdates'])->name('pasti-information.request-all');
        Route::post('/maklumat-gaji-guru/request-all', [GuruSalaryInformationController::class, 'requestAllUpdates'])->name('guru-salary-information.request-all');
        Route::resource('/programs', ProgramController::class)->except(['index', 'show']);
        Route::get('/kpi/gurus', [KpiController::class, 'index'])->name('kpi.gurus.index');
        Route::get('/messages/create', [AdminMessageController::class, 'create'])->name('messages.create');
        Route::post('/messages', [AdminMessageController::class, 'store'])->name('messages.store');
        Route::get('/users/expired-skim-pas', [AdminUserController::class, 'expiredSkimPas'])->name('users.expired-skim-pas');
        Route::get('/ajk-program', [AjkProgramController::class, 'index'])->name('ajk-program.index');
        Route::post('/ajk-program/assignments', [AjkProgramController::class, 'updateAssignments'])->name('ajk-program.assignments.update');
        Route::get('/kewangan', [FinancialController::class, 'index'])->name('financial.index');
        Route::post('/kewangan', [FinancialController::class, 'store'])->name('financial.store');
    });

    Route::middleware('role:guru')->group(function () {
        Route::get('/kpi/saya', fn () => redirect()->route('kpi.guru.show', auth()->user()->guru))->name('kpi.mine');
        Route::get('/pasti-saya', [PastiController::class, 'editOwn'])->name('pasti.self.edit');
        Route::put('/pasti-saya', [PastiController::class, 'updateOwn'])->name('pasti.self.update');
        Route::get('/leave-notices/create', [LeaveNoticeController::class, 'create'])->name('leave-notices.create');
        Route::post('/leave-notices', [LeaveNoticeController::class, 'store'])->name('leave-notices.store');
        Route::get('/maklumat-pasti/{pastiInformationRequest}/isi', [PastiInformationController::class, 'edit'])->name('pasti-information.edit');
        Route::post('/maklumat-pasti/{pastiInformationRequest}/isi', [PastiInformationController::class, 'update'])->name('pasti-information.update');
        Route::get('/maklumat-gaji-guru/{guruSalaryRequest}/isi', [GuruSalaryInformationController::class, 'edit'])->name('guru-salary-information.edit');
        Route::post('/maklumat-gaji-guru/{guruSalaryRequest}/isi', [GuruSalaryInformationController::class, 'update'])->name('guru-salary-information.update');
        Route::post('/kursus-guru/responses/{response}', [GuruCourseController::class, 'respond'])->name('kursus-guru.responses.respond');
        Route::put('/profile/pasti-selection', [ProfileController::class, 'updatePastiSelection'])->name('profile.pasti-selection.update');
    });

    Route::middleware('role:master_admin|admin|guru')->group(function () {
        Route::get('/claims', [ClaimController::class, 'index'])->name('claims.index');
        Route::post('/claims', [ClaimController::class, 'store'])->name('claims.store');
        Route::delete('/claims/{claim}', [ClaimController::class, 'destroy'])->name('claims.destroy');
        Route::get('/pemarkahan', [PemarkahanController::class, 'index'])->name('pemarkahan.index');
        Route::post('/pemarkahan', [PemarkahanController::class, 'store'])->name('pemarkahan.store');
        Route::get('/maklumat-pasti', [PastiInformationController::class, 'index'])->name('pasti-information.index');
        Route::get('/maklumat-gaji-guru', [GuruSalaryInformationController::class, 'index'])->name('guru-salary-information.index');
        Route::get('/kursus-guru', [GuruCourseController::class, 'index'])->name('kursus-guru.index');
        Route::get('/programs', [ProgramController::class, 'index'])->name('programs.index');
        Route::get('/programs/{program}', [ProgramController::class, 'show'])->name('programs.show');
        Route::post('/programs/{program}/teachers/{guruId}/status', [ProgramParticipationController::class, 'updateStatus'])
            ->name('programs.teachers.status.update');
        Route::get('/kpi/guru/{guru}', [KpiController::class, 'show'])->name('kpi.guru.show');
        Route::get('/leave-notices', [LeaveNoticeController::class, 'index'])->name('leave-notices.index');
        Route::delete('/leave-notices/{leaveNotice}', [LeaveNoticeController::class, 'destroy'])->name('leave-notices.destroy');
        Route::get('/messages', [AdminMessageController::class, 'index'])->name('messages.index');
        Route::get('/messages/{message}', [AdminMessageController::class, 'show'])->name('messages.show');
        Route::post('/messages/{message}/reply', [AdminMessageController::class, 'reply'])->name('messages.reply');
        Route::get('/senarai-guru', [GuruController::class, 'directory'])->name('guru-directory.index');
    });

    Route::middleware('role:master_admin|admin')->group(function () {
        Route::post('/claims/{claim}/approve', [ClaimController::class, 'approve'])->name('claims.approve');
        Route::post('/kursus-guru/offers', [GuruCourseController::class, 'sendOffer'])->name('kursus-guru.offers.send');
    });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy'])->name('notifications.destroy');
    Route::match(['get', 'post'], '/impersonation/leave', [ImpersonationController::class, 'stop'])->name('impersonation.stop');
});

require __DIR__.'/auth.php';


