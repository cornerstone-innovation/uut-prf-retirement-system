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
use App\Http\Controllers\Api\Public\InvestorOnboardingController;
use App\Http\Controllers\Api\Public\InvestorOtpController;
use App\Http\Controllers\Api\Public\InvestorNidaVerificationController;
use App\Http\Controllers\Api\UserManagementController;
use App\Http\Controllers\Api\AccessControlController;
use App\Http\Controllers\Api\Public\ClickPesaWebhookController;
use App\Http\Controllers\Api\AdminDashboardController;
use App\Http\Controllers\Api\Admin\MarketSecurityController;
use App\Http\Controllers\Api\Admin\PlanEquityHoldingController;
use App\Http\Controllers\Api\Admin\PlanBondHoldingController;
use App\Http\Controllers\Api\Admin\PlanCashPositionController;
use App\Http\Controllers\Api\Admin\PlanUnitSummaryController;
use App\Http\Controllers\Api\Admin\MarketSecurityPriceSnapshotController;
use App\Http\Controllers\Api\Admin\PlanValuationController;




Route::post('/login', [AuthController::class, 'login']);
Route::post('/investor/login', [AuthController::class, 'investorLogin']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);



// Public webhook route
Route::post('/webhooks/clickpesa', [ClickPesaWebhookController::class, 'handle']);
Route::get('/purchase-requests/{purchaseRequest}/payment-status', [PaymentController::class, 'latestStatus']);



Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/email/verification-notification', [AuthController::class, 'sendVerificationEmail']);

    Route::get('/admin/market-securities/search', [MarketSecurityController::class, 'search']);
    Route::post('/admin/market-securities/sync-selected', [MarketSecurityController::class, 'syncSelected']);
     Route::get('/plans/{plan}/equity-holdings', [PlanEquityHoldingController::class, 'index']);
    Route::post('/plans/{plan}/equity-holdings', [PlanEquityHoldingController::class, 'store']);

    Route::get('/plans/{plan}/bond-holdings', [PlanBondHoldingController::class, 'index']);
    Route::post('/plans/{plan}/bond-holdings', [PlanBondHoldingController::class, 'store']);

    Route::get('/plans/{plan}/cash-positions', [PlanCashPositionController::class, 'index']);
    Route::post('/plans/{plan}/cash-positions', [PlanCashPositionController::class, 'store']);

    Route::get('/plans/{plan}/unit-summary', [PlanUnitSummaryController::class, 'show']);
    Route::post('/admin/market-securities/sync-price-snapshots', [MarketSecurityPriceSnapshotController::class, 'sync']);
    Route::get('/plans/{plan}/valuations', [PlanValuationController::class, 'index']);
    Route::post('/plans/{plan}/calculate-nav', [PlanValuationController::class, 'calculate']);

    Route::patch('/plans/{plan}/equity-holdings/{equityHolding}', [PlanEquityHoldingController::class, 'update']);
    Route::delete('/plans/{plan}/equity-holdings/{equityHolding}', [PlanEquityHoldingController::class, 'destroy']);

    Route::patch('/plans/{plan}/bond-holdings/{bondHolding}', [PlanBondHoldingController::class, 'update']);
    Route::delete('/plans/{plan}/bond-holdings/{bondHolding}', [PlanBondHoldingController::class, 'destroy']);

    Route::patch('/plans/{plan}/cash-positions/{cashPosition}', [PlanCashPositionController::class, 'update']);
    Route::delete('/plans/{plan}/cash-positions/{cashPosition}', [PlanCashPositionController::class, 'destroy']);
    Route::get('/plans/{plan}/valuation-snapshot/latest', [PlanValuationController::class, 'latest']);
   
    Route::post('/plans/{plan}/calculate-nav-preview', [PlanValuationController::class, 'preview']);
    Route::post('/plans/{plan}/nav-records/from-calculation', [PlanValuationController::class, 'acceptPreview']);   




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
    Route::get('/purchase-requests/{purchaseRequest}/latest-payment', [PurchaseRequestController::class, 'showLatestPayment']);
    Route::post('/purchase-requests/{purchaseRequest}/initialize-payment', [PaymentController::class, 'initialize']);
    Route::post('/purchase-requests/{purchaseRequest}/allocate', [PurchaseRequestController::class, 'allocate']);
    Route::post('/payments/{payment}/sync-status', [PaymentController::class, 'syncStatus']);
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
  

    Route::get('/users', [UserManagementController::class, 'index']);
    Route::get('/users/{user}', [UserManagementController::class, 'show']);
    Route::post('/users', [UserManagementController::class, 'store']);
    Route::patch('/users/{user}', [UserManagementController::class, 'update']);
    Route::patch('/users/{user}/status', [UserManagementController::class, 'updateStatus']);
    Route::get('/roles', [AccessControlController::class, 'roles']);
    Route::get('/permissions', [AccessControlController::class, 'permissions']);       
    Route::post('/purchase-requests/{purchaseRequest}/reconfirm', [PurchaseRequestController::class, 'reconfirm']); 
    Route::get('/dashboard/summary', [AdminDashboardController::class, 'summary']);
  



    
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


Route::prefix('investor-onboarding')->group(function () {
    Route::post('/start', [InvestorOnboardingController::class, 'start']);
    Route::post('/verify-nida', [InvestorNidaVerificationController::class, 'verify']);
    Route::post('/send-otp', [InvestorOtpController::class, 'send']);
    Route::post('/verify-otp', [InvestorOtpController::class, 'verify']);
    Route::post('/check-email', [InvestorOnboardingController::class, 'checkEmailAvailability']);
    Route::post('/complete-registration', [InvestorOnboardingController::class, 'complete']);
});