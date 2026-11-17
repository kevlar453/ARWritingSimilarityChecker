<?php

namespace App\Libraries;

/**
 * Enkripsi & Dekripsi Rahasia HMAC Webhook (AES-256-GCM Lintas Bahasa PHP <-> Python).
 *
 * Format envelope: "v1:" . base64_encode(iv_12B . tag_16B . ciphertext)
 * Kunci diturunkan dengan domain separation HMAC-SHA256 dari encryption.key / WEBHOOK_ENCRYPTION_KEY.
 */
class WebhookCrypto
{
    private const VERSION_PREFIX = 'v1:';
    private const KDF_CONTEXT    = 'artilik-webhook-secret-v1';

    /**
     * Menghasilkan rahasia HMAC baru yang acak secara kriptografis.
     */
    public static function generateSecret(): string
    {
        return 'whsec_' . bin2hex(random_bytes(24));
    }

    /**
     * Mengembalikan sidik jari aman SHA-256 (12 karakter hex) tanpa membocorkan rahasia.
     */
    public static function fingerprint(string $secret): string
    {
        if ($secret === '') {
            return '';
        }

        return substr(hash('sha256', $secret), 0, 12);
    }

    /**
     * Mengenkripsi webhook_secret menggunakan AES-256-GCM sebelum disimpan ke database.
     */
    public static function encryptSecret(string $plaintext): string
    {
        $key = self::deriveEncryptionKey();
        $iv = random_bytes(12);
        $tag = '';

        $ciphertext = openssl_encrypt(
            $plaintext,
            'aes-256-gcm',
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            '',
            16
        );

        if ($ciphertext === false || strlen($tag) !== 16) {
            throw new \RuntimeException('Gagal mengenkripsi webhook_secret dengan AES-256-GCM.');
        }

        return self::VERSION_PREFIX . base64_encode($iv . $tag . $ciphertext);
    }

    /**
     * Mendekripsi envelope "v1:..." menjadi webhook_secret plaintext.
     */
    public static function decryptSecret(string $encoded): string
    {
        if (strpos($encoded, self::VERSION_PREFIX) !== 0) {
            throw new \RuntimeException('Format ciphertext webhook_secret tidak dikenali.');
        }

        $raw = base64_decode(substr($encoded, strlen(self::VERSION_PREFIX)), true);
        if ($raw === false || strlen($raw) < 29) {
            throw new \RuntimeException('Ciphertext webhook_secret korup atau terlalu pendek.');
        }

        $iv = substr($raw, 0, 12);
        $tag = substr($raw, 12, 16);
        $ciphertext = substr($raw, 28);

        $key = self::deriveEncryptionKey();
        $plaintext = openssl_decrypt(
            $ciphertext,
            'aes-256-gcm',
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            ''
        );

        if ($plaintext === false) {
            throw new \RuntimeException('Autentikasi tag GCM gagal saat mendekripsi webhook_secret.');
        }

        return $plaintext;
    }

    /**
     * Menurunkan sub-kunci AES-256 (32 byte) dengan HMAC-SHA256.
     */
    private static function deriveEncryptionKey(): string
    {
        $masterBytes = self::resolveMasterKeyBytes();
        return hash_hmac('sha256', self::KDF_CONTEXT, $masterBytes, true);
    }

    /**
     * Membaca dan menormalisasi master key dari environment atau Config\Encryption.
     */
    private static function resolveMasterKeyBytes(): string
    {
        $rawConfig = null;
        foreach (['WEBHOOK_ENCRYPTION_KEY', 'encryption.key'] as $envName) {
            $val = $_ENV[$envName] ?? $_SERVER[$envName] ?? getenv($envName);
            if ($val !== false && $val !== null && trim((string) $val) !== '') {
                $rawConfig = trim((string) $val);
                break;
            }
        }

        if (($rawConfig === null || $rawConfig === '') && function_exists('env')) {
            $rawConfig = trim((string) (env('WEBHOOK_ENCRYPTION_KEY') ?: env('encryption.key') ?: ''));
        }

        if (($rawConfig === null || $rawConfig === '') && class_exists(\Config\Encryption::class)) {
            $rawConfig = trim((string) (config('Encryption')->key ?? ''));
        }

        if ($rawConfig === null || $rawConfig === '') {
            throw new \RuntimeException('encryption.key atau WEBHOOK_ENCRYPTION_KEY belum dikonfigurasi pada environment.');
        }

        // Bersihkan kutip pembungkus bila terbawa dari pembacaan .env
        if (
            (str_starts_with($rawConfig, "'") && str_ends_with($rawConfig, "'")) ||
            (str_starts_with($rawConfig, '"') && str_ends_with($rawConfig, '"'))
        ) {
            $rawConfig = substr($rawConfig, 1, -1);
        }

        if (str_starts_with($rawConfig, 'hex2bin:')) {
            $hex = trim(substr($rawConfig, 8));
            if ($hex === '' || strlen($hex) % 2 !== 0 || ! ctype_xdigit($hex)) {
                throw new \RuntimeException('Format encryption.key hex2bin tidak valid.');
            }
            $bytes = hex2bin($hex);
            if ($bytes === false || strlen($bytes) < 32) {
                throw new \RuntimeException('Kunci enkripsi hex2bin wajib minimal 32 byte (64 karakter hex).');
            }
            return strlen($bytes) === 32 ? $bytes : substr($bytes, 0, 32);
        }

        if (str_starts_with($rawConfig, 'base64:')) {
            $bytes = base64_decode(trim(substr($rawConfig, 7)), true);
            if ($bytes === false || strlen($bytes) < 32) {
                throw new \RuntimeException('Kunci enkripsi base64 wajib minimal 32 byte.');
            }
            return strlen($bytes) === 32 ? $bytes : substr($bytes, 0, 32);
        }

        if (strlen($rawConfig) < 32) {
            throw new \RuntimeException('Kunci enkripsi wajib minimal 32 byte.');
        }

        return strlen($rawConfig) === 32 ? $rawConfig : hash('sha256', $rawConfig, true);
    }
}
