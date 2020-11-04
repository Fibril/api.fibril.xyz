<?php

namespace Incidents\Handler;

use Dispatcher;
use IncidentMapper;
use Services\Auth\JwtGuard;

class IncidentReadHandler
{
    public function __invoke($guildId, $incidentId, $request)
    {
        if (JwtGuard::isAuthorized(['guild_ids' => [$guildId => []]]) !== true)
            return Dispatcher::UNAUTHORIZED;

        $incidentMapper = new IncidentMapper($guildId);

        // Find the incident with the given id.
        $incident = $incidentMapper->findById($incidentId);

        header('Content-Type: application/json');

        if ($incident !== false)
            die(json_encode($incident, JSON_PRETTY_PRINT));

        return Dispatcher::NOT_FOUND;
    }
}
