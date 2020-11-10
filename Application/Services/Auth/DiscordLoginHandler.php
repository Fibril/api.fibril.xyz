<?php

namespace Services\Auth;

use Config;

class DiscordLoginHandler extends JwtGuard
{
    private $discordToken;
    private $discordTokenExpiresIn;
    private $discordRefreshToken;

    public function __invoke($request)
    {
        session_start();

        $params = $request->getQuery();

        if (isset($_SESSION['state']) && $_SESSION['state'] === $params['state'])
        {
            $result = json_decode($this->http([
                CURLOPT_URL => 'https://discord.com/api/oauth2/token',
                CURLOPT_POST => 1,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
                CURLOPT_POSTFIELDS => http_build_query([
                    'client_id' => '568041297349443595',
                    'client_secret' => Config::get('discord', 'client_secret'),
                    'grant_type' => 'authorization_code',
                    'code' => $params['code'] ?? null,
                    'redirect_uri' => 'https://api.fibril.xyz/login',
                    'scope' => 'identify%20guilds'
                ])
            ]));

            if (isset($result->error))
                die('<body style="background-color: #121921;"></body><script>if (window.opener && window.opener !== window) { window.close(); } else { window.location.href = "https://fibril.xyz/" }</script>');

            $this->discordToken = $result->access_token;
            $this->discordTokenExpiresIn = $result->expires_in;
            $this->discordRefreshToken = $result->refresh_token;

            $user = json_decode($this->http([
                CURLOPT_URL => "https://discord.com/api/users/@me",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded', 'Authorization: Bearer ' . $this->discordToken]
            ]));

            if (isset($user))
            {
                setcookie('__Secure-Fibril-Token', JwtGuard::issueToken(
                    $user->id,
                    $user->username . '#' . $user->discriminator,
                    'https://cdn.discordapp.com/avatars/' . $user->id . '/' . $user->avatar . (substr($user->avatar, 0, 2) === 'a_' ? '.gif' : '.png')
                ), time() + 604800, '/', 'fibril.xyz', true, false);

                // Destroys the current session.
                setcookie(session_name(), '', time() - 3600);
                session_destroy();
                session_write_close();

                die('<body style="background-color: #121921;"></body><script>document.domain = "fibril.xyz"; if (window.opener && window.opener !== window) { window.opener.login(); window.close(); } else { window.location.href = "https://fibril.xyz/dashboard"; }</script>');
            }
        }

        $_SESSION['state'] = bin2hex(random_bytes(16));
        header('Location: https://discord.com/api/oauth2/authorize?client_id=568041297349443595&redirect_uri=https%3A%2F%2Fapi.fibril.xyz%2Flogin&response_type=code&scope=identify%20guilds&state=' . $_SESSION['state']);
        die('<body style="background-color: #121921;"></body>');
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
}
