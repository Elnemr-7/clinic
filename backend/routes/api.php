<?php

use Illuminate\Support\Facades\Route;

// -----------------------------------------------------------------------------
// Public Endpoints (Booking Website & Landing Page)
// -----------------------------------------------------------------------------
Route::prefix('public')->group(function () {
    Route::get('/plans', [\App\Http\Controllers\Public\PublicPlanController::class, 'index']);
    Route::post('/leads', [\App\Http\Controllers\Public\PublicPlanController::class, 'submitLead'])->middleware('throttle:10,1');

    Route::get('/tenant-by-host', [\App\Http\Controllers\Public\PublicBookingController::class, 'tenantByHost']);
    Route::get('/clinic/{clinicId}', [\App\Http\Controllers\Public\PublicBookingController::class, 'clinicDetails']);
    Route::get('/check-followup', [\App\Http\Controllers\Public\PublicBookingController::class, 'checkEligibility']);
    Route::post('/book-appointment', [\App\Http\Controllers\Public\PublicBookingController::class, 'bookAppointment'])->middleware('throttle:10,1');
    Route::get('/live-queue/{referenceCode}', [\App\Http\Controllers\Public\PublicBookingController::class, 'liveQueueStatus']);
    Route::get('/tv/{code}', [\App\Http\Controllers\Public\PublicBookingController::class, 'tvQueue']);
    Route::get('/rx/{token}', [\App\Http\Controllers\Public\PublicBookingController::class, 'downloadPrescriptionPdf']);
});

// TV Screen alias under clinic prefix
Route::get('/clinic/public/tv/{code}', [\App\Http\Controllers\Public\PublicBookingController::class, 'tvQueue']);

// Also make /rx/{token} available directly from root
Route::get('/rx/{token}', [\App\Http\Controllers\Public\PublicBookingController::class, 'downloadPrescriptionPdf']);

// -----------------------------------------------------------------------------
// Super Admin Endpoints (Strictly Isolated /api/super-admin/*)
// -----------------------------------------------------------------------------
Route::prefix('super-admin')->group(function () {
    Route::post('/login', [\App\Http\Controllers\SuperAdmin\AuthController::class, 'login'])->middleware('throttle:5,1');

    Route::middleware(['auth:sanctum', 'super_admin.auth'])->group(function () {
        Route::get('/me', [\App\Http\Controllers\SuperAdmin\AuthController::class, 'me']);
        Route::post('/logout', [\App\Http\Controllers\SuperAdmin\AuthController::class, 'logout']);

        Route::get('/stats', [\App\Http\Controllers\SuperAdmin\DashboardController::class, 'stats']);

        Route::get('/leads', [\App\Http\Controllers\SuperAdmin\DoctorLeadController::class, 'index']);
        Route::post('/leads/{id}/convert', [\App\Http\Controllers\SuperAdmin\DoctorLeadController::class, 'convert']);

        Route::get('/tenants', [\App\Http\Controllers\SuperAdmin\TenantController::class, 'index']);
        Route::post('/tenants', [\App\Http\Controllers\SuperAdmin\TenantController::class, 'store']);
        Route::get('/tenants/{id}', [\App\Http\Controllers\SuperAdmin\TenantController::class, 'show']);
        Route::put('/tenants/{id}', [\App\Http\Controllers\SuperAdmin\TenantController::class, 'update']);
        Route::delete('/tenants/{id}', [\App\Http\Controllers\SuperAdmin\TenantController::class, 'destroy']);
        Route::put('/tenants/{id}/wallet', [\App\Http\Controllers\SuperAdmin\TenantController::class, 'updateWallet']);
        Route::post('/tenants/{id}/impersonate', [\App\Http\Controllers\SuperAdmin\TenantController::class, 'impersonate']);

        Route::apiResource('plans', \App\Http\Controllers\SuperAdmin\PlanController::class);

        Route::get('/topups', [\App\Http\Controllers\SuperAdmin\TopupController::class, 'index']);
        Route::put('/topups/{id}/approve', [\App\Http\Controllers\SuperAdmin\TopupController::class, 'approve']);
        Route::put('/topups/{id}/reject', [\App\Http\Controllers\SuperAdmin\TopupController::class, 'reject']);
    });
});

