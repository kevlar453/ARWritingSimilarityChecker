<?php

namespace App\Controllers;

use CodeIgniter\HTTP\ResponseInterface;

class SitemapController extends BaseController
{
    /**
     * Menghasilkan dynamic XML Sitemap untuk search engine (Google, Bing, Yandex)
     */
    public function index(): ResponseInterface
    {
        $baseUrl = rtrim(base_url(), '/');
        $today = date('Y-m-d');

        $urls = [
            [
                'loc'        => $baseUrl . '/',
                'lastmod'    => $today,
                'changefreq' => 'daily',
                'priority'   => '1.0',
            ],
            [
                'loc'        => $baseUrl . '/verify',
                'lastmod'    => $today,
                'changefreq' => 'weekly',
                'priority'   => '0.8',
            ],
            [
                'loc'        => $baseUrl . '/ambassador',
                'lastmod'    => $today,
                'changefreq' => 'weekly',
                'priority'   => '0.9',
            ],
            [
                'loc'        => $baseUrl . '/ambassador/guide',
                'lastmod'    => $today,
                'changefreq' => 'weekly',
                'priority'   => '0.8',
            ],
            [
                'loc'        => $baseUrl . '/integrations/ojs/pdf',
                'lastmod'    => '2026-10-07',
                'changefreq' => 'weekly',
                'priority'   => '0.8',
            ],
            [
                'loc'        => $baseUrl . '/integrations/ojs/connector',
                'lastmod'    => '2026-10-07',
                'changefreq' => 'weekly',
                'priority'   => '0.8',
            ],
            [
                'loc'        => $baseUrl . '/docs/Brosur_Eksekutif_ARTilik.pdf',
                'lastmod'    => $today,
                'changefreq' => 'weekly',
                'priority'   => '0.8',
            ],
            [
                'loc'        => $baseUrl . '/privacy',
                'lastmod'    => '2026-10-07',
                'changefreq' => 'monthly',
                'priority'   => '0.6',
            ],
            [
                'loc'        => $baseUrl . '/terms',
                'lastmod'    => '2026-10-07',
                'changefreq' => 'monthly',
                'priority'   => '0.6',
            ],
        ];

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . "\n";
        $xml .= '        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"' . "\n";
        $xml .= '        xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">' . "\n";

        foreach ($urls as $u) {
            $xml .= '    <url>' . "\n";
            $xml .= '        <loc>' . htmlspecialchars($u['loc'], ENT_XML1, 'UTF-8') . '</loc>' . "\n";
            $xml .= '        <lastmod>' . $u['lastmod'] . '</lastmod>' . "\n";
            $xml .= '        <changefreq>' . $u['changefreq'] . '</changefreq>' . "\n";
            $xml .= '        <priority>' . $u['priority'] . '</priority>' . "\n";
            $xml .= '    </url>' . "\n";
        }

        $xml .= '</urlset>';

        return $this->response
            ->setHeader('Content-Type', 'application/xml; charset=utf-8')
            ->setHeader('Cache-Control', 'public, max-age=86400, s-maxage=86400')
            ->setBody($xml);
    }
}
