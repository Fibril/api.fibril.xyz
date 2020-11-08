<?php

namespace Services\Auth;

class JwtGuard
{
    public static function isAuthorized(array $payload = [])
    {
        if (isset($_COOKIE['__Secure-Fibril-Token']))
            return self::validate($_COOKIE['__Secure-Fibril-Token'], $payload);

        return false;
    }

    private static function validate($token, array $partialPayload = [])
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
                $currentTimestamp = time(); // round(microtime(true) * 1000); // Current timestamp in milliseconds.

                // Check whether the expiry timestamp is ahead of the current timestamp.
                if ($header->exp > $currentTimestamp)
                {
                    if (self::array_exists_in_array($partialPayload, json_decode(self::base64url_decode($base64UrlPayload), true)))
                    {
                        return true;
                    }

                    // Compared payload didn't match.
                }

                // Token expired.
            }
        }

        return false;
    }

    private static function array_exists_in_array(array $needle, array $haystack): bool
    {
        foreach ($needle as $key => $value)
        {
            // If the other array doesn't have this key, fail.
            if (!array_key_exists($key, $haystack))
            {
                return false;
            }

            // Make sure the values are the same type, otherwise fail.
            if (gettype($value) !== gettype($haystack[$key]))
            {
                return false;
            }

            // For scalar types, test them directly.
            if (is_scalar($value))
            {
                if ($value !== $haystack[$key])
                {
                    return false;
                }

                continue;
            }

            // For array, recurse into this same function.
            if (is_array($value))
            {
                if (!self::array_exists_in_array($value, $haystack[$key]))
                {
                    return false;
                }

                continue;
            }

            // For anything else, fail or write some other logic.
            throw new \Exception('Unsupported type');
        }

        // The loop passed without return false, so it is a subset.
        return true;
    }

    /**
     * Generates a JWT for use with Fibril's API.
     * @param  string $identifier The user identifier.
     * @return string The JSON web token.
     */
    protected static function issueToken($identifier)
    {
        // $currentTimestamp = round(microtime(true) * 1000); // Current timestamp in milliseconds.
        $currentTimestamp = time();
        $expiryTimestamp = $currentTimestamp + 604800; // 2 minutes = 120 seconds. // 60 minutes = 3600 seconds. // 7 days = 604800 seconds.

        // Create token header as a JSON string https://tools.ietf.org/html/rfc7519#section-4
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256', 'exp' => $expiryTimestamp]);

        // Create token payload as a JSON string.
        // $payload = json_encode(['user_id' => $identifier, 'guild_id' => '678840415494995988']);
        $payload = json_encode(['user_id' => $identifier, 'guild_ids' => [
            '678840415494995988' => ['owner' => true],
            '726114818347499600' => ['owner' => false],
            '672933392291069952' => ['owner' => false]
        ]]);

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
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64url_decode($data)
    {
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
    }
}
