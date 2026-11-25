<?php

namespace App\Libraries;

/**
 * Klien Google OAuth 2.0 + OpenID Connect (Authorization Code + PKCE S256).
 * Menggunakan native cURL dan OpenSSL agar bebas dari ketergantungan SDK eksternal.
 */
class GoogleOAuthClient
{
    private const AUTH_ENDPOINT  = 'https://accounts.google.com/o/oauth2/v2/auth';
    private const TOKEN_ENDPOINT = 'https://oauth2.googleapis.com/token';
    private const JWKS_ENDPOINT  = 'https://www.googleapis.com/oauth2/v3/certs';
    private const JWKS_CACHE_KEY = 'google_oauth_jwks_certs';
    private const JWKS_CACHE_TTL = 3600;

    private string $clientId;
    private string $clientSecret;
    private string $redirectUri;

    /** @var callable|null */
    private $httpTransport;

    /** @var callable|null */
    private $clock;

    /**
     * Inisialisasi klien OAuth dengan konfigurasi opsional atau dari environment (.env).
     * Parameter transport/clock dapat disuntikkan untuk keperluan pengujian unit luring.
     */
    public function __construct(
        ?string $clientId = null,
        ?string $clientSecret = null,
        ?string $redirectUri = null,
        ?callable $httpTransport = null,
        ?callable $clock = null
    ) {
        $this->clientId     = trim((string) ($clientId ?? $this->readEnv('GOOGLE_CLIENT_ID')));
        $this->clientSecret = trim((string) ($clientSecret ?? $this->readEnv('GOOGLE_CLIENT_SECRET')));
        $envRedirect        = trim((string) ($redirectUri ?? $this->readEnv('GOOGLE_REDIRECT_URI')));
        $this->redirectUri  = $envRedirect !== '' ? $envRedirect : base_url('auth/google/callback');
        $this->httpTransport = $httpTransport;
        $this->clock        = $clock;
    }

    public function isConfigured(): bool
    {
        return $this->clientId !== '' && $this->clientSecret !== '';
    }

    public function getClientId(): string
    {
        return $this->clientId;
    }

    public function getRedirectUri(): string
    {
        return $this->redirectUri;
    }

    public static function generateState(): string
    {
        return bin2hex(random_bytes(24));
    }

    public static function generateCodeVerifier(): string
    {
        return self::base64UrlEncode(random_bytes(32));
    }

    public static function computeCodeChallenge(string $verifier): string
    {
        return self::base64UrlEncode(hash('sha256', $verifier, true));
    }

