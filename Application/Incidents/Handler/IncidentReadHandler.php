<?php

namespace Incidents\Handler;

class IncidentReadHandler// implements HandlerInterface
{
    public function __invoke($guildId, $incidentId, $request)
    {
        $incidentMapper = new \IncidentMapper($guildId);

        // Find the incident with the given id.
        $incident = $incidentMapper->findById($incidentId);

        header('Content-Type: application/json');

        if ($incident !== false)
        {
            die(json_encode($incident, JSON_PRETTY_PRINT));
        }

        http_response_code(404);
        die(json_encode(array('message' => 'Not Found', 'documentation_url' => 'https://docs.fibril.xyz/api'), JSON_PRETTY_PRINT));
    }
}
