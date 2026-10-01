<?php

namespace App\Utils;

use Firebase\JWT\JWT as FirebaseJWT;
use Firebase\JWT\Key;

class JWT
{
    private static string $secret = 'your-secret-key-change-in-production-12345';
    private static string $algorithm = 'HS256';

    public static function encode(array $payload): string
    {
        $issuedAt = time();
        $expire = $issuedAt + 86400; // 24 hours
        
        $payload['iat'] = $issuedAt;
        $payload['exp'] = $expire;
        
        return FirebaseJWT::encode($payload, self::$secret, self::$algorithm);
    }

    public static function decode(string $token): array
    {
        $decoded = FirebaseJWT::decode($token, new Key(self::$secret, self::$algorithm));
        return (array) $decoded;
    }
}