    /**
     * Menyusun URL otorisasi Google OAuth 2.0 dengan PKCE S256 dan parameter OIDC.
     */
    public function buildAuthorizationUrl(string $state, string $codeChallenge, ?string $loginHint = null): string
    {
        $params = [
            'client_id'             => $this->clientId,
            'redirect_uri'          => $this->redirectUri,
            'response_type'         => 'code',
            'scope'                 => 'openid email profile',
            'state'                 => $state,
            'code_challenge'        => $codeChallenge,
            'code_challenge_method' => 'S256',
            'access_type'           => 'online',
            'prompt'                => 'select_account',
        ];

        if ($loginHint !== null && filter_var($loginHint, FILTER_VALIDATE_EMAIL)) {
            $params['login_hint'] = $loginHint;
        }

        return self::AUTH_ENDPOINT . '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * Menukar authorization code + PKCE code_verifier menjadi access_token dan id_token.
     *
     * @throws \RuntimeException jika pertukaran token gagal atau respons tidak valid.
     */
    public function exchangeCode(string $code, string $codeVerifier): array
    {
        $payload = [
            'code'          => $code,
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri'  => $this->redirectUri,
            'grant_type'    => 'authorization_code',
            'code_verifier' => $codeVerifier,
        ];

        $res = $this->requestHttp('POST', self::TOKEN_ENDPOINT, $payload);
        $data = json_decode($res['body'], true);

        if ($res['status'] < 200 || $res['status'] >= 300 || ! is_array($data)) {
            $errDesc = is_array($data) ? (string) ($data['error_description'] ?? $data['error'] ?? 'HTTP ' . $res['status']) : ('HTTP ' . $res['status']);
            throw new \RuntimeException('Gagal menukar kode otorisasi Google: ' . $errDesc);
        }

        if (empty($data['id_token'])) {
            throw new \RuntimeException('Respons Google OAuth tidak menyertakan id_token.');
        }

        return $data;
    }

    /**
     * Memverifikasi tanda tangan RS256 dan klaim OIDC pada id_token dari Google.
     *
     * @return array{google_id:string, email:string, name:string, avatar_url:?string, email_verified:bool}
     * @throws \RuntimeException jika token tidak valid, kedaluwarsa, atau email belum terverifikasi.
     */
    public function verifyIdToken(string $idToken, ?array $jwksOverride = null): array
    {
        $parts = explode('.', $idToken);
        if (count($parts) !== 3) {
            throw new \RuntimeException('Format JWT id_token Google tidak valid.');
        }

        [$headerB64, $payloadB64, $sigB64] = $parts;
        $headerJson  = self::base64UrlDecode($headerB64);
        $payloadJson = self::base64UrlDecode($payloadB64);
        $signature   = self::base64UrlDecode($sigB64);

        $header = json_decode($headerJson, true);
        $claims = json_decode($payloadJson, true);

        if (! is_array($header) || ! is_array($claims)) {
            throw new \RuntimeException('Payload JWT id_token Google tidak dapat diurai.');
        }

        if (($header['alg'] ?? '') !== 'RS256') {
            throw new \RuntimeException('Algoritma JWT id_token harus RS256.');
        }

        $kid = (string) ($header['kid'] ?? '');
        if ($kid === '') {
            throw new \RuntimeException('Header JWT tidak memiliki key identifier (kid).');
        }

        $jwk = $this->findJwkByKid($kid, $jwksOverride);
        if ($jwk === null) {
            throw new \RuntimeException('Kunci publik Google JWKS untuk kid tersebut tidak ditemukan.');
        }

        $pem = self::jwkToPem($jwk);
        $verified = openssl_verify($headerB64 . '.' . $payloadB64, $signature, $pem, OPENSSL_ALGO_SHA256);
        if ($verified !== 1) {
            throw new \RuntimeException('Verifikasi tanda tangan kriptografis id_token Google gagal.');
        }

        return $this->validateClaims($claims);
    }

    /**
     * Memvalidasi klaim standar OpenID Connect dan mengembalikan profil pengguna ternormalisasi.
     *
     * @throws \RuntimeException jika ada klaim yang tidak memenuhi syarat keamanan.
     */
    private function validateClaims(array $claims): array
    {
        $now = $this->now();

        $iss = (string) ($claims['iss'] ?? '');
        if (! in_array($iss, ['https://accounts.google.com', 'accounts.google.com'], true)) {
            throw new \RuntimeException('Issuer (iss) id_token Google tidak sah.');
        }

        $aud = (string) ($claims['aud'] ?? '');
        if ($this->clientId === '' || ! hash_equals($this->clientId, $aud)) {
            throw new \RuntimeException('Audience (aud) id_token tidak cocok dengan client_id aplikasi.');
        }

        $exp = $claims['exp'] ?? null;
        if (! is_numeric($exp) || (int) $exp < ($now - 60)) {
            throw new \RuntimeException('Token identitas Google telah kedaluwarsa.');
        }

        if (isset($claims['iat']) && is_numeric($claims['iat']) && (int) $claims['iat'] > ($now + 300)) {
            throw new \RuntimeException('Timestamp iat pada id_token Google tidak wajar.');
        }

        $sub = trim((string) ($claims['sub'] ?? ''));
        if ($sub === '' || strlen($sub) > 64) {
            throw new \RuntimeException('Subject (sub) akun Google tidak valid.');
        }

        $emailVerified = $claims['email_verified'] ?? false;
        if ($emailVerified !== true) {
            throw new \RuntimeException('Alamat email pada akun Google belum terverifikasi oleh Google.');
        }

        $email = strtolower(trim((string) ($claims['email'] ?? '')));
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \RuntimeException('Alamat email dari akun Google tidak valid.');
        }

        $name = trim((string) ($claims['name'] ?? ''));
        if ($name === '') {
            $name = strstr($email, '@', true) ?: 'Pengguna Google';
        }
        $name = mb_substr($name, 0, 150);

        $picture = trim((string) ($claims['picture'] ?? ''));
        $avatarUrl = null;
        if ($picture !== '' && strlen($picture) <= 500 && stripos($picture, 'https://') === 0 && filter_var($picture, FILTER_VALIDATE_URL)) {
            $avatarUrl = $picture;
        }

        return [
            'google_id'      => $sub,
            'email'          => $email,
            'name'           => $name,
            'avatar_url'     => $avatarUrl,
            'email_verified' => true,
        ];
    }

    /**
     * Mencari JWK yang cocok berdasarkan kid, menyegarkan cache JWKS sekali bila tidak ditemukan.
     */
    private function findJwkByKid(string $kid, ?array $jwksOverride = null): ?array
    {
        $jwks = $jwksOverride ?? $this->getJwks(false);
        foreach ($jwks['keys'] ?? [] as $key) {
            if (is_array($key) && ($key['kid'] ?? '') === $kid && ($key['kty'] ?? '') === 'RSA') {
                return $key;
            }
        }

        if ($jwksOverride !== null) {
            return null;
        }

        // Segarkan cache JWKS Google bila terjadi rotasi kunci baru
        $freshJwks = $this->getJwks(true);
        foreach ($freshJwks['keys'] ?? [] as $key) {
            if (is_array($key) && ($key['kid'] ?? '') === $kid && ($key['kty'] ?? '') === 'RSA') {
                return $key;
            }
        }

        return null;
    }

