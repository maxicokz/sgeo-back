<?php

namespace App\Services;

class SecretsManager
{
    /** Required environment secrets that must be present at boot. */
    private const REQUIRED = [
        'DB_PASSWORD',
        'OPENROUTER_API_KEY',
        'APP_KEY',
    ];

    /**
     * Retrieve a secret from the environment.
     *
     * @param string $key     Environment variable name
     * @param mixed  $default Value returned when the key is absent
     * @return mixed
     */
    public function get(string $key, $default = null): mixed
    {
        $value = $_ENV[$key] ?? getenv($key);
        return ($value !== false && $value !== null && $value !== '') ? $value : $default;
    }

    /**
     * Validate that all required secrets are present.
     *
     * @throws \RuntimeException if any required secret is missing
     */
    public function validate(): void
    {
        $missing = [];
        foreach (self::REQUIRED as $key) {
            if ($this->get($key) === null) {
                $missing[] = $key;
            }
        }

        if (!empty($missing)) {
            throw new \RuntimeException(
                'Missing required secrets: ' . implode(', ', $missing)
            );
        }
    }

    /**
     * Mask a secret value, showing only first 4 and last 4 characters.
     *
     * Example: "supersecretkey123" → "supe...y123"
     *
     * @param string $value The value to mask
     * @return string
     */
    public function mask(string $value): string
    {
        $len = mb_strlen($value);

        if ($len <= 8) {
            // Short value — show nothing meaningful
            return str_repeat('*', $len);
        }

        return mb_substr($value, 0, 4) . '...' . mb_substr($value, -4);
    }
}
