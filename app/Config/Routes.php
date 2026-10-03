<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'DashboardController::index');
$routes->get('dashboard', 'DashboardController::index');
$routes->get('report/(:num)', 'DashboardController::report/$1');
$routes->get('dashboard/report/(:num)', 'DashboardController::report/$1');
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

    // Reports
    $routes->get('reports/(:num)', 'Api\ReportController::show/$1');
    $routes->get('reports/(:num)/sources', 'Api\ReportController::sources/$1');
    $routes->get('reports/(:num)/matches', 'Api\ReportController::matches/$1');
});

// Admin Corpus Routes
$routes->group('admin/corpus', function ($routes) {
    $routes->get('/', 'Admin\CorpusController::index');
    $routes->post('/', 'Admin\CorpusController::create');
    $routes->get('(:num)', 'Admin\CorpusController::show/$1');
    $routes->delete('(:num)', 'Admin\CorpusController::delete/$1');
    $routes->post('(:num)/reindex', 'Admin\CorpusController::reindex/$1');
});
