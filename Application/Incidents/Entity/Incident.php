<?php

namespace Incidents\Entity;

class Incident implements \JsonSerializable
{
    private $id;
    private $guildId;
    private $staffId;
    private $staffUsername;
    private $offenderId;
    private $offenderUsername;
    private $actionTaken;
    private $description;

    public function setId($id) 
    {
        $this->id = $id;
    }

    public function setGuildId($guildId) 
    {
        $this->guildId = $guildId;
    }

    public function setStaffId($staffId) 
    {
        $this->staffId = $staffId;
    }

    public function setStaffUsername($staffUsername) 
    {
        $this->staffUsername = $staffUsername;
    }

    public function setOffenderId($offenderId) 
    {
        $this->offenderId = $offenderId;
    }

    public function setOffenderUsername($offenderUsername) 
    {
        $this->offenderUsername = $offenderUsername;
    }

    public function setActionTaken($actionTaken) 
    {
        $this->actionTaken = $actionTaken;
    }

    public function setDescription($description) 
    {
        $this->description = $description;
    }

    public function getId() 
    {
        return $this->id;
    }

    public function getGuildId() 
    {
        return $this->guildId;
    }

    public function getStaffId() 
    {
        return $this->staffId;
    }

    public function getStaffUsername() 
    {
        return $this->staffUsername;
    }

    public function getOffenderId() 
    {
        return $this->offenderId;
    }

    public function getOffenderUsername() 
    {
        return $this->offenderUsername;
    }

    public function getActionTaken() 
    {
        return $this->actionTaken;
    }

    public function getDescription() 
    {
        return $this->description;
    }

    public function getTimestamp()
    {
        return ($this->id >> 22) + FIBRIL_EPOCH;
    }

    public function jsonSerialize()
    {
        return [
            'url' => 'https://fibril.xyz/dashboard/' . $this->guildId . '/incidents/' . $this->id,
            'id' => $this->id,
            'guild_id' => $this->guildId,
            // 'staff_id' => $this->staffMember->getId(),
            // 'staff_username' => $this->staffMember->getUsername(),
            // 'offender_id' => $this->offender->getId(),
            // 'offender_username' => $this->offender->getUsername(),
            'staff_id' => $this->getStaffId(),
            'staff_username' => $this->getStaffUsername(),
            'offender_id' => $this->getOffenderId(),
            'offender_username' => $this->getOffenderUsername(),
            'action_taken' => $this->actionTaken,
            'description' => $this->description,
            'created_at' => date('Y-m-d\TH:i:s\Z', $this->getTimestamp() / 1000),
            'last_modified_at' => date('Y-m-d\TH:i:s\Z', $this->getTimestamp() / 1000), // TODO: Remove this line.
            '_links' => [
                'self' => [
                    'href' => 'https://api.fibril.xyz/guilds/' . $this->guildId . '/incidents/' . $this->id
                ],
                'html' => [
                    'href' => 'https://fibril.xyz/dashboard/' . $this->guildId . '/incidents/' . $this->id
                ]
            ]
        ];
    }
    
    // public function handle($request) 
    // {
    //     $paginator = new Paginator($query);
    //     $totalItems = count($paginator);

    //     $currentPage = ($request->getAttribute('page')) ?: 1;
    //     $totalPageCount = ceil($totalItems / $this->perPageCount);
    //     $nextPage = (($currentPage < $totalPageCount) ? $currentPage + 1 : $totalPageCount);
    //     $previousPage = (($currentPage > 1) ? $currentPage - 1 : 1);

    //     $result['_per_page'] = $this->perPageCount;
    //     $result['_page'] = $currentCount;
    //     $result['_total'] = $totalItems;
    //     $result['_total_pages'] = $totalPageCount;

    //     //$result['_links']['self']['href'] = 'https://api.fibril.xyz/guilds/' . $this->guildId . '/incidents/' . $this->id;

    //     $result['_links']['self'] = $this->urlHelper->generate('incidents.read', ['page' => $currentPage]);
    //     $result['_links']['first'] = $this->urlHelper->generate('incidents.read', ['page' => 1]);
    //     $result['_links']['prev'] = $this->urlHelper->generate('incidents.read', ['page' => $previousPage]);
    //     $result['_links']['next'] = $this->urlHelper->generate('incidents.read', ['page' => $nextPage]);
    //     $result['_links']['last'] = $this->urlHelper->generate('incidents.read', ['page' => $totalPageCount]);
    //     $result['_links']['create'] = $this->urlHelper->generate('incidents.create');
    //     $result['_links']['read'] = $this->urlHelper->generate('incidents.read', ['page' => 1]);

    //     return $result;
    // }
}