// -----------------------------------------------------------------------------
// Clinic Endpoints (Doctor & Receptionist Workspace /api/clinic/*)
// -----------------------------------------------------------------------------
Route::prefix('clinic')->group(function () {
    Route::post('/login', [\App\Http\Controllers\Clinic\AuthController::class, 'login'])->middleware('throttle:5,1');
    Route::get('/heartbeat', function () {
        return response()->json(['status' => 'ok', 'time' => now()]);
    });

    Route::middleware(['auth:sanctum', 'clinic.auth', 'tenant.active'])->group(function () {
        Route::get('/me', [\App\Http\Controllers\Clinic\AuthController::class, 'me']);
        Route::post('/logout', [\App\Http\Controllers\Clinic\AuthController::class, 'logout']);

        // Doctor Single-Screen Consultation Workspace
        Route::prefix('doctor')->middleware('clinic.auth:doctor')->group(function () {
            Route::get('/current-patient', [\App\Http\Controllers\Clinic\DoctorWorkspaceController::class, 'currentPatient']);
            Route::get('/prescription/{appointmentId}', [\App\Http\Controllers\Clinic\DoctorWorkspaceController::class, 'savePrescription']); // fallback
            Route::get('/prescription-templates', [\App\Http\Controllers\Clinic\SettingsController::class, 'getTemplates']);
            Route::get('/services', [\App\Http\Controllers\Clinic\SettingsController::class, 'getServices']);
            Route::get('/templates', [\App\Http\Controllers\Clinic\SettingsController::class, 'getTemplates']);
            Route::get('/services', [\App\Http\Controllers\Clinic\SettingsController::class, 'getServices']);
            Route::post('/prescription/{appointmentId}', [\App\Http\Controllers\Clinic\DoctorWorkspaceController::class, 'savePrescription']);
            Route::post('/labs/{appointmentId}', [\App\Http\Controllers\Clinic\DoctorWorkspaceController::class, 'saveLabsAndNotes']);
            Route::post('/services-and-followup/{appointmentId}', [\App\Http\Controllers\Clinic\DoctorWorkspaceController::class, 'saveExtraServicesAndFollowup']);
            Route::post('/next-patient', [\App\Http\Controllers\Clinic\DoctorWorkspaceController::class, 'completeAndCallNextPatient']);
            Route::get('/patient-history/{patientId}', [\App\Http\Controllers\Clinic\DoctorWorkspaceController::class, 'previousVisits']);
            Route::post('/attachments/{patientId}', [\App\Http\Controllers\Clinic\DoctorWorkspaceController::class, 'saveAttachments']);
            Route::get('/attachments/{attachmentId}', [\App\Http\Controllers\Clinic\DoctorWorkspaceController::class, 'downloadAttachment']);
            Route::get('/drugs/autocomplete', [\App\Http\Controllers\Clinic\DoctorWorkspaceController::class, 'autoCompleteDrugs']);
            Route::get('/labs/autocomplete', [\App\Http\Controllers\Clinic\DoctorWorkspaceController::class, 'autoCompleteLabs']);
        });

        // Receptionist Single-Screen Desk
        Route::prefix('reception')->group(function () {
            Route::get('/queue', [\App\Http\Controllers\Clinic\ReceptionController::class, 'queue']);
            Route::get('/check-followup', [\App\Http\Controllers\Clinic\ReceptionController::class, 'checkFollowup']);
            Route::post('/quick-book', [\App\Http\Controllers\Clinic\ReceptionController::class, 'quickBook']);
            Route::post('/status/{appointmentId}', [\App\Http\Controllers\Clinic\ReceptionController::class, 'updateStatus']);
            Route::post('/collect-extra/{appointmentId}', [\App\Http\Controllers\Clinic\ReceptionController::class, 'collectExtraServices']);
            Route::post('/vitals/{appointmentId}', [\App\Http\Controllers\Clinic\ReceptionController::class, 'saveVitals']);
            Route::get('/cashbox', [\App\Http\Controllers\Clinic\ReceptionController::class, 'cashboxSummary']);
            Route::post('/expense', [\App\Http\Controllers\Clinic\ReceptionController::class, 'recordExpense']);
        });

        // Settings & Configurations
        Route::prefix('settings')->middleware('clinic.auth:doctor')->group(function () {
            Route::get('/', [\App\Http\Controllers\Clinic\SettingsController::class, 'getSettings']);
            Route::post('/vitals', [\App\Http\Controllers\Clinic\SettingsController::class, 'updateVitalsConfig']);
            Route::post('/print-margins', [\App\Http\Controllers\Clinic\SettingsController::class, 'updatePrintMargins']);
            Route::get('/test-print', [\App\Http\Controllers\Clinic\SettingsController::class, 'testPrintPdf']);

            Route::get('/drugs', [\App\Http\Controllers\Clinic\SettingsController::class, 'getDrugs']);
            Route::post('/drugs', [\App\Http\Controllers\Clinic\SettingsController::class, 'saveDrug']);
            Route::delete('/drugs/{id}', [\App\Http\Controllers\Clinic\SettingsController::class, 'deleteDrug']);

            Route::get('/services', [\App\Http\Controllers\Clinic\SettingsController::class, 'getServices']);
            Route::get('/templates', [\App\Http\Controllers\Clinic\SettingsController::class, 'getTemplates']);
            Route::post('/templates', [\App\Http\Controllers\Clinic\SettingsController::class, 'saveTemplate']);
            Route::delete('/templates/{id}', [\App\Http\Controllers\Clinic\SettingsController::class, 'deleteTemplate']);

            Route::get('/labs', [\App\Http\Controllers\Clinic\SettingsController::class, 'getLabs']);
            Route::post('/labs', [\App\Http\Controllers\Clinic\SettingsController::class, 'saveLab']);

            Route::get('/branches', [\App\Http\Controllers\Clinic\SettingsController::class, 'getBranches']);
            Route::post('/branches', [\App\Http\Controllers\Clinic\SettingsController::class, 'saveBranch']);
            Route::post('/shifts', [\App\Http\Controllers\Clinic\SettingsController::class, 'saveShift']);
            Route::delete('/shifts/{id}', [\App\Http\Controllers\Clinic\SettingsController::class, 'deleteShift']);
            Route::post('/days-off', [\App\Http\Controllers\Clinic\SettingsController::class, 'saveDayOff']);

            Route::get('/receptionists', [\App\Http\Controllers\Clinic\SettingsController::class, 'getReceptionists']);
            Route::post('/receptionists', [\App\Http\Controllers\Clinic\SettingsController::class, 'saveReceptionist']);
            Route::delete('/receptionists/{id}', [\App\Http\Controllers\Clinic\SettingsController::class, 'deleteReceptionist']);

            Route::get('/whatsapp-qr', [\App\Http\Controllers\Clinic\SettingsController::class, 'getQrCode']);
            Route::post('/topup', [\App\Http\Controllers\Clinic\SettingsController::class, 'requestTopup']);
            Route::post('/custom-domain', [\App\Http\Controllers\Clinic\SettingsController::class, 'updateCustomDomain']);
            Route::post('/website', [\App\Http\Controllers\Clinic\SettingsController::class, 'saveWebsiteSettings']);
            Route::post('/upload-image', [\App\Http\Controllers\Clinic\SettingsController::class, 'uploadImage']);
        });
    });
});

// -----------------------------------------------------------------------------
// Internal Microservice Webhooks
// -----------------------------------------------------------------------------
Route::prefix('internal')->group(function () {
    Route::post('/whatsapp/webhook/{tenantId}', [\App\Http\Controllers\Internal\WhatsAppWebhookController::class, 'handle'])
        ->middleware('internal.webhook');
    Route::post('/fawry/callback', [\App\Http\Controllers\Internal\FawryWebhookController::class, 'callback']);
});
