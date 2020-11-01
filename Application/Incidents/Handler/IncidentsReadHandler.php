<?php

namespace Incidents\Handler;

class IncidentsReadHandler
{
    public function __invoke($guildId, $request) 
    {
        $incidentMapper = new \IncidentMapper($guildId);

        $params = $request->getQuery();

        // Find the incident with the given id.
        $incidents = $incidentMapper->findAll(
            $params['search'], 
            $params['per_page'], 
            $params['page'], 
            $params['after'], 
            $params['before']
        );

        header('Content-Type: application/json');
        // header('Cache-Control: public, max-age=60, s-maxage=60'); // TODO: Figure out whether to keep this or remove it.

        if ($incidents !== false)
            die(json_encode($incidents, JSON_PRETTY_PRINT));

        http_response_code(404);
        die(json_encode(array('message' => 'Not Found', 'documentation_url' => 'https://docs.fibril.xyz/api'), JSON_PRETTY_PRINT));
    }
}