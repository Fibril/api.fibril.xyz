<?php

namespace Incidents\Handler;

use Dispatcher;
use IncidentMapper;
use Services\Auth\JwtGuard;

class IncidentActivityHandler
{
    public function __invoke($guildId, $request)
    {
        if (JwtGuard::isAuthorized(['guild_ids' => [$guildId => []]]) !== true)
            return Dispatcher::UNAUTHORIZED;

        // if (JwtGuard::isAuthorized(['guild_ids' => [$guildId => ['owner' => true]]]) !== true)
        //     return Dispatcher::UNAUTHORIZED;

        $incidentMapper = new IncidentMapper($guildId);

        $params = $request->getQuery();

        $results = $incidentMapper->getActivity(
            $params['after'] ?? null,
            $params['before'] ?? null
        );

        header('Content-Type: application/json');

        $data = [];

        foreach ($results as $result)
        {
            $timestamp = ($result['id'] >> 22) + FIBRIL_EPOCH;
            $date = date('Y-m-d\TH:i:s\Z', $timestamp / 1000);

            if (!in_array($date, $data))
                array_push($data, $date);
        }

        die(json_encode($data, JSON_PRETTY_PRINT));
    }
}
