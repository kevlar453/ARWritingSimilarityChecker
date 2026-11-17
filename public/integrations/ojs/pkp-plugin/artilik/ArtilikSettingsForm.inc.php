<?php
/**
 * @file plugins/generic/artilik/ArtilikSettingsForm.inc.php
 *
 * Copyright (c) 2026 AR Tilik Development Team
 * Distributed under the GNU GPL v3.
 *
 * @class ArtilikSettingsForm
 * @ingroup plugins_generic_artilik
 *
 * @brief Form pengaturan konfigurasi API Key AR Tilik untuk OJS 3.x
 */

import('lib.pkp.classes.form.Form');

class ArtilikSettingsForm extends Form {
    /** @var ArtilikPlugin */
    public $_plugin;

    /** @var int */
    public $_contextId;

    /**
     * Constructor
     * @param $plugin ArtilikPlugin
     * @param $contextId int
     */
    public function __construct($plugin, $contextId) {
        $this->_plugin = $plugin;
        $this->_contextId = (int) $contextId;

        parent::__construct($plugin->getTemplateResource('settings.tpl'));

        $this->addCheck(new FormValidator($this, 'apiKey', 'required', 'Harap masukkan API Key AR Tilik.'));
        $this->addCheck(new FormValidatorPost($this));
        $this->addCheck(new FormValidatorCSRF($this));
    }

    /**
     * Inisialisasi data form dari database OJS
     */
    public function initData() {
        $this->setData('apiKey', $this->_plugin->getSetting($this->_contextId, 'apiKey'));
        $this->setData('webhookSecret', $this->_plugin->getSetting($this->_contextId, 'webhookSecret'));
        $this->setData('apiEndpoint', $this->_plugin->getSetting($this->_contextId, 'apiEndpoint') ?: 'https://tilik.arsetontong.top/api/v1');
        $this->setData('autoCheckOnDecision', (bool) ($this->_plugin->getSetting($this->_contextId, 'autoCheckOnDecision') ?? true));
    }

    /**
     * Membaca input POST dari user
     */
    public function readInputData() {
        $this->readUserVars(['apiKey', 'webhookSecret', 'apiEndpoint', 'autoCheckOnDecision']);
    }

    /**
     * Render form template
     */
    public function fetch($request, $template = null, $display = false) {
        $templateMgr = TemplateManager::getManager($request);
        $dispatcher = $request->getDispatcher();
        $webhookEndpointUrl = '';
        if ($dispatcher) {
            $webhookEndpointUrl = $dispatcher->url($request, ROUTE_PAGE, null, 'artilik', 'webhook');
        }

        $templateMgr->assign([
            'pluginName'          => $this->_plugin->getName(),
            'apiKey'              => $this->getData('apiKey'),
            'webhookSecret'       => $this->getData('webhookSecret'),
            'apiEndpoint'         => $this->getData('apiEndpoint'),
            'autoCheckOnDecision' => $this->getData('autoCheckOnDecision'),
            'webhookEndpointUrl'  => $webhookEndpointUrl,
        ]);

        return parent::fetch($request, $template, $display);
    }

    /**
     * Menyimpan data pengaturan ke tabel plugin_settings OJS
     */
    public function execute(...$functionArgs) {
        $this->_plugin->updateSetting($this->_contextId, 'apiKey', trim((string) $this->getData('apiKey')));
        $this->_plugin->updateSetting($this->_contextId, 'webhookSecret', trim((string) $this->getData('webhookSecret')));
        $this->_plugin->updateSetting($this->_contextId, 'apiEndpoint', trim((string) $this->getData('apiEndpoint')) ?: 'https://tilik.arsetontong.top/api/v1');
        $this->_plugin->updateSetting($this->_contextId, 'autoCheckOnDecision', (bool) $this->getData('autoCheckOnDecision'));

        return parent::execute(...$functionArgs);
    }
}
