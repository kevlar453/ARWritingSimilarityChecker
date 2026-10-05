<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->match(['get', 'head'], '/', 'DashboardController::index');
$routes->match(['get', 'head'], 'dashboard', 'DashboardController::index');
$routes->match(['get', 'head'], 'report/(:num)', 'DashboardController::report/$1');
$routes->match(['get', 'head'], 'dashboard/report/(:num)', 'DashboardController::report/$1');
$routes->match(['get', 'head'], 'report/(:num)/export', 'DashboardController::exportPdf/$1');
$routes->match(['get', 'head'], 'dashboard/report/(:num)/export', 'DashboardController::exportPdf/$1');
$routes->get('report/(:num)/preview', 'DashboardController::previewPdf/$1');
$routes->get('dashboard/report/(:num)/preview', 'DashboardController::previewPdf/$1');

// API Routes
$routes->group('api', function ($routes) {
    // Documents
    $routes->post('documents', 'Api\DocumentController::create');
    $routes->get('documents/(:num)', 'Api\DocumentController::show/$1');
    $routes->post('documents/(:num)/check', 'Api\DocumentController::check/$1');
    $routes->post('documents/(:num)/enroll', 'Api\DocumentController::enroll/$1');

    // Reports
    $routes->get('reports/(:num)', 'Api\ReportController::show/$1');
    $routes->get('reports/(:num)/sources', 'Api\ReportController::sources/$1');
    $routes->get('reports/(:num)/matches', 'Api\ReportController::matches/$1');
});

// Admin Corpus Routes
$routes->group('admin/corpus', function ($routes) {
    $routes->get('/', 'Admin\CorpusController::index');
    $routes->post('/', 'Admin\CorpusController::create');
    $routes->post('sync-dropzone', 'Admin\CorpusController::syncDropzone');
    $routes->post('auto-discover', 'Admin\CorpusController::autoDiscover');
    $routes->post('enroll/(:num)', 'Api\DocumentController::enroll/$1');
    $routes->get('(:num)', 'Admin\CorpusController::show/$1');
    $routes->delete('(:num)', 'Admin\CorpusController::delete/$1');
    $routes->post('(:num)/reindex', 'Admin\CorpusController::reindex/$1');
});

// Human Review & Evaluation Subsystem (Phase HR-1 Isolated Module)
$routes->group('evaluation', function ($routes) {
    $routes->get('/', 'EvaluationController::index');
    $routes->get('summary', 'EvaluationController::summary');
    $routes->get('quality', 'EvaluationController::quality');
    $routes->get('(:segment)', 'EvaluationController::detail/$1');
    $routes->post('(:segment)/save', 'EvaluationController::save/$1');
});
