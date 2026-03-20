<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\InvestorController;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\InvestorCategoryController;
use App\Http\Controllers\Api\InvestorDocumentController;
use App\Http\Controllers\Api\CompanyDirectorController;
use App\Http\Controllers\Api\DirectorVerificationController;
use App\Http\Controllers\Api\KycController;
use App\Http\Controllers\Api\InvestorVerificationController;
use App\Http\Controllers\Api\KycReviewController;
use App\Http\Controllers\Api\KycOperationsController;
use App\Http\Controllers\Api\KycCaseManagementController;
use App\Http\Controllers\Api\PlanController;
use App\Http\Controllers\Api\PurchaseRequestController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PortfolioController;
use App\Http\Controllers\Api\NavRecordController;
use App\Http\Controllers\Api\CutoffTimeRuleController;
use App\Http\Controllers\Api\BusinessHolidayController;
use App\Http\Controllers\Api\FundController;
use App\Http\Controllers\Api\PlanCategoryController;





Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/email/verification-notification', [AuthController::class, 'sendVerificationEmail']);

    Route::post('/company-directors/{companyDirector}/verify-identity', [DirectorVerificationController::class, 'verifyIdentity']);

    Route::post('/investor-documents/{investorDocument}/verify', [InvestorDocumentController::class, 'verify']);
    Route::post('/investor-documents/{investorDocument}/reject', [InvestorDocumentController::class, 'reject']);
    Route::get('/investor-documents/{investorDocument}/view', [InvestorDocumentController::class, 'view']);
    Route::get('/investor-documents/{investorDocument}/download', [InvestorDocumentController::class, 'download']);
    Route::get('/investor-documents/{investorDocument}/history', [InvestorDocumentController::class, 'history']);
    Route::get('/kyc/pending-queue', [KycOperationsController::class, 'pendingQueue']);
    Route::get('/kyc/missing-documents-queue', [KycOperationsController::class, 'missingDocumentsQueue']);
    Route::get('/kyc/rejected-documents-queue', [KycOperationsController::class, 'rejectedDocumentsQueue']);
    Route::get('/kyc/escalated-queue', [KycOperationsController::class, 'escalatedQueue']);
    Route::get('/investors/{investor}/kyc-case-assignments', [KycCaseManagementController::class, 'assignments']);
    Route::post('/investors/{investor}/kyc-case-assign', [KycCaseManagementController::class, 'assign']);
    Route::get('/investors/{investor}/kyc-case-notes', [KycCaseManagementController::class, 'notes']);
    Route::post('/investors/{investor}/kyc-case-notes', [KycCaseManagementController::class, 'storeNote']);
    Route::post('/plans/{plan}/check-purchase-eligibility', [PlanController::class, 'checkPurchaseEligibility']);
    Route::post('/plans/{plan}/purchase-eligibility', [PlanController::class, 'purchaseEligibility']);
    Route::post('/plans/{plan}/purchase-preview', [PlanController::class, 'purchasePreview']);
    Route::get('/purchase-requests', [PurchaseRequestController::class, 'index']);
    Route::post('/purchase-requests', [PurchaseRequestController::class, 'store']);
    Route::post('/purchase-requests/{purchaseRequest}/initialize-payment', [PaymentController::class, 'initialize']);
    Route::post('/purchase-requests/{purchaseRequest}/allocate', [PurchaseRequestController::class, 'allocate']);
    Route::post('/payments/mock-callback', [PaymentController::class, 'mockCallback']);
    Route::get('/portfolio/summary', [PortfolioController::class, 'summary']);
    Route::get('/portfolio/holdings', [PortfolioController::class, 'holdings']);
    Route::get('/portfolio/transactions', [PortfolioController::class, 'transactions']);
    Route::get('/nav-records', [NavRecordController::class, 'index']);
    Route::post('/nav-records', [NavRecordController::class, 'store']);
    Route::get('/nav-records/{navRecord}', [NavRecordController::class, 'show']);
    Route::post('/nav-records/{navRecord}/approve', [NavRecordController::class, 'approve']);
    Route::post('/nav-records/{navRecord}/publish', [NavRecordController::class, 'publish']);
    Route::get('/cutoff-time-rules', [CutoffTimeRuleController::class, 'index']);
    Route::post('/cutoff-time-rules', [CutoffTimeRuleController::class, 'store']);
    Route::post('/cutoff-time-rules/{cutoffTimeRule}/approve', [CutoffTimeRuleController::class, 'approve']);
    Route::post('/cutoff-time-rules/{cutoffTimeRule}/activate', [CutoffTimeRuleController::class, 'activate']);
    Route::get('/business-holidays', [BusinessHolidayController::class, 'index']);
    Route::post('/business-holidays', [BusinessHolidayController::class, 'store']);
    Route::patch('/business-holidays/{businessHoliday}', [BusinessHolidayController::class, 'update']);
    Route::get('/plans', [PlanController::class, 'index']);
    Route::get('/plans/{plan}', [PlanController::class, 'show']);
    Route::post('/plans', [PlanController::class, 'store']);
    Route::patch('/plans/{plan}', [PlanController::class, 'update']);
    Route::post('/plans/{plan}/rules', [PlanController::class, 'storeRule']);
    Route::patch('/plans/{plan}/rules/{planRule}', [PlanController::class, 'updateRule']);
    Route::post('/plans/{plan}/purchase-eligibility', [PlanController::class, 'purchaseEligibility']);
    Route::post('/plans/{plan}/purchase-preview', [PlanController::class, 'purchasePreview']);
    Route::get('/funds', [FundController::class, 'index']);
    Route::get('/plan-categories', [PlanCategoryController::class, 'index']);
    
  



    
});

Route::middleware(['auth:sanctum'])->prefix('investors')->group(function () {
    Route::get('/', [InvestorController::class, 'index']);
    Route::post('/', [InvestorController::class, 'store']);
    Route::get('/{investor}', [InvestorController::class, 'show']);
    Route::post('/{investor}/approve', [InvestorController::class, 'approve']);
    Route::post('/{investor}/reject', [InvestorController::class, 'reject']);
    Route::post('/{investor}/documents', [InvestorDocumentController::class, 'store']);
    Route::get('/{investor}/directors', [CompanyDirectorController::class, 'index']);
    Route::post('/{investor}/directors', [CompanyDirectorController::class, 'store']);
    Route::get('/{investor}/kyc-summary', [KycController::class, 'summary']);
    Route::post('/{investor}/sync-kyc', [KycController::class, 'sync']);
    Route::post('/{investor}/verify-identity', [InvestorVerificationController::class, 'verifyIdentity']);
    Route::get('/{investor}/kyc-reviews', [KycReviewController::class, 'index']);
    Route::post('/{investor}/kyc-review', [KycReviewController::class, 'store']);

});

Route::middleware(['auth:sanctum'])->prefix('investor-categories')->group(function () {
    Route::get('/', [InvestorCategoryController::class, 'index']);
    Route::get('/{investorCategory}/document-requirements', [InvestorCategoryController::class, 'documentRequirements']);
});


Route::middleware(['auth:sanctum'])->group(function () {

    Route::post(
        '/company-directors/{companyDirector}/verify-identity',
        [DirectorVerificationController::class, 'verifyIdentity']
    );

});