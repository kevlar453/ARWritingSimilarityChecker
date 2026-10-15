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
        return 'Modul integrasi otomatis PKP OJS 3.x dengan mesin orisinalitas Dual-Lens AR Tilik dan Audit Forensik Cerdas AI.';
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
