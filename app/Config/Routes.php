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
$routes->match(['get', 'head'], 'verify/(:segment)', 'DashboardController::verify/$1');
$routes->match(['get', 'head'], 'verify', 'DashboardController::verify');
$routes->match(['get', 'head'], 'integrations/ojs/pdf', 'DashboardController::downloadOjsPdf');
$routes->match(['get', 'head'], 'docs/ojs/pdf', 'DashboardController::downloadOjsPdf');

// Legal & Contact Routes
$routes->match(['get', 'head'], 'privacy', 'LegalController::privacy');
$routes->match(['get', 'head'], 'terms', 'LegalController::terms');
$routes->get('contact/challenge', 'LegalController::challenge');
$routes->match(['get', 'post'], 'contact/validate-email', 'LegalController::validateEmail');
$routes->post('contact/send', 'LegalController::sendMessage');

// Auth Routes
$routes->group('auth', function ($routes) {
    $routes->post('register', 'AuthController::register');
    $routes->post('login', 'AuthController::login');
    $routes->get('logout', 'AuthController::logout');
    $routes->post('logout', 'AuthController::logout');
    $routes->get('status', 'AuthController::status');
    $routes->get('verify-email', 'AuthController::verifyEmail');
    $routes->post('resend-verification', 'AuthController::resendVerification');
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
$routes->group('api-keys', function ($routes) {
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
});

// Human Review & Evaluation Subsystem (Phase HR-1 Isolated Module)
$routes->group('evaluation', function ($routes) {
    $routes->get('/', 'EvaluationController::index');
    $routes->get('summary', 'EvaluationController::summary');
    $routes->get('quality', 'EvaluationController::quality');
    $routes->get('(:segment)', 'EvaluationController::detail/$1');
    $routes->post('(:segment)/save', 'EvaluationController::save/$1');
});
