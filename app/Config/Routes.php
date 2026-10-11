<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->match(['get', 'head'], '/', 'LandingController::index');
$routes->match(['get', 'head'], 'dashboard', 'DashboardController::index');
$routes->match(['get', 'head'], 'report/(:num)', 'DashboardController::report/$1');
$routes->match(['get', 'head'], 'reports/(:num)', 'DashboardController::report/$1');
$routes->match(['get', 'head'], 'dashboard/report/(:num)', 'DashboardController::report/$1');
$routes->match(['get', 'head'], 'dashboard/reports/(:num)', 'DashboardController::report/$1');
$routes->match(['get', 'head'], 'report/(:num)/export', 'DashboardController::exportPdf/$1');
$routes->match(['get', 'head'], 'reports/(:num)/export', 'DashboardController::exportPdf/$1');
$routes->match(['get', 'head'], 'dashboard/report/(:num)/export', 'DashboardController::exportPdf/$1');
$routes->match(['get', 'head'], 'dashboard/reports/(:num)/export', 'DashboardController::exportPdf/$1');
$routes->get('report/(:num)/preview', 'DashboardController::previewPdf/$1');
$routes->get('reports/(:num)/preview', 'DashboardController::previewPdf/$1');
$routes->get('dashboard/report/(:num)/preview', 'DashboardController::previewPdf/$1');
$routes->get('dashboard/reports/(:num)/preview', 'DashboardController::previewPdf/$1');
$routes->post('dashboard/audit/run/(:num)', 'DashboardController::runAudit/$1');
$routes->post('audit/run/(:num)', 'DashboardController::runAudit/$1');
$routes->match(['get', 'head'], 'documents/(:num)/preview', 'DashboardController::previewDocument/$1');
$routes->match(['get', 'head'], 'dashboard/documents/(:num)/preview', 'DashboardController::previewDocument/$1');
$routes->match(['get', 'head'], 'documents/shared/(:segment)', 'DashboardController::sharedAccess/$1');
$routes->match(['get', 'head'], 'dashboard/documents/shared/(:segment)', 'DashboardController::sharedAccess/$1');
$routes->post('dashboard/documents/(:num)/purge-file', 'DashboardController::purgeDocumentFile/$1');
$routes->post('dashboard/documents/(:num)/delete', 'DashboardController::deleteUserDocument/$1');
$routes->match(['get', 'head'], 'verify/(:segment)/pdf', 'DashboardController::downloadCertificatePdf/$1');
$routes->match(['get', 'head'], 'dashboard/reports/(:num)/certificate-pdf', 'DashboardController::downloadCertificatePdfByReportId/$1');
$routes->match(['get', 'head'], 'verify/(:segment)', 'DashboardController::verify/$1');
$routes->match(['get', 'head'], 'verify', 'DashboardController::verify');
$routes->match(['get', 'head'], 'integrations/ojs/pdf', 'DashboardController::downloadOjsPdf');
$routes->match(['get', 'head'], 'docs/ojs/pdf', 'DashboardController::downloadOjsPdf');
$routes->match(['get', 'head'], 'integrations/ojs/connector', 'DashboardController::downloadOjsConnector');
$routes->match(['get', 'head'], 'docs/ojs/connector', 'DashboardController::downloadOjsConnector');
$routes->match(['get', 'head'], 'integrations/ojs/ARTilikOJSConnector.php', 'DashboardController::downloadOjsConnector');
$routes->match(['get', 'head'], 'integrations/ojs/runner', 'DashboardController::downloadOjsRunner');
$routes->match(['get', 'head'], 'docs/ojs/runner', 'DashboardController::downloadOjsRunner');
$routes->match(['get', 'head'], 'integrations/ojs/artilik_ojs_runner.php', 'DashboardController::downloadOjsRunner');

// Media Promosi & Prospektus Eksekutif Institusi (PDF)
$routes->match(['get', 'head'], 'docs/brosur', 'DashboardController::downloadPromoBrochure');
$routes->match(['get', 'head'], 'promosi/brosur', 'DashboardController::downloadPromoBrochure');
$routes->match(['get', 'head'], 'brosur', 'DashboardController::downloadPromoBrochure');
$routes->match(['get', 'head'], 'docs/Brosur_Eksekutif_ARTilik.pdf', 'DashboardController::downloadPromoBrochure');
$routes->match(['get', 'head'], 'promosi/Brosur_Eksekutif_ARTilik.pdf', 'DashboardController::downloadPromoBrochure');

$routes->match(['get', 'head'], 'sitemap.xml', 'SitemapController::index');

// Legal & Contact Routes
$routes->match(['get', 'head'], 'privacy', 'LegalController::privacy');
$routes->match(['get', 'head'], 'terms', 'LegalController::terms');
$routes->get('contact/challenge', 'LegalController::challenge');
$routes->match(['get', 'post'], 'contact/validate-email', 'LegalController::validateEmail');
$routes->post('contact/send', 'LegalController::sendMessage');

