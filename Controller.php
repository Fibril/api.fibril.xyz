<?php

header('Access-Control-Allow-Origin: https://fibril.xyz');
header('Access-Control-Allow-Credentials: true');
// header('Access-Control-Allow-Methods: GET, POST');

include __DIR__ . '/Includes/Autoloader.php';

define('FIBRIL_EPOCH', 1555452000000); // Fibril's birthday, 17th of April 2019.
define('JWT_SECRET', Config::get('api', 'secret'));

date_default_timezone_set('UTC');

$routeCollector = new RouteCollector();

use Incidents\Handler;
use Services\Auth\DiscordLoginHandler;


$routeCollector->get('/login', DiscordLoginHandler::class); // TODO: Does it really log into Discord??? Nah, name it something better.
// $routeCollector->get('/logout', FibrilLogoutHandler::class);

$routeCollector->get('/guilds/{guild_id}/incidents', Handler\IncidentsReadHandler::class);
$routeCollector->post('/guilds/{guild_id}/incidents', Handler\IncidentsCreateHandler::class);

$routeCollector->get('/guilds/{guild_id}/incidents/{incident_id}', Handler\IncidentReadHandler::class);
$routeCollector->delete('/guilds/{guild_id}/incidents/{incident_id}', Handler\IncidentDeleteHandler::class);
// $routeCollector->put('/guilds/{guild_id}/incidents/{incident_id}', Handler\IncidentUpdateHandler::class); // TODO: { "guild_id": ["This field is required"] }
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

    case Dispatcher::UNAUTHORIZED:
        header('Content-Type: application/json');
        http_response_code(401);
        die(json_encode(array('message' => 'Requires authentication', 'documentation_url' => 'https://docs.fibril.xyz/api'), JSON_PRETTY_PRINT));
        break;

    case Dispatcher::NO_CONTENT:
        http_response_code(204);
        die();
        break;
}

die();
