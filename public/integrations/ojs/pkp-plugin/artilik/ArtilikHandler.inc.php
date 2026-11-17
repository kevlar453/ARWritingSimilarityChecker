<?php
/**
 * @file plugins/generic/artilik/ArtilikHandler.inc.php
 *
 * Copyright (c) 2026 AR Tilik Development Team
 * Distributed under the GNU GPL v3.
 *
 * @class ArtilikHandler
 * @ingroup plugins_generic_artilik
 *
 * @brief Endpoint PKP OJS 3.x penerima webhook callback dari AR Tilik dengan verifikasi HMAC-SHA256.
 */

if (function_exists('import') && ! class_exists('Handler', false)) {
    import('classes.handler.Handler');
}

require_once(__DIR__ . '/ARTilikOJSConnector.php');

if (! class_exists('Handler', false)) {
    // Fallback kelas dasar agar berkas ini dapat diuji lint/unit di luar runtime penuh PKP OJS
    class Handler {}
}

if (! class_exists('ArtilikHandler', false)) {
class ArtilikHandler extends Handler {

    /**
     * Endpoint webhook menggunakan tanda tangan kriptografis HMAC-SHA256, bukan sesi login OJS.
     */
    public function authorize($request, &$args, $roleAssignments) {
        return true;
    }

    /**
     * Memproses HTTP POST callback pada URL: /index.php/<context>/artilik/webhook
     */
    public function webhook($args, $request) {
        $method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper((string) $_SERVER['REQUEST_METHOD']) : 'GET';
        if ($method !== 'POST') {
            return $this->sendJsonResponse(405, [
                'status'  => 'error',
                'message' => 'Method tidak diizinkan. Gunakan HTTP POST.',
            ]);
        }

        $rawBody = $this->readRawBody();
        $headers = $this->collectRequestHeaders();

        $plugin = $this->resolvePluginInstance();
        if (! $plugin) {
            return $this->sendJsonResponse(500, [
                'status'  => 'error',
                'message' => 'Plugin AR Tilik tidak aktif pada OJS.',
            ]);
        }

        $context = $request ? $request->getContext() : null;
        $contextId = $context ? (int) $context->getId() : 0;

        $decodedPreview = json_decode($rawBody, true);
        $submissionId = is_array($decodedPreview)
            ? self::extractNumericSubmissionId($decodedPreview['external_id'] ?? null)
            : null;
        $submission = $submissionId ? $this->findSubmission($submissionId) : null;

        if ($submission && $contextId === 0) {
            $contextId = (int) $submission->getData('contextId');
        }

        $webhookSecret = trim((string) $plugin->getSetting($contextId, 'webhookSecret'));
        if ($webhookSecret === '' && $submission) {
            $subContextId = (int) $submission->getData('contextId');
            if ($subContextId > 0) {
                $webhookSecret = trim((string) $plugin->getSetting($subContextId, 'webhookSecret'));
                $contextId = $subContextId;
            }
        }
        if ($webhookSecret === '' && $contextId !== 0) {
            $webhookSecret = trim((string) $plugin->getSetting(0, 'webhookSecret'));
        }

        if ($webhookSecret === '') {
            return $this->sendJsonResponse(401, [
                'status'  => 'error',
                'message' => 'Webhook HMAC Secret belum dikonfigurasi pada pengaturan plugin OJS.',
            ]);
        }

        try {
            $payload = \ARTilikOJSConnector::parseWebhookRequest($rawBody, $headers, $webhookSecret);
        } catch (\Throwable $e) {
            return $this->sendJsonResponse(401, [
                'status'  => 'error',
                'message' => $e->getMessage(),
            ]);
        }

        $persist = $this->persistWebhookResult($plugin, $contextId, $submission, $payload);

        return $this->sendJsonResponse(200, [
            'status'                 => 'ok',
            'verified'               => true,
            'stored'                 => $persist['stored'],
            'event_logged'           => $persist['event_logged'],
            'editorial_note_created' => $persist['editorial_note_created'],
            'warnings'               => $persist['warnings'],
            'submission_id'          => $submission ? (int) $submission->getId() : $submissionId,
            'external_id'            => $payload['external_id'] ?? null,
            'report_id'              => $payload['report_id'] ?? null,
        ]);
    }

    /**
     * Mengekstrak ID submisi numerik OJS dari external_id (misal "1240" atau "submission_1240").
     */
    public static function extractNumericSubmissionId($externalId): ?int {
        if ($externalId === null) {
            return null;
        }
        $val = trim((string) $externalId);
        if ($val === '') {
            return null;
        }
        if (ctype_digit($val)) {
            $num = (int) $val;
            return $num > 0 ? $num : null;
        }
        if (preg_match('/(\d+)$/', $val, $matches)) {
            $num = (int) $matches[1];
            return $num > 0 ? $num : null;
        }
        return null;
    }

    /**
     * Menyimpan hasil webhook ke plugin_settings serta mencatat aktivitas/catatan editorial pada submisi OJS.
     *
     * @return array{stored: bool, event_logged: bool, editorial_note_created: bool, warnings: array<int, string>}
     */
    protected function persistWebhookResult($plugin, int $contextId, $submission, array $payload): array {
        $record = [
            'verified_hmac'          => true,
            'event'                  => $payload['event'] ?? 'similarity_check.completed',
            'delivery_id'            => $payload['delivery_id'] ?? '',
            'external_id'            => $payload['external_id'] ?? null,
            'document_id'            => isset($payload['document_id']) ? (int) $payload['document_id'] : null,
            'report_id'              => isset($payload['report_id']) ? (int) $payload['report_id'] : null,
            'status'                 => $payload['status'] ?? 'completed',
            'overall_similarity'     => isset($payload['overall_similarity']) ? (float) $payload['overall_similarity'] : 0.0,
            'substantive_similarity' => isset($payload['substantive_similarity']) ? (float) $payload['substantive_similarity'] : null,
            'exact_similarity'       => isset($payload['exact_similarity']) ? (float) $payload['exact_similarity'] : 0.0,
            'cosine_similarity'      => isset($payload['cosine_similarity']) ? (float) $payload['cosine_similarity'] : 0.0,
            'predicted_decision'     => $payload['predicted_decision'] ?? 'completed',
            'verification_code'      => $payload['verification_code'] ?? '',
            'web_report_url'         => $payload['web_report_url'] ?? '',
            'verification_url'       => $payload['verification_url'] ?? '',
            'pdf_export_url'         => $payload['pdf_export_url'] ?? '',
            'received_at'            => date('Y-m-d H:i:s'),
        ];

        $settingKey = $submission
            ? 'webhookResult_' . (int) $submission->getId()
            : 'webhookResult_ext_' . substr(hash('sha256', (string) ($payload['external_id'] ?? ($payload['report_id'] ?? '0'))), 0, 16);

        $targetContextId = $submission ? (int) $submission->getData('contextId') : $contextId;
        $plugin->updateSetting($targetContextId, $settingKey, json_encode($record, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 'string');
        $plugin->updateSetting($targetContextId, 'webhookLastDelivery', json_encode($record, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 'string');

        $eventLogged = false;
        $noteCreated = false;
        $warnings = [];

        if ($submission && $this->hasDaoRegistry()) {
            $subId = (int) $submission->getId();
            $simText = number_format((float) $record['overall_similarity'], 2) . '%';
            $decision = strtoupper((string) $record['predicted_decision']);
            $verCode = (string) $record['verification_code'];
            $webUrl = (string) $record['web_report_url'];
            $pdfUrl = (string) $record['pdf_export_url'];

            // 1. Catat ke Riwayat Aktivitas Submisi OJS (SubmissionEventLogDAO sudah mengembalikan objek Entry yang tepat)
            try {
                $eventLogDao = $this->getDao('SubmissionEventLogDAO');
                if ($eventLogDao) {
                    $entry = $eventLogDao->newDataObject();
                    $entry->setAssocType(defined('ASSOC_TYPE_SUBMISSION') ? ASSOC_TYPE_SUBMISSION : 0x0100009);
                    $entry->setAssocId($subId);
                    $entry->setUserId(0);
                    $entry->setDateLogged(class_exists('\Core') ? \Core::getCurrentDate() : date('Y-m-d H:i:s'));
                    $entry->setEventType(0x10000999);
                    $entry->setMessage(
                        "AR Tilik Webhook (HMAC Terverifikasi): Kemiripan {$simText}, Putusan: {$decision}, Kode: {$verCode}, Laporan: {$webUrl}"
                    );
                    $entry->setIsTranslated(1);
                    $insertedId = $eventLogDao->insertObject($entry);
                    $eventLogged = ($insertedId !== false && $insertedId !== null);
                } else {
                    $warnings[] = 'SubmissionEventLogDAO tidak tersedia pada runtime OJS.';
                }
            } catch (\Throwable $e) {
                $msg = 'Gagal menulis SubmissionEventLogDAO: ' . $e->getMessage();
                $warnings[] = $msg;
                error_log('[AR Tilik OJS] ' . $msg);
            }

            // 2. Tambahkan catatan diskusi editorial pada workflow submisi bila peserta tersedia
            try {
                $queryDao = $this->getDao('QueryDAO');
                $noteDao = $this->getDao('NoteDAO');
                $stageAssignmentDao = $this->getDao('StageAssignmentDAO');
                if ($queryDao && $noteDao && $stageAssignmentDao) {
                    $participantIds = [];
                    $assignments = $stageAssignmentDao->getBySubmissionAndStageId($subId, (int) $submission->getData('stageId'));
                    if ($assignments) {
                        while ($assignment = $assignments->next()) {
                            $uid = (int) $assignment->getUserId();
                            if ($uid > 0 && ! in_array($uid, $participantIds, true)) {
                                $participantIds[] = $uid;
                            }
                        }
                    }

                    if (! empty($participantIds)) {
                        $query = $queryDao->newDataObject();
                        $query->setAssocType(defined('ASSOC_TYPE_SUBMISSION') ? ASSOC_TYPE_SUBMISSION : 0x0100009);
                        $query->setAssocId($subId);
                        $query->setStageId((int) $submission->getData('stageId'));
                        $query->setSequence(defined('REALLY_BIG_NUMBER') ? REALLY_BIG_NUMBER : 10000);
                        $query->setIsClosed(false);
                        $queryId = $queryDao->insertObject($query);
                        $queryDao->resequence(defined('ASSOC_TYPE_SUBMISSION') ? ASSOC_TYPE_SUBMISSION : 0x0100009, $subId);

                        foreach ($participantIds as $pid) {
                            $queryDao->insertParticipant($queryId, $pid);
                        }

                        $note = $noteDao->newDataObject();
                        $note->setAssocType(defined('ASSOC_TYPE_QUERY') ? ASSOC_TYPE_QUERY : 0x010000a);
                        $note->setAssocId($queryId);
                        $note->setUserId($participantIds[0]);
                        $note->setTitle("Hasil Cek Kemiripan AR Tilik ({$simText})");
                        $note->setContents(
                            '<p>Laporan pemeriksaan orisinalitas <strong>AR Tilik</strong> telah diterima otomatis melalui webhook terverifikasi HMAC-SHA256:</p>'
                            . '<ul>'
                            . '<li><strong>Tingkat Kemiripan Total:</strong> ' . htmlspecialchars($simText, ENT_QUOTES, 'UTF-8') . '</li>'
                            . '<li><strong>Rekomendasi Sistem:</strong> ' . htmlspecialchars($decision, ENT_QUOTES, 'UTF-8') . '</li>'
                            . '<li><strong>Kode Verifikasi:</strong> <code>' . htmlspecialchars($verCode, ENT_QUOTES, 'UTF-8') . '</code></li>'
                            . ($webUrl ? '<li><strong>Laporan Interaktif:</strong> <a href="' . htmlspecialchars($webUrl, ENT_QUOTES, 'UTF-8') . '" target="_blank">Buka Laporan AR Tilik</a></li>' : '')
                            . ($pdfUrl ? '<li><strong>Unduh Sertifikat/PDF:</strong> <a href="' . htmlspecialchars($pdfUrl, ENT_QUOTES, 'UTF-8') . '" target="_blank">Unduh Laporan PDF</a></li>' : '')
                            . '</ul>'
                        );
                        $noteId = $noteDao->insertObject($note);
                        $noteCreated = ($noteId !== false && $noteId !== null);
                    }
                }
            } catch (\Throwable $e) {
                $msg = 'Gagal membuat catatan Query/Note editorial: ' . $e->getMessage();
                $warnings[] = $msg;
                error_log('[AR Tilik OJS] ' . $msg);
            }
        }

        return [
            'stored'                 => true,
            'event_logged'           => $eventLogged,
            'editorial_note_created' => $noteCreated,
            'warnings'               => $warnings,
        ];
    }

    protected function hasDaoRegistry(): bool {
        return class_exists('\DAORegistry');
    }

    protected function getDao(string $name) {
        return class_exists('\DAORegistry') ? \DAORegistry::getDAO($name) : null;
    }

    protected function resolvePluginInstance() {
        if (class_exists('\ArtilikPlugin') && method_exists('\ArtilikPlugin', 'getActiveInstance')) {
            $inst = \ArtilikPlugin::getActiveInstance();
            if ($inst) {
                return $inst;
            }
        }
        if (class_exists('\PluginRegistry')) {
            return \PluginRegistry::getPlugin('generic', 'artilikplugin')
                ?: \PluginRegistry::getPlugin('generic', 'artilik');
        }
        return null;
    }

    protected function findSubmission(int $submissionId) {
        if (class_exists('\Services')) {
            try {
                $service = \Services::get('submission');
                if ($service) {
                    return $service->get($submissionId);
                }
            } catch (\Throwable $e) {
                // Fallback ke SubmissionDAO di bawah
            }
        }
        if (class_exists('\DAORegistry')) {
            $dao = \DAORegistry::getDAO('SubmissionDAO');
            if ($dao) {
                return $dao->getById($submissionId);
            }
        }
        return null;
    }

    protected function readRawBody(): string {
        $raw = @file_get_contents('php://input');
        return is_string($raw) ? $raw : '';
    }

    protected function collectRequestHeaders(): array {
        $headers = function_exists('getallheaders') ? (array) @getallheaders() : [];
        foreach ($_SERVER as $key => $val) {
            if (is_string($key) && strpos($key, 'HTTP_') === 0 && (is_string($val) || is_numeric($val))) {
                $headerName = str_replace('_', '-', substr($key, 5));
                if (! isset($headers[$headerName])) {
                    $headers[$headerName] = (string) $val;
                }
                $headers[$key] = (string) $val;
            }
        }
        return $headers;
    }

    protected function sendJsonResponse(int $statusCode, array $payload) {
        if (! headers_sent()) {
            http_response_code($statusCode);
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return true;
    }
}
}
