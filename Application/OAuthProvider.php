<?php

class OAuthProvider
{
    // TODO: Provide token information in every request header for accessing restricted endpoints in the API. If the token fetched from the request header information is valid, let the user access the specified end point, and respond with JSON.

    public function __construct()
    {
    }

    private function authorize($fibrilToken)
    {
    }

    /**
     * Exchanges a Discord token for a Fibril's API token.
     * @param  string $discordToken
     * @return bool   Whether the Discord user was exchanged a token for Fibril's API.
     */
    public function signInWithDiscordToken($discordToken)
    {

        // TODO: Throw an Exception if the credentials are invalid?
        // If any exceptions are thrown when validating the credentials, a response with the status 403 (Forbidden) will be returned.

        $userInfo = $this->getDiscordInfo($discordToken);

        if ($userInfo === false)
        {
            return false;
        }

        // $userMapper = new UserMapper();
        // $userMapper->create();

        $token = self::issueToken($userInfo['id']);

        return $token;

        // $auth = OAuthProvider->getInstance()->
        // $auth->signInWithDiscordToken($accessToken)
        //     .addOnCompleteListener(new OnCompleteListener()
        //     {
        //         public function onComplete($task)
        //         {
        //             if ($task->isSuccessful())
        //             {
        //                 $user = $task->getResult()->getUser();
        //                 $email = $user->getEmail();
        //
        //                 // ...
        //             }
        //             $token = bin2hex(random_bytes(16));
        //             $token = $auth->generateToken(); // ( int $size [, bool $strong = FALSE ] ) : string
        //         } // echo '<span style="color: #D53E43;">$request</span>🡒<span style="color: #25A2AE;">getQuery</span>() ⇒ ' . var_export($request->getQuery(), true) . '<br>';
        //     });
    }

    /**
     * Generates a JWT for use with Fibril's API.
     * @param  string $identifier The user identifier.
     * @return string The JSON web token.
     */
    public static function issueToken($identifier)
    {
        // TODO: Create a token by using the Discord user information.
        // TODO: Then return that information in the response header, so that the token can be stored in the browser's local storage.

        $currentTimestamp = round(microtime(true) * 1000); // Current timestamp in milliseconds.
        $expiryTimestamp = $currentTimestamp + 120000; // 2 minutes = 120000  // 60 minutes equals 3600000 milliseconds.

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

        setcookie('__Secure-Fibril-Token', $jwt, 0, '', '.fibril.xyz', true, true);

        return $jwt;
    }

    public static function isLoggedIn()
    {
        if (isset($_COOKIE['__Secure-Fibril-Token']))
            return self::validateToken($_COOKIE['__Secure-Fibril-Token']);

        return false;
    }

    private static function validateToken($jwt)
    {
        $jwt = explode('.', $jwt);
        $base64UrlHeader = $jwt[0];
        $base64UrlPayload = $jwt[1];
        $signature = $jwt[2];

        if (self::base64url_decode($signature) === hash_hmac('sha256', $base64UrlHeader . '.' . $base64UrlPayload, JWT_SECRET))
        {
            $header = json_decode(base64_decode($base64UrlHeader));
            $currentTimestamp = round(microtime(true) * 1000); // Current timestamp in milliseconds.

            // Check whether the expiry timestamp is ahead of the current timestamp.
            if ($header->exp > $currentTimestamp)
            {
                return true;
            }
        }

        return false;
    }

    private static function base64url_encode($data)
    {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
    }

    private static function base64url_decode($data)
    {
        return str_replace(['-', '_', ''], ['+', '/', '='], base64_decode($data));
    }

    /**
     * Whether the Discord token is valid and belongs to a Discord user.
     * @param  string $discordToken
     * @return bool
     */
    private function isDiscordUser($discordToken)
    {
        $result = $this->http([
            CURLOPT_URL => "https://discord.com/api/users/@me",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded', 'Authorization: Bearer ' . $discordToken]
        ]);

        $user = json_decode($result);

        return isset($user->id);
    }

    /**
     * Gets Discord info such as username, id, avatar, joined guilds, etc.
     * @param  string      $discordToken
     * @return string|bool Returns the Discord information on success, false on failure.
     */
    public function getDiscordInfo($discordToken)
    {
        if ($this->isDiscordUser($discordToken))
        {
            $result = $this->http([
                CURLOPT_URL => "https://discord.com/api/users/@me",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded', 'Authorization: Bearer ' . $discordToken]
            ]);

            $user = json_decode($result);

            $result = $this->http([
                CURLOPT_URL => "https://discord.com/api/users/@me/guilds",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded', 'Authorization: Bearer ' . $discordToken]
            ]);

            $guilds = json_decode($result);

            return [
                'id' => $user->id,
                'username' => $user->username,
                'discriminator' => $user->discriminator,
                'avatar_url' => 'https://cdn.discordapp.com/avatars/' . $user->id . '/' . $user->avatar . '.png?size=128',
                'guilds' => $guilds,
            ];
        }

        return false;
    }

    private function http($options)
    {
        // Initiate a new cURL session and get its cURL handler.
        $curlHandler = curl_init();

        curl_setopt_array($curlHandler, $options);

        $result = curl_exec($curlHandler);

        // Close the cURL session.
        curl_close($curlHandler);

        return $result;
    }

    // if (!isset($_SESSION['user']) && isset($_SESSION['access_token']))
    // {
    //     $result = http(array(CURLOPT_URL => "https://discord.com/api/users/@me",
    //         CURLOPT_RETURNTRANSFER => true,
    //         CURLOPT_HTTPHEADER => array('Content-Type: application/x-www-form-urlencoded', 'Authorization: Bearer ' . $_SESSION['access_token'])));

    //     $_SESSION['user'] = $result;
    // }

    // if (!isset($_SESSION['guilds']) && isset($_SESSION['access_token']))
    // {
    //     $guilds = http(array(CURLOPT_URL => "https://discord.com/api/users/@me/guilds",
    //         CURLOPT_RETURNTRANSFER => true,
    //         CURLOPT_HTTPHEADER => array('Content-Type: application/x-www-form-urlencoded', 'Authorization: Bearer ' . $_SESSION['access_token'])));

    //     $_SESSION['guilds'] = $guilds;
    // }

    // if (isset($_SESSION['guilds']))
    // {
    //     echo 'Guilds fetched from session as: ' . $_SESSION['guilds'] . "<br><br><br><br><br><br>";

    //     $json_guilds = json_decode($_SESSION['guilds']);

    //     echo "<b>Guilds in which you're admin: </b><br>";
    //     foreach($json_guilds as $json_guild)
    //     {
    //         // Check whether we have the admin permission.
    //         if ($json_guild->permissions === 2147483647)
    //         {
    //             echo $json_guild->name . "<br>";
    //             echo "<a href='/fibril/dashboard/" . $json_guild->id . "'><img src='https://cdn.discord.com/icons/" . $json_guild->id . "/" . $json_guild->icon . ".png' style='border-radius: 50%;'></a><br>";
    //         }
    //     }

    //     echo "<br><br>";
    // }
}
