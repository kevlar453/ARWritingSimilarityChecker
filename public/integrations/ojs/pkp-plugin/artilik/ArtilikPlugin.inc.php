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

    /**
     * Daftarkan plugin ke OJS
     */
    public function register($category, $path, $mainContextId = null) {
        $success = parent::register($category, $path, $mainContextId);
        if ($success && $this->getEnabled($mainContextId)) {
            // Pasang hook saat editor merekam keputusan telaah atau saat file naskah diunggah
            \HookRegistry::register('EditorAction::recordDecision', [$this, 'callbackRecordDecision']);
            \HookRegistry::register('TemplateManager::display', [$this, 'callbackTemplateDisplay']);
        }
        return $success;
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
     * Callback saat editor merekam keputusan (contoh wiring)
     */
    public function callbackRecordDecision($hookName, $args) {
        $submission = $args[0] ?? null;
        $decision   = $args[1] ?? null;

        // Logika integrasi: ambil file naskah artikel utama dan jalankan pengecekan melalui SDK
        // $connector = $this->getConnector($submission ? $submission->getContextId() : null);
        // if ($connector) { ... }

        return false;
    }

    /**
     * Callback display template OJS
     */
    public function callbackTemplateDisplay($hookName, $args) {
        return false;
    }
}
