<?php

namespace Incidents\Handler;

use IncidentMapper;

class IncidentDeleteHandler
{
    public function __invoke($guildId, $incidentId, $request)
    {
        $incidentMapper = new IncidentMapper($guildId);

        // Find the incident with the given id.
        $incident = $incidentMapper->findById($incidentId);

        header('Content-Type: application/json');

        if ($incident !== false)
        {
            // Remove the incident from persistent storage.
            $incidentMapper->delete($incident);

            http_response_code(204);
            die();
        }

        http_response_code(404);
        die(json_encode(array('message' => 'Not Found', 'documentation_url' => 'https://docs.fibril.xyz/api'), JSON_PRETTY_PRINT));
    }
}
