<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CRM\ClientController;
use App\Http\Controllers\CRM\ClientMergeController;
use App\Http\Controllers\CRM\ClientNoteController;
use App\Http\Controllers\CRM\ClientReminderController;
use App\Http\Controllers\Loyalty\PointController;
use App\Http\Controllers\Loyalty\ReferralController;
use App\Http\Controllers\Finance\QuoteController;
use App\Http\Controllers\Finance\InvoiceController;
use App\Http\Controllers\Scheduling\JobController;
use App\Http\Controllers\Scheduling\JobChecklistController;
use App\Http\Controllers\Inventory\InventoryItemController;
use App\Http\Controllers\HR\StaffController;
use App\Http\Controllers\HR\PayrollController;
use App\Http\Controllers\ServiceCatalog\ServiceController;
use App\Http\Controllers\Portal\ClientPortalController;
use App\Http\Controllers\Communication\CampaignController;
use App\Http\Controllers\Reports\ReportController;
use App\Http\Controllers\Automation\AutomationController;
use App\Http\Controllers\Security\AuditLogController;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::apiResource('clients', ClientController::class);
    Route::post('clients/{client}/merge', ClientMergeController::class);
    Route::apiResource('clients.notes', ClientNoteController::class)->shallow()->only(['index','store','destroy']);
    Route::apiResource('clients.reminders', ClientReminderController::class)->shallow();

    Route::apiResource('loyalty/points', PointController::class)->only(['index','store']);
    Route::post('loyalty/points/{account}/redeem', [PointController::class, 'redeem']);
    Route::post('loyalty/points/{account}/adjust', [PointController::class, 'adjust']);
    Route::post('loyalty/referrals/{client}', [ReferralController::class, 'generate']);

    Route::apiResource('quotes', QuoteController::class);
    Route::post('quotes/{quote}/convert', [QuoteController::class, 'convert']);
    Route::apiResource('invoices', InvoiceController::class);
    Route::post('invoices/{invoice}/pay', [InvoiceController::class, 'recordPayment']);
    Route::post('invoices/{invoice}/send-reminder', [InvoiceController::class, 'sendReminder']);

    Route::apiResource('jobs', JobController::class);
    Route::post('jobs/{job}/reassign', [JobController::class, 'reassign']);
    Route::post('jobs/{job}/status', [JobController::class, 'updateStatus']);
    Route::apiResource('jobs.checklists', JobChecklistController::class)->shallow();

    Route::apiResource('inventory/items', InventoryItemController::class);
    Route::post('inventory/items/{item}/assign', [InventoryItemController::class, 'assignToJob']);

    Route::apiResource('staff', StaffController::class);
    Route::post('staff/{staff}/attendance', [StaffController::class, 'attendance']);
    Route::get('payroll/preview', [PayrollController::class, 'preview']);
    Route::post('payroll/run', [PayrollController::class, 'run']);

    Route::apiResource('services', ServiceController::class);
    Route::post('services/{service}/duplicate', [ServiceController::class, 'duplicate']);

    Route::get('portal/me', [ClientPortalController::class, 'dashboard']);
    Route::post('portal/me/reschedule', [ClientPortalController::class, 'reschedule']);
    Route::post('portal/me/cancel', [ClientPortalController::class, 'cancel']);

    Route::apiResource('campaigns', CampaignController::class);
    Route::post('campaigns/{campaign}/send', [CampaignController::class, 'dispatchCampaign']);

    Route::get('reports/{type}', [ReportController::class, 'show']);
    Route::post('reports/{type}/export', [ReportController::class, 'export']);

    Route::apiResource('automations', AutomationController::class);
    Route::post('automations/{automation}/run', [AutomationController::class, 'run']);

    Route::get('security/audit-logs', [AuditLogController::class, 'index']);
});

Route::post('portal/login', [ClientPortalController::class, 'login']);
Route::post('webhooks/payment', [AutomationController::class, 'paymentWebhook']);
Route::post('webhooks/telegram', [CampaignController::class, 'telegramWebhook']);
