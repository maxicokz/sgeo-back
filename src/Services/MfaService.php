<?php

namespace App\Services;

use App\Database\Connection;

/**
 * MFA (Multi-Factor Authentication) service — TOTP RFC 6238 implementation.
 * No external dependencies required.
 */
class MfaService
{
    private const BASE32_CHARS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    private const TOTP_PERIOD  = 30;
    private const TOTP_DIGITS  = 6;

    // -------------------------------------------------------------------------
    // Secret generation
    // -------------------------------------------------------------------------

    /**
     * Generate a random 16-character Base32 secret.
     */
    public function generateSecret(): string
    {
        $secret = '';
        $chars  = self::BASE32_CHARS;
        $len    = strlen($chars);

        for ($i = 0; $i < 16; $i++) {
            $secret .= $chars[random_int(0, $len - 1)];
        }

        return $secret;
    }

    // -------------------------------------------------------------------------
    // QR-code URL
    // -------------------------------------------------------------------------

    /**
     * Build an otpauth:// URL suitable for Google Authenticator and compatible apps.
     */
    public function generateQrCodeUrl(string $secret, string $username, string $issuer = 'SGEO'): string
    {
        $label = rawurlencode($issuer . ':' . $username);

        return sprintf(
            'otpauth://totp/%s?secret=%s&issuer=%s&algorithm=SHA1&digits=%d&period=%d',
            $label,
            $secret,
            rawurlencode($issuer),
            self::TOTP_DIGITS,
            self::TOTP_PERIOD
        );
    }

    // -------------------------------------------------------------------------
    // Code verification
    // -------------------------------------------------------------------------

    /**
     * Verify a 6-digit TOTP code.
     *
     * @param string $secret The Base32-encoded secret
     * @param string $code   The 6-digit code from the authenticator app
     * @param int    $window Time-step tolerance (±window periods)
     */
    public function verifyCode(string $secret, string $code, int $window = 1): bool
    {
        $code = trim($code);
        if (!preg_match('/^\d{6}$/', $code)) {
            return false;
        }

        $timestamp = time();

        for ($i = -$window; $i <= $window; $i++) {
            $ts = $timestamp + ($i * self::TOTP_PERIOD);
            if (hash_equals($this->generateTotp($secret, $ts), $code)) {
                return true;
            }
        }

        return false;
    }

    // -------------------------------------------------------------------------
    // TOTP internals
    // -------------------------------------------------------------------------

    /**
     * Decode a Base32 string to binary.
     */
    private function base32Decode(string $secret): string
    {
        $secret  = strtoupper($secret);
        $chars   = self::BASE32_CHARS;
        $buffer  = 0;
        $bitsLeft = 0;
        $result  = '';

        for ($i = 0; $i < strlen($secret); $i++) {
            $char = $secret[$i];
            $pos  = strpos($chars, $char);

            if ($pos === false) {
                continue; // skip padding/invalid chars
            }

            $buffer   = ($buffer << 5) | $pos;
            $bitsLeft += 5;

            if ($bitsLeft >= 8) {
                $bitsLeft -= 8;
                $result   .= chr(($buffer >> $bitsLeft) & 0xFF);
            }
        }

        return $result;
    }

    /**
     * Generate a TOTP code for the given secret and Unix timestamp.
     */
    private function generateTotp(string $secret, int $timestamp): string
    {
        $counter = (int) floor($timestamp / self::TOTP_PERIOD);

        // Pack counter as 64-bit big-endian
        $counterBytes = pack('N*', 0) . pack('N*', $counter);

        $key  = $this->base32Decode($secret);
        $hash = hash_hmac('sha1', $counterBytes, $key, true);

        // Dynamic truncation
        $offset = ord($hash[strlen($hash) - 1]) & 0x0F;
        $code   = (
            ((ord($hash[$offset])     & 0x7F) << 24) |
            ((ord($hash[$offset + 1]) & 0xFF) << 16) |
            ((ord($hash[$offset + 2]) & 0xFF) << 8)  |
            ( ord($hash[$offset + 3]) & 0xFF)
        ) % (10 ** self::TOTP_DIGITS);

        return str_pad((string) $code, self::TOTP_DIGITS, '0', STR_PAD_LEFT);
    }

    // -------------------------------------------------------------------------
    // Backup codes
    // -------------------------------------------------------------------------

    /**
     * Generate an array of random 8-character backup codes.
     */
    public function generateBackupCodes(int $count = 8): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = strtoupper(bin2hex(random_bytes(4))); // 8 hex chars
        }
        return $codes;
    }

    // -------------------------------------------------------------------------
    // MFA policy
    // -------------------------------------------------------------------------

    /**
     * Return true if MFA is required for the given user record.
     */
    public function isMfaRequired(array $user): bool
    {
        return in_array($user['role'] ?? '', ['admin', 'manager'], true);
    }

    // -------------------------------------------------------------------------
    // Database helpers
    // -------------------------------------------------------------------------

    /**
     * Enable MFA for a user — store secret and backup codes.
     */
    public function enableMfa(int $userId, string $secret, array $backupCodes): void
    {
        $existing = Connection::queryOne(
            'SELECT id FROM mfa_secrets WHERE user_id = ?',
            [$userId]
        );

        $data = [
            'secret'       => $secret,
            'enabled'      => true,
            'backup_codes' => json_encode($backupCodes),
        ];

        if ($existing) {
            Connection::update('mfa_secrets', $data, 'user_id = ?', [$userId]);
        } else {
            $data['user_id']    = $userId;
            $data['created_at'] = date('Y-m-d H:i:s');
            Connection::insert('mfa_secrets', $data);
        }
    }

    /**
     * Disable MFA for a user.
     */
    public function disableMfa(int $userId): void
    {
        Connection::update(
            'mfa_secrets',
            ['enabled' => false],
            'user_id = ?',
            [$userId]
        );
    }
}
