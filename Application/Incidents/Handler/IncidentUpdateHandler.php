<?php

namespace Incidents\Handler;

use Dispatcher;
use IncidentMapper;
use Services\Auth\JwtGuard;

class IncidentUpdateHandler
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
        {
            $data = $request->getData();

            // Ensure that a new incident id doesn't get parsed.
            if (isset($data['id']))
                unset($data['id']);

            // Ensure that a new guild id doesn't get parsed.
            if (isset($data['guild_id']))
                unset($data['guild_id']);

            // Populate the incident with the new data.
            $incidentMapper->populate($incident, $data);

            // Save the incident to storage.
            $incidentMapper->save($incident);

            return Dispatcher::NO_CONTENT;
        }

        return Dispatcher::NOT_FOUND;
    }
}
