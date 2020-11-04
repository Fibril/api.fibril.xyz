<?php

namespace Incidents\Handler;

use Dispatcher;
use IncidentMapper;
use Services\Auth\JwtGuard;

class IncidentsReadHandler
{
    public function __invoke($guildId, $request)
    {
        if (JwtGuard::isAuthorized(['guild_ids' => [$guildId => []]]) !== true)
            return Dispatcher::UNAUTHORIZED;

        $incidentMapper = new IncidentMapper($guildId);

        $params = $request->getQuery();

        // Find the incident with the given id.
        $incidents = $incidentMapper->findAll(
            $params['search'] ?? null,
            $params['per_page'] ?? null,
            $params['page'] ?? null,
            $params['after'] ?? null,
            $params['before'] ?? null
        );

        header('Content-Type: application/json');
        // header('Cache-Control: public, max-age=60, s-maxage=60'); // TODO: Figure out whether to keep this or remove it.

        if ($incidents !== false)
            die(json_encode($incidents, JSON_PRETTY_PRINT)); // TODO: Apply link headers for proper pagination navigation. e.g. header('Link: <https://example.com/users?page=1>; rel="prev", <https://example.com/users?page=3>; rel="next"');

        return Dispatcher::NOT_FOUND;
    }
}
