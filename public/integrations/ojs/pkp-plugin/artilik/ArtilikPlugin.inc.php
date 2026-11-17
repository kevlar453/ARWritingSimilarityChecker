<?php
/**
 * @file plugins/generic/artilik/ArtilikPlugin.inc.php
 *
 * Copyright (c) 2026 AR Tilik Development Team
 * Distributed under the GNU GPL v3.
 *
 * @class ArtilikPlugin
 * @ingroup plugins_generic_artilik
 *
 * @brief Kelas utama plugin generic PKP OJS 3.x untuk AR Tilik
 */

import('lib.pkp.classes.plugins.GenericPlugin');
require_once(__DIR__ . '/ARTilikOJSConnector.php');

class ArtilikPlugin extends GenericPlugin {

    /** @var ArtilikPlugin|null */
    private static $activeInstance = null;

    /**
     * Mengembalikan instance plugin aktif untuk handler webhook.
     */
    public static function getActiveInstance() {
        return self::$activeInstance;
    }

    /**
     * Daftarkan plugin ke OJS
     */
    public function register($category, $path, $mainContextId = null) {
        $success = parent::register($category, $path, $mainContextId);
        if ($success && $this->getEnabled($mainContextId)) {
            self::$activeInstance = $this;
            // Pasang hook handler webhook callback & hook editorial decision / workflow display
            \HookRegistry::register('LoadHandler', [$this, 'callbackLoadHandler']);
            \HookRegistry::register('EditorAction::recordDecision', [$this, 'callbackRecordDecision']);
            \HookRegistry::register('TemplateManager::display', [$this, 'callbackTemplateDisplay']);
        }
        return $success;
    }

    /**
     * Menghubungkan rute /<context>/artilik/webhook ke ArtilikHandler::webhook
     */
    public function callbackLoadHandler($hookName, $args) {
        $page = $args[0] ?? '';
        $op   = $args[1] ?? '';

        if ($page === 'artilik' && $op === 'webhook') {
            if (! defined('HANDLER_CLASS')) {
                define('HANDLER_CLASS', 'ArtilikHandler');
            }
            require_once(__DIR__ . '/ArtilikHandler.inc.php');
            if (is_array($args) && array_key_exists(2, $args)) {
                $args[2] = $this->getPluginPath() . '/ArtilikHandler.inc.php';
            }
            return true;
        }

        return false;
    }

    /**
     * Nama tampilan plugin di daftar plugin OJS
     */
    public function getDisplayName() {
        return 'AR Tilik Writing Similarity & AI Forensic Checker';
    }

    /**
     * Deskripsi fungsi plugin
     */
    public function getDescription() {
        return 'Modul integrasi otomatis PKP OJS 3.x dengan mesin orisinalitas Tiga Lensa AR Tilik dan Audit Forensik Cerdas AI.';
    }

    /**
     * Tampilkan tombol 'Settings' pada daftar Installed Plugins
     */
    public function getActions($request, $actionArgs) {
        $actions = parent::getActions($request, $actionArgs);
        if (! $this->getEnabled()) {
            return $actions;
        }

        $router = $request->getRouter();
        import('lib.pkp.classes.linkAction.request.AjaxModal');

        $linkAction = new \LinkAction(
            'settings',
            new \AjaxModal(
                $router->url(
                    $request,
                    null,
                    null,
                    'manage',
                    null,
                    [
                        'verb'     => 'settings',
                        'plugin'   => $this->getName(),
                        'category' => 'generic'
                    ]
                ),
                $this->getDisplayName()
            ),
            __('manager.plugins.settings'),
            null
        );

        array_unshift($actions, $linkAction);
        return $actions;
    }