// Feedback & Testimonials Routes
$routes->post('feedback/vote', 'FeedbackController::vote');
$routes->post('feedback/testimonial', 'FeedbackController::submitTestimonial');
$routes->get('feedback/status/(:num)', 'FeedbackController::getStatus/$1');

// Ambassador & Referral Program Routes (Fase 2)
$routes->match(['get', 'head'], 'ambassador', 'AmbassadorController::index');
$routes->match(['get', 'head'], 'ambassador/guide', 'AmbassadorController::guide');
$routes->post('ambassador/apply', 'AmbassadorController::apply');
$routes->match(['get', 'head'], 'ambassador/verify/(:segment)', 'AmbassadorController::verify/$1');
$routes->post('ambassador/verify/(:segment)', 'AmbassadorController::submitVerification/$1');
$routes->match(['get', 'head'], 'ambassador/dashboard', 'AmbassadorController::dashboard');
$routes->post('ambassador/proof', 'AmbassadorController::submitProof');
$routes->post('ambassador/payout', 'AmbassadorController::requestPayout');
$routes->get('ref/(:segment)', 'AmbassadorController::ref/$1');

// Shortcut / Alias Friendly Routes
$routes->match(['get', 'head'], 'pricing', static function () {
    return redirect()->to(base_url('/#langganan'));
});
$routes->match(['get', 'head'], 'login', static function () {
    $redirect = service('request')->getGet('redirect');
    $target = base_url('/?auth=login');
    if (!empty($redirect)) {
        $target .= '&redirect=' . urlencode((string) $redirect);
    }
    return redirect()->to($target);
});

// Auth Routes
$routes->group('auth', function ($routes) {
    $routes->post('register', 'AuthController::register');
    $routes->post('login', 'AuthController::login');
    $routes->get('logout', 'AuthController::logout');
    $routes->post('logout', 'AuthController::logout');
    $routes->get('status', 'AuthController::status');
    $routes->match(['get', 'head'], 'verify-email', 'AuthController::verifyEmail');
    $routes->post('resend-verification', 'AuthController::resendVerification');
    $routes->post('forgot-password', 'AuthController::forgotPassword');
    $routes->match(['get', 'head'], 'reset-password', 'AuthController::resetPasswordView');
    $routes->post('reset-password', 'AuthController::resetPassword');
    $routes->get('security-token', 'AuthController::securityToken');
});

// Payment & Top-Up Routes (Midtrans Snap & Webhook)
$routes->group('payment', function ($routes) {
    $routes->get('packages', 'PaymentController::packages');
    $routes->post('create-snap', 'PaymentController::createSnapToken');
    $routes->post('webhook', 'PaymentController::webhook');
    $routes->get('status/(:segment)', 'PaymentController::checkStatus/$1');
    $routes->get('history', 'PaymentController::history');
});

// API Routes
$routes->group('api', function ($routes) {
    // Documents
    $routes->post('documents', 'Api\DocumentController::create');
    $routes->get('documents/(:num)', 'Api\DocumentController::show/$1');
    $routes->post('documents/(:num)/check', 'Api\DocumentController::check/$1');
    $routes->post('documents/(:num)/enroll', 'Api\DocumentController::enroll/$1');
    $routes->post('integration/check', 'Api\DocumentController::checkSubmission');
    $routes->post('corpus/contribute', 'Api\DocumentController::contributeCorpus');

    // Document Sharing (Model A+B)
    $routes->get('documents/(:num)/shares', 'Api\DocumentController::listShares/$1');
    $routes->post('documents/(:num)/shares', 'Api\DocumentController::addShare/$1');
    $routes->delete('documents/(:num)/shares/(:num)', 'Api\DocumentController::deleteShare/$1/$2');
    $routes->post('documents/(:num)/share-link', 'Api\DocumentController::toggleShareLink/$1');

    // Public REST API v1 & OJS Integration Endpoints
    $routes->group('v1', function ($routes) {
        $routes->post('check', 'Api\PublicApiController::checkSubmission');
        $routes->get('status/(:num)', 'Api\PublicApiController::getStatus/$1');
        $routes->get('report/(:num)', 'Api\PublicApiController::getReport/$1');
        $routes->get('quota', 'Api\PublicApiController::getQuota');
    });

    // Reports
    $routes->get('reports/(:num)', 'Api\ReportController::show/$1');
    $routes->get('reports/(:num)/sources', 'Api\ReportController::sources/$1');
    $routes->get('reports/(:num)/matches', 'Api\ReportController::matches/$1');
});

// API Key Management Routes (Protected by Auth/Session)
$routes->group('api-keys', ['filter' => 'auth'], function ($routes) {
    $routes->get('list', 'Api\ApiKeyController::listKeys');
    $routes->post('generate', 'Api\ApiKeyController::generate');
    $routes->post('revoke/(:num)', 'Api\ApiKeyController::revoke/$1');
});

