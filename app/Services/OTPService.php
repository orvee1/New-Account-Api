<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

class OTPService
{
    private const TTL_SECONDS = 600;
    private const MAX_ATTEMPTS = 5;

    public static function generateOTP(string $phone): string
    {
        $otp = (string) random_int(100000, 999999);

        Cache::put(self::cacheKey($phone), [
            'hash' => Hash::make($otp),
            'attempts' => 0,
            'generated_at' => now()->toIso8601String(),
        ], now()->addSeconds(self::TTL_SECONDS));

        return $otp;
    }

    public static function validateOTP(string $phone, string $otp): bool
    {
        $payload = Cache::get(self::cacheKey($phone));

        if (! is_array($payload)) {
            return false;
        }

        $attempts = (int) ($payload['attempts'] ?? 0);
        if ($attempts >= self::MAX_ATTEMPTS) {
            Cache::forget(self::cacheKey($phone));
            return false;
        }

        $isValid = Hash::check($otp, (string) ($payload['hash'] ?? ''));

        if ($isValid) {
            return true;
        }

        $payload['attempts'] = $attempts + 1;
        Cache::put(self::cacheKey($phone), $payload, now()->addSeconds(self::TTL_SECONDS));

        return false;
    }

    public static function deleteOTP(string $phone): void
    {
        Cache::forget(self::cacheKey($phone));
    }

    private static function cacheKey(string $phone): string
    {
        return 'otp:password-reset:'.preg_replace('/\D+/', '', $phone);
    }
}
