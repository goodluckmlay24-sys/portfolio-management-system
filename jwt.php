<?php
// config/jwt.php
class JWT {
    private static $secret = 'your-secret-key-change-in-production';
    private static $algorithm = 'HS256';
    
    public static function encode($payload) {
        $header = json_encode(['typ' => 'JWT', 'alg' => self::$algorithm]);
        $base64Header = base64_encode($header);
        
        $payload['exp'] = time() + 3600; // 1 hour expiry
        $base64Payload = base64_encode(json_encode($payload));
        
        $signature = hash_hmac('sha256', "$base64Header.$base64Payload", self::$secret, true);
        $base64Signature = base64_encode($signature);
        
        return "$base64Header.$base64Payload.$base64Signature";
    }
    
    public static function decode($token) {
        $parts = explode('.', $token);
        if (count($parts) !== 3) return null;
        
        list($base64Header, $base64Payload, $base64Signature) = $parts;
        $signature = base64_decode($base64Signature);
        
        $expectedSignature = hash_hmac('sha256', "$base64Header.$base64Payload", self::$secret, true);
        
        if (!hash_equals($signature, $expectedSignature)) {
            return null;
        }
        
        $payload = json_decode(base64_decode($base64Payload), true);
        
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return null; // Token expired
        }
        
        return $payload;
    }
}
?>