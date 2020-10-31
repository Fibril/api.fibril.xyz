<?php

include __DIR__ . '/Includes/Autoloader.php';

define('FIBRIL_EPOCH', 1555452000000); // Fibril's birthday, 17th of April 2019.
define('JWT_SECRET', Config::get('api', 'secret'));

date_default_timezone_set('UTC');
session_start();

// header("X-Robots-Tag: noindex, nofollow", true);

// TODO: (SoC) Discord API stuff should be done in a different place. Same goes for the cURL stuff.
function http($options)
{
    // Initiate a new cURL session and get its cURL handler.
    $curlHandler = curl_init();

    curl_setopt_array($curlHandler, $options);

    $result = curl_exec($curlHandler);

    // Close the cURL session.
    curl_close($curlHandler);

    return $result;
}

$routeCollector = new RouteCollector();

$routeCollector->get('/redirect', function ($request)
{
    $_SESSION['state'] = bin2hex(random_bytes(16));

    header('Location: https://discord.com/api/oauth2/authorize?client_id=568041297349443595&redirect_uri=https%3A%2F%2Fapi.fibril.xyz%2Fdiscord-callback&response_type=code&scope=identify%20guilds&state=' . $_SESSION['state']);

    die();
});

$routeCollector->get('/discord-callback', function ($request)
{
    $params = $request->getQuery();

    echo $_SESSION['state'] . "<br>";
    echo $params['state'] . "<br>";

    if (isset($_SESSION['state']) !== true || $_SESSION['state'] !== $params['state'])
        die('<br>Bad state.');

    $client_id = '568041297349443595';
    $client_secret = Config::get('discord', 'client_secret');

    $redirect_uri = 'https://api.fibril.xyz/discord-callback';

    $result = http([
        CURLOPT_URL => 'https://discord.com/api/oauth2/token',
        CURLOPT_POST => 1,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_POSTFIELDS => http_build_query([
            'client_id' => $client_id,
            'client_secret' => $client_secret,
            'grant_type' => 'authorization_code',
            'code' => $params['code'],
            'redirect_uri' => $redirect_uri,
            'scope' => 'identify%20guilds'
        ])
    ]);

    $result = json_decode($result);

    if ($result->error)
    {
        die('<script>if (window.opener && window.opener !== window) { window.close(); }</script>');
    }

    $_SESSION['expires_in'] = $result->expires_in;
    $_SESSION['access_token'] = $result->access_token;
    $_SESSION['refresh_token'] = $result->refresh_token;

    echo "<script>document.domain = 'fibril.xyz'; if (window.opener && window.opener !== window) { window.opener.redirect(); window.close(); } else { window.location.href = 'https://fibril.xyz/dashboard'; }</script>";
    die();
});

// A JWT gets send to the server.
// header('Authorization: Bearer ' . $jwt);
// The server MUST be prioritising an Authorization header over a sent cookie.
// If the token isn't set in the __Secure-Fibril-Token, or missing from a Authorization header, or simply isn't valid, the WWW-Authenticate header is sent along with a 401 Unauthorized response.
// header('WWW-Authenticate: Bearer realm="/users/@me"');

$routeCollector->get('/favicon.ico', function ($request)
{
    // header('Referrer-Policy: strict-origin-when-cross-origin');
    http_response_code(204);
    die();
});

$routeCollector->get('/authorize', function ($request)
{
});

$routeCollector->post('/token', function ($request)
{
    $grantType = $request->getData()['grant_type'];

    $auth = new OAuthProvider();
    $jwt = $auth->issueToken('12345');

    header('Content-Type: application/json');

    die(json_encode([
        'access_token' => $jwt,
        'token_type' => 'JWT',
        'expires_in' => 120,
        'refresh_token' => 'tGzv3JOkF0XG5Qx2TlKWIA'
    ]));
});

$routeCollector->post('/token/revoke', function ($request)
{
});

$routeCollector->get('/test', function ($request)
{
    $auth = new OAuthProvider();
    // $token = $auth->signInWithDiscordToken($_SESSION['access_token']);

    $jwt = $auth->issueToken("12345");

    echo '$auth->isLoggedIn() ⇒ ';
    var_dump($auth->isLoggedIn());
    echo '<br>';
    echo '<pre>$_COOKIE ⇒ ';
    var_dump($_COOKIE);
    echo '</pre>';
    die();

    echo '<pre>$_SESSION[\'access_token\'] ⇒ ' . $_SESSION['access_token'] . '</pre>';

    die();
});

// /authorize?response_type=code&client_id=s6BhdRkqt3&state=xyz&redirect_uri=https%3A%2F%2Fclient%2Eexample%2Ecom%2Fcb
// /authorize?response_type=token&client_id=s6BhdRkqt3&state=xyz&redirect_uri=https%3A%2F%2Fclient%2Eexample%2Ecom%2Fcb

// $routeCollector->get('/oauth2/authorize', Handler\AuthorizationHandler::class);
// $routeCollector->post('/oauth2/token', Handler\TokenSupplyHandler::class);
// $routeCollector->post('/oauth2/token/revoke', Handler\TokenRevocationHandler::class);
/*
https://tools.ietf.org/html/rfc7009

POST /revoke HTTP/1.1
Host: api.fibril.xyz
Content-Type: application/x-www-form-urlencoded
Authorization: Basic czZCaGRSa3F0MzpnWDFmQmF0M2JW

token=45ghiukldjahdnhzdauz&token_type_hint=refresh_token

GET https://github.com/login/oauth/authorize
POST https://github.com/login/oauth/access_token

Authorization: token OAUTH-TOKEN
GET https://api.github.com/user

 */

use Incidents\Handler;

$routeCollector->get('/guilds/{guild_id}/incidents', Handler\IncidentsReadHandler::class);
$routeCollector->post('/guilds/{guild_id}/incidents', Handler\IncidentsCreateHandler::class);

$routeCollector->get('/guilds/{guild_id}/incidents/{incident_id}', Handler\IncidentReadHandler::class);
$routeCollector->delete('/guilds/{guild_id}/incidents/{incident_id}', Handler\IncidentDeleteHandler::class);
// $routeCollector->put('/guilds/{guild_id}/incidents/{incident_id}', Handler\IncidentUpdateHandler::class); // TODO: { "client_id": ["This field is required"] }
$routeCollector->patch('/guilds/{guild_id}/incidents/{incident_id}', Handler\IncidentUpdateHandler::class);

$dispatcher = new Dispatcher($routeCollector);
$responseStatus = $dispatcher->dispatch(new Request());

switch ($responseStatus)
{
    case Dispatcher::NOT_FOUND:
        header('Content-Type: application/json');
        http_response_code(404);
        die(json_encode(['message' => 'Not Found', 'documentation_url' => 'https://docs.fibril.xyz/api'], JSON_PRETTY_PRINT));
        break;

    case Dispatcher::METHOD_NOT_ALLOWED:
        header('Content-Type: application/json');
        http_response_code(405);
        die(json_encode(['message' => 'Method Not Allowed', 'documentation_url' => 'https://docs.fibril.xyz/api'], JSON_PRETTY_PRINT));
        break;
}

die();