    /**
     * Kelola request pengaturan plugin (form settings & simpan data)
     */
    public function manage($args, $request) {
        switch ($request->getUserVar('verb')) {
            case 'settings':
                $context = $request->getContext();
                $contextId = $context ? $context->getId() : 0;

                require_once(__DIR__ . '/ArtilikSettingsForm.inc.php');
                $form = new \ArtilikSettingsForm($this, $contextId);

                import('lib.pkp.classes.core.JSONMessage');

                if ($request->getUserVar('save')) {
                    $form->readInputData();
                    if ($form->validate()) {
                        $form->execute();

                        // Buat notifikasi sukses di antarmuka OJS
                        $notificationManager = new \NotificationManager();
                        $user = $request->getUser();
                        if ($user) {
                            $notificationManager->createTrivialNotification(
                                $user->getId(),
                                NOTIFICATION_TYPE_SUCCESS,
                                ['contents' => __('common.changesSaved')]
                            );
                        }

                        return new \JSONMessage(true);
                    }
                }

                $form->initData();
                return new \JSONMessage(true, $form->fetch($request));
        }

        return parent::manage($args, $request);
    }

    /**
     * Inisialisasi instance konektor SDK
     */
    public function getConnector($contextId = null) {
        $apiKey = $this->getSetting($contextId, 'apiKey');
        $apiEndpoint = $this->getSetting($contextId, 'apiEndpoint') ?: 'https://tilik.arsetontong.top/api/v1';
        if (empty($apiKey)) {
            return null;
        }
        return new \ARTilikOJSConnector($apiKey, $apiEndpoint);
    }

    /**
     * Callback saat editor merekam keputusan: kirim naskah ke AR Tilik secara asinkron dengan callback_url (tanpa polling).
     */
    public function callbackRecordDecision($hookName, $args) {
        $submission = $args[0] ?? null;
        if (! $submission || ! is_object($submission)) {
            return false;
        }

        $contextId = (int) $submission->getData('contextId');
        if (! $this->getSetting($contextId, 'autoCheckOnDecision')) {
            return false;
        }

        $connector = $this->getConnector($contextId);
        if (! $connector) {
            return false;
        }

        try {
            $submissionId = (int) $submission->getId();
            $filePath = $this->resolvePrimarySubmissionFilePath($submissionId);
            if (! $filePath || ! file_exists($filePath)) {
                return false;
            }

            $request = \Application::get()->getRequest();
            $dispatcher = $request ? $request->getDispatcher() : null;
            $callbackUrl = $dispatcher ? $dispatcher->url($request, ROUTE_PAGE, null, 'artilik', 'webhook') : null;
            if (! $callbackUrl || stripos($callbackUrl, 'https://') !== 0) {
                $callbackUrl = null;
            }

            $title = method_exists($submission, 'getLocalizedTitle')
                ? (string) $submission->getLocalizedTitle()
                : ('OJS Submission #' . $submissionId);
            $author = method_exists($submission, 'getAuthorString')
                ? (string) $submission->getAuthorString(true)
                : '';

            $res = $connector->submitManuscript(
                $filePath,
                $title ?: ('OJS Submission #' . $submissionId),
                $author,
                (string) $submissionId,
                $callbackUrl
            );

            if (! empty($res['data']['report_id'])) {
                $pendingRecord = [
                    'status'      => 'queued',
                    'document_id' => (int) ($res['data']['document_id'] ?? 0),
                    'report_id'   => (int) $res['data']['report_id'],
                    'queued_at'   => date('Y-m-d H:i:s'),
                ];
                $this->updateSetting($contextId, 'webhookResult_' . $submissionId, json_encode($pendingRecord), 'string');
            }
        } catch (\Throwable $e) {
            error_log('[AR Tilik OJS] Gagal menjadwalkan pemeriksaan otomatis: ' . $e->getMessage());
        }

        return false;
    }