    private function getJwks(bool $forceRefresh): array
    {
        if (! $forceRefresh && function_exists('cache')) {
            try {
                $cached = cache()->get(self::JWKS_CACHE_KEY);
                if (is_array($cached) && ! empty($cached['keys'])) {
                    return $cached;
                }
            } catch (\Throwable $e) {
                // Abaikan kegagalan cache dan lanjut ambil dari endpoint JWKS
            }
        }

        $res = $this->requestHttp('GET', self::JWKS_ENDPOINT);
        $jwks = json_decode($res['body'], true);
        if ($res['status'] < 200 || $res['status'] >= 300 || ! is_array($jwks) || empty($jwks['keys'])) {
            throw new \RuntimeException('Gagal mengunduh daftar kunci publik Google JWKS.');
        }

        if (function_exists('cache')) {
            try {
                cache()->save(self::JWKS_CACHE_KEY, $jwks, self::JWKS_CACHE_TTL);
            } catch (\Throwable $e) {
                // Non-fatal
            }
        }

        return $jwks;
    }

    /**
     * Mengonversi JSON Web Key (RSA modulus n + exponent e) menjadi format PEM X.509 SubjectPublicKeyInfo.
     */
    public static function jwkToPem(array $jwk): string
    {
        if (empty($jwk['n']) || empty($jwk['e'])) {
            throw new \RuntimeException('Data JWK RSA tidak lengkap.');
        }

        $modulus  = self::base64UrlDecode((string) $jwk['n']);
        $exponent = self::base64UrlDecode((string) $jwk['e']);

        $rsaPublicKey = self::encodeDerSequence(
            self::encodeDerInteger($modulus) . self::encodeDerInteger($exponent)
        );

        // OID rsaEncryption (1.2.840.113549.1.1.1) + NULL
        $algorithmIdentifier = self::encodeDerSequence(
            "\x06\x09\x2a\x86\x48\x86\xf7\x0d\x01\x01\x01\x05\x00"
        );

        $subjectPublicKeyInfo = self::encodeDerSequence(
            $algorithmIdentifier . "\x03" . self::encodeDerLength(strlen($rsaPublicKey) + 1) . "\x00" . $rsaPublicKey
        );

        return "-----BEGIN PUBLIC KEY-----\n"
            . chunk_split(base64_encode($subjectPublicKeyInfo), 64, "\n")
            . "-----END PUBLIC KEY-----\n";
    }

    private static function encodeDerInteger(string $bytes): string
    {
        if ($bytes === '' || (ord($bytes[0]) & 0x80) !== 0) {
            $bytes = "\x00" . $bytes;
        }
        return "\x02" . self::encodeDerLength(strlen($bytes)) . $bytes;
    }

    private static function encodeDerSequence(string $content): string
    {
        return "\x30" . self::encodeDerLength(strlen($content)) . $content;
    }

    private static function encodeDerLength(int $length): string
    {
        if ($length < 0x80) {
            return chr($length);
        }
        $encoded = ltrim(pack('N', $length), "\x00");
        return chr(0x80 | strlen($encoded)) . $encoded;
    }

    public static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    public static function base64UrlDecode(string $data): string
    {
        $remainder = strlen($data) % 4;
        if ($remainder > 0) {
            $data .= str_repeat('=', 4 - $remainder);
        }
        $decoded = base64_decode(strtr($data, '-_', '+/'), true);
        return $decoded === false ? '' : $decoded;
    }

    /**
     * @return array{status:int, body:string}
     */
    private function requestHttp(string $method, string $url, ?array $formBody = null): array
    {
        if ($this->httpTransport !== null) {
            return ($this->httpTransport)($method, $url, $formBody);
        }

        if (! function_exists('curl_init')) {
            throw new \RuntimeException('Ekstensi PHP cURL dibutuhkan untuk autentikasi Google.');
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 12,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_PROTOCOLS      => CURLPROTO_HTTPS,
        ]);

        if (strtoupper($method) === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($formBody ?? [], '', '&', PHP_QUERY_RFC3986));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
        }

        $body   = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err    = curl_error($ch);
        curl_close($ch);

        if ($body === false) {
            throw new \RuntimeException('Koneksi ke server Google gagal: ' . $err);
        }

        return ['status' => $status, 'body' => (string) $body];
    }

    private function now(): int
    {
        return $this->clock !== null ? (int) ($this->clock)() : time();
    }

    private function readEnv(string $key): string
    {
        if (function_exists('env')) {
            $val = env($key, '');
            if (is_string($val) && $val !== '') {
                return $val;
            }
        }
        return (string) ($_ENV[$key] ?? getenv($key) ?: '');
    }
}
