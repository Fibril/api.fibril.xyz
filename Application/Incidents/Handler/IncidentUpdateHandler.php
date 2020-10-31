<?php

namespace Incidents\Handler;

class IncidentUpdateHandler
{
    public function __invoke($guildId, $incidentId, $request) 
    {
        $incidentMapper = new \IncidentMapper($guildId);

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

            // 204: No Content reponse code as per RFC 2616 Section 9.6
            http_response_code(204);
            die();
        }

        http_response_code(404);
        die(json_encode(array('message' => 'Not Found', 'documentation_url' => 'https://docs.fibril.xyz/api'), JSON_PRETTY_PRINT));
    }
}