    /**
     * Menampilkan ringkasan hasil webhook AR Tilik yang sudah tersimpan pada halaman Workflow editor OJS 3.x.
     */
    public function callbackTemplateDisplay($hookName, $args) {
        $templateMgr = $args[0] ?? null;
        $template    = $args[1] ?? '';

        if (! $templateMgr || strpos((string) $template, 'workflow/workflow.tpl') === false) {
            return false;
        }

        $submission = $templateMgr->getTemplateVars('submission');
        if (! $submission || ! is_object($submission)) {
            return false;
        }

        $contextId = (int) $submission->getData('contextId');
        $rawResult = $this->getSetting($contextId, 'webhookResult_' . (int) $submission->getId());
        if (! $rawResult) {
            return false;
        }

        $data = json_decode((string) $rawResult, true);
        if (! is_array($data) || ! isset($args[4]) || ! is_string($args[4])) {
            return false;
        }

        $simVal = isset($data['overall_similarity']) ? number_format((float) $data['overall_similarity'], 2) . '%' : 'Dalam Antrean';
        $decision = strtoupper((string) ($data['predicted_decision'] ?? ($data['status'] ?? 'QUEUED')));
        $verCode = (string) ($data['verification_code'] ?? '-');
        $webUrl = (string) ($data['web_report_url'] ?? '');
        $pdfUrl = (string) ($data['pdf_export_url'] ?? '');
        $verifiedBadge = ! empty($data['verified_hmac'])
            ? '<span style="background:#059669;color:#fff;padding:2px 8px;border-radius:999px;font-size:11px;font-weight:600;">Webhook HMAC Terverifikasi</span>'
            : '<span style="background:#0284c7;color:#fff;padding:2px 8px;border-radius:999px;font-size:11px;font-weight:600;">Menunggu Callback</span>';

        $panelHtml = '<div id="artilik-webhook-panel" style="margin:12px 16px;padding:14px 16px;border:1px solid #cbd5e1;border-left:4px solid #0284c7;background:#f8fafc;border-radius:6px;font-family:sans-serif;font-size:13px;">'
            . '<div style="display:flex;justify-content:space-between;align-items:center;gap:8px;flex-wrap:wrap;">'
            . '<strong>AR Tilik Similarity Check</strong>' . $verifiedBadge
            . '</div>'
            . '<div style="margin-top:8px;color:#334155;">'
            . 'Skor Kemiripan: <strong>' . htmlspecialchars($simVal, ENT_QUOTES, 'UTF-8') . '</strong> &bull; '
            . 'Putusan: <strong>' . htmlspecialchars($decision, ENT_QUOTES, 'UTF-8') . '</strong> &bull; '
            . 'Kode: <code>' . htmlspecialchars($verCode, ENT_QUOTES, 'UTF-8') . '</code>'
            . ($webUrl ? ' &bull; <a href="' . htmlspecialchars($webUrl, ENT_QUOTES, 'UTF-8') . '" target="_blank" style="color:#0284c7;">Lihat Laporan</a>' : '')
            . ($pdfUrl ? ' &bull; <a href="' . htmlspecialchars($pdfUrl, ENT_QUOTES, 'UTF-8') . '" target="_blank" style="color:#0284c7;">Unduh PDF</a>' : '')
            . '</div></div>';

        if (strpos($args[4], '</body>') !== false) {
            $args[4] = str_replace('</body>', $panelHtml . '</body>', $args[4]);
        }

        return false;
    }

    /**
     * Mengambil path berkas naskah utama di penyimpanan OJS 3.3.
     */
    protected function resolvePrimarySubmissionFilePath(int $submissionId): ?string {
        if (! class_exists('\Services') || ! class_exists('\Config')) {
            return null;
        }
        $files = \Services::get('submissionFile')->getMany([
            'submissionIds' => [$submissionId],
        ]);
        $filesDir = rtrim((string) \Config::getVar('files', 'files_dir'), '/');
        foreach ($files as $submissionFile) {
            $relPath = ltrim((string) $submissionFile->getData('path'), '/');
            if ($relPath !== '' && file_exists($filesDir . '/' . $relPath)) {
                return $filesDir . '/' . $relPath;
            }
        }
        return null;
    }
}