// Admin Portal & Management Routes (Protected by AdminFilter)
$routes->group('admin', ['filter' => 'admin'], function ($routes) {
    $routes->get('/', 'Admin\DashboardController::index');
    $routes->post('users/update', 'Admin\DashboardController::updateUser');
    $routes->post('documents/reprocess', 'Admin\DashboardController::reprocessDocument');
    $routes->post('documents/delete', 'Admin\DashboardController::deleteDocument');
    $routes->post('corpus/visibility', 'Admin\DashboardController::updateCorpusVisibility');
    $routes->post('security/unblock', 'Admin\DashboardController::unblockSecurity');
    $routes->post('security/clear-all', 'Admin\DashboardController::clearAllLocks');
    $routes->get('analytics/export-csv', 'Admin\DashboardController::exportIntegrityCsv');
    $routes->get('analytics/visitors-realtime', 'Admin\DashboardController::getVisitorsRealtime');

    // Corpus management API routes
    $routes->get('corpus/list', 'Admin\CorpusController::index');
    $routes->post('corpus', 'Admin\CorpusController::create');
    $routes->post('corpus/sync-dropzone', 'Admin\CorpusController::syncDropzone');
    $routes->post('corpus/auto-discover', 'Admin\CorpusController::autoDiscover');
    $routes->post('corpus/harvest-neliti', 'Admin\CorpusController::harvestNeliti');
    $routes->post('corpus/harvest-garuda', 'Admin\CorpusController::harvestGaruda');
    $routes->post('corpus/harvest-scheduled', 'Admin\CorpusController::harvestScheduled');
    $routes->get('corpus/harvest-logs', 'Admin\CorpusController::harvestLogs');
    $routes->post('corpus/harvest-schedule-update', 'Admin\CorpusController::updateHarvestSchedule');
    $routes->post('corpus/enroll/(:num)', 'Api\DocumentController::enroll/$1');
    $routes->get('corpus/(:num)', 'Admin\CorpusController::show/$1');
    $routes->delete('corpus/(:num)', 'Admin\CorpusController::delete/$1');
    $routes->post('corpus/(:num)/reindex', 'Admin\CorpusController::reindex/$1');

    // Google AI / Gemini API Keys Management (Protected by AdminFilter)
    $routes->post('google-keys', 'Admin\GoogleKeyController::create');
    $routes->post('google-keys/create', 'Admin\GoogleKeyController::create');
    $routes->post('google-keys/toggle/(:num)', 'Admin\GoogleKeyController::toggle/$1');
    $routes->delete('google-keys/(:num)', 'Admin\GoogleKeyController::delete/$1');
    $routes->post('google-keys/delete/(:num)', 'Admin\GoogleKeyController::delete/$1');
    $routes->post('google-keys/test/(:num)', 'Admin\GoogleKeyController::testConnection/$1');

    // Feedback & Testimonials Moderation (Protected by AdminFilter)
    $routes->post('testimonials/approve/(:num)', 'Admin\DashboardController::approveTestimonial/$1');
    $routes->post('testimonials/reject/(:num)', 'Admin\DashboardController::rejectTestimonial/$1');
    $routes->post('testimonials/feature/(:num)', 'Admin\DashboardController::toggleFeaturedTestimonial/$1');
    $routes->post('testimonials/delete/(:num)', 'Admin\DashboardController::deleteTestimonial/$1');

    // Ambassador Batch Review & Activation & Proof Verification (Tahap 2, 4, 5, 6)
    $routes->post('ambassador/accept/(:num)', 'Admin\AmbassadorAdminController::acceptApplication/$1');
    $routes->post('ambassador/reject/(:num)', 'Admin\AmbassadorAdminController::rejectApplication/$1');
    $routes->post('ambassador/activate/(:num)', 'Admin\AmbassadorAdminController::activateAmbassador/$1');
    $routes->post('ambassador/proof/approve/(:num)', 'Admin\AmbassadorAdminController::approveProof/$1');
    $routes->post('ambassador/proof/reject/(:num)', 'Admin\AmbassadorAdminController::rejectProof/$1');
    $routes->get('ambassador/proof/file/(:num)', 'Admin\AmbassadorAdminController::viewProofFile/$1');
    $routes->get('ambassador/ktm/file/(:num)', 'Admin\AmbassadorAdminController::viewKtmFile/$1');

    // Ambassador Payout Management
    $routes->get('ambassador/payouts', 'Admin\AmbassadorAdminController::listPayouts');
    $routes->post('ambassador/payouts/(:num)/process', 'Admin\AmbassadorAdminController::processPayout/$1');
    $routes->get('ambassador/payouts/proof/(:num)', 'Admin\AmbassadorAdminController::viewTransferProof/$1');
});

// Human Review & Evaluation Subsystem (DINONAKTIFKAN - mitigasi risiko RCE lewat exec)
// $routes->group('evaluation', ['filter' => 'admin'], function ($routes) {
//     $routes->get('/', 'EvaluationController::index');
//     $routes->get('summary', 'EvaluationController::summary');
//     $routes->get('quality', 'EvaluationController::quality');
//     $routes->get('(:segment)', 'EvaluationController::detail/$1');
//     $routes->post('(:segment)/save', 'EvaluationController::save/$1');
// });

