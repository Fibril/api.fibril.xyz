<?php

namespace Services\Auth;

class JwtGuard
{
    public static function isAuthorized(array $requiredParameters = [])
    {
        if (isset($_COOKIE['__Secure-Fibril-Token']))
            return self::validate($_COOKIE['__Secure-Fibril-Token']);

        return false;
    }

    private static function validate($token, array $payload = []) // TODO: Check if all required params are present in the JWT.
    {
        $token = explode('.', $token);

        if ($token !== false && count($token) == 3)
        {
            $base64UrlHeader = $token[0];
            $base64UrlPayload = $token[1];
            $signature = self::base64url_decode($token[2]);

            if (hash_hmac('sha256', $base64UrlHeader . '.' . $base64UrlPayload, JWT_SECRET) === $signature)
            {
                $header = json_decode(base64_decode($base64UrlHeader));
                $currentTimestamp = round(microtime(true) * 1000); // Current timestamp in milliseconds.

                // Check whether the expiry timestamp is ahead of the current timestamp.
                if ($header->exp > $currentTimestamp)
                {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Generates a JWT for use with Fibril's API.
     * @param  string $identifier The user identifier.
     * @return string The JSON web token.
     */
    protected static function issueToken($identifier)
    {
        $currentTimestamp = round(microtime(true) * 1000); // Current timestamp in milliseconds.
        $expiryTimestamp = $currentTimestamp + 120000; // 2 minutes = 120000 milliseconds // 60 minutes = 3600000 milliseconds.

        // Create token header as a JSON string https://tools.ietf.org/html/rfc7519#section-4
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256', 'exp' => $expiryTimestamp]);

        // Create token payload as a JSON string.
        $payload = json_encode(['user_id' => $identifier]);

        // Encode Header to Base64Url string.
        $base64UrlHeader = self::base64url_encode($header);

        // Encode Payload to Base64Url string.
        $base64UrlPayload = self::base64url_encode($payload);

        // Create Signature Hash.
        $signature = hash_hmac('sha256', $base64UrlHeader . '.' . $base64UrlPayload, JWT_SECRET);

        // Encode Signature to Base64Url string.
        $base64UrlSignature = self::base64url_encode($signature);

        // Create JWT.
        $jwt = $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;

        return $jwt;
    }

    private static function base64url_encode($data)
    {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
    }

    private static function base64url_decode($data)
    {
        return str_replace(['-', '_', ''], ['+', '/', '='], base64_decode($data));
    }
}
