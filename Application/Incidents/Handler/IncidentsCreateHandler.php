<?php

namespace Incidents\Handler;

use Dispatcher;
use IncidentMapper;
use Services\Auth\JwtGuard;

class IncidentsCreateHandler
{
    public function __invoke($guildId, $request)
    {
        if (JwtGuard::isAuthorized(['guild_ids' => [$guildId => []]]) !== true)
            return Dispatcher::UNAUTHORIZED;

        $incidentMapper = new IncidentMapper($guildId);

        $data = $request->getData();

        // Ensure that a new incident id doesn't get parsed.
        if (isset($data['id']))
            unset($data['id']);

        // Ensure that a new guild id doesn't get parsed.
        if (isset($data['guild_id']))
            unset($data['guild_id']);

        // Create a new incident.
        $incident = $incidentMapper->create($data);

        // TODO: Check whether the resource was actually created. Respond with 409 Conflict if not.

        // Save the incident to persistent storage.
        $incidentMapper->save($incident);

        header('Content-Type: application/json');
        header('Location: https://api.fibril.xyz/guilds/' . $incident->getGuildId() . '/incidents/' . $incident->getId());

        http_response_code(201);
        die(json_encode($incident, JSON_PRETTY_PRINT));
    }
}
