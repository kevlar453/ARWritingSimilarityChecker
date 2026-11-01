<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Proteksi CSRF berbasis Fetch Metadata (Sec-Fetch-Site) & Verifikasi Origin Header (OWASP Defense-in-Depth)
 * Memblokir permintaan pengubah state (POST/PUT/DELETE/PATCH) lintas situs tanpa merusak klien API / AJAX internal.
 */
class SameOriginFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $method = strtoupper($request->getMethod());
        if (! in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH'], true)) {
            return null;
        }

        $uriPath = ltrim( strtolower($request->getUri()->getPath()), '/' );
        $exemptPrefixes = [
            'payment/webhook',
            'api/v1/',
            'api/integration/',
            'email/unsubscribe',
            'unsubscribe',
        ];
        foreach ($exemptPrefixes as $prefix) {
            if (strpos($uriPath, $prefix) === 0) {
                return null;
            }
        }

        // 1. Evaluasi W3C Fetch Metadata (Sec-Fetch-Site)
        $secFetchSite = strtolower(trim($request->getHeaderLine('Sec-Fetch-Site')));
        if ($secFetchSite === 'cross-site') {
            return service('response')
                ->setStatusCode(403)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'Permintaan lintas situs (Cross-Site Request Forgery) ditolak oleh kebijakan keamanan AR Tilik.',
                ]);
        }

        // 2. Evaluasi kesesuaian header Origin jika dikirimkan oleh peramban
        $origin = trim($request->getHeaderLine('Origin'));
        if ($origin !== '' && strtolower($origin) !== 'null') {
            $originHost = strtolower((string) parse_url($origin, PHP_URL_HOST));
            $hostHeader = strtolower(trim($request->getHeaderLine('Host')));
            $currentHost = explode(':', $hostHeader)[0];

            if ($originHost !== '' && $currentHost !== '' && $originHost !== $currentHost) {
                return service('response')
                    ->setStatusCode(403)
                    ->setJSON([
                        'status'  => 'error',
                        'message' => 'Origin permintaan tidak sesuai dengan domain resmi peladen.',
                    ]);
            }
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}
