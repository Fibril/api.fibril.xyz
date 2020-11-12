<?php

class IncidentMapper extends Mapper
{
    private $guildId;

    public function __construct($guildId)
    {
        $this->guildId = $guildId;
    }

    /**
     * Create a new Incident domainobject
     */
    protected function _create()
    {
        return new Incident();
    }

    private static $previousSnowflakeRandomSequence;
    private static $previousSnowflakeDeltaTimestamp;

    /**
     * Gets a new snowflake id. The id will be returned as a string,
     * as not all languages supports 64-bit integers. Note that the
     * snowflake may not be unique.
     *
     * @return string Returns a snowflake id as a string value.
     */
    private static function generateSnowflake() // XXX: Move this somewhere else? Yeah, I should...
    {
        do
        {
            $currentTimestamp = round(microtime(true) * 1000); // Current timestamp in milliseconds.
            $deltaTimestamp = $currentTimestamp - FIBRIL_EPOCH; // Amount of milliseconds since epoch time.
            $randomSequence = random_int(0, 4194303); // 2^22 - 1 = 4194303
        } while ($randomSequence == self::$previousSnowflakeRandomSequence && $deltaTimestamp == self::$previousSnowflakeDeltaTimestamp);

        $snowflake = bindec(sprintf('%042b', $deltaTimestamp) . sprintf('%022b', $randomSequence));

        self::$previousSnowflakeRandomSequence = $randomSequence;
        self::$previousSnowflakeDeltaTimestamp = $deltaTimestamp;

        // Timestamp                                  Random sequence
        // 111111111111111111111111111111111111111111 1111111111111111111111
        // 64                                         22                    0

        return strval($snowflake);
    }

    /**
     * Insert the DomainObject in persistent storage.
     *
     * @param DomainObjectAbstract $object
     */
    protected function _insert(&$object)
    {
        $currentAttempts = 0;

        do
        {
            $object->setId(self::generateSnowflake());
            $object->setGuildId($this->guildId);

            try
            {
                // Insert Incident object in database.
                $sql = 'INSERT INTO incidents VALUES (?, ?, ?, ?, ?, ?, ?, ?)';
                $this->query($sql, [
                    $object->getId(),
                    $object->getGuildId(),
                    $object->getStaffId(),
                    $object->getStaffUsername(),
                    $object->getOffenderId(),
                    $object->getOffenderUsername(),
                    $object->getActionTaken(),
                    $object->getDescription(),
                ]);

                return $object;
            }
            catch (PDOException $e)
            {
                $currentAttempts++;

                // Check whether the error was caused by a conflicting resource.
                if ($e->errorInfo[1] == 1062)
                {
                    // Sleep for 1 microsecond.
                    usleep(1);

                    // Try again.
                    continue;
                }
                else
                {
                    // An error other than a duplicate entry occurred.
                    break;
                }
            }

            break;
        } while ($currentAttempts < 10);

        return false;
    }

    /**
     * Update the Incident to persistent storage.
     *
     * @param DomainObjectAbstract $object
     */
    protected function _update(&$object)
    {
        $sql = 'UPDATE incidents SET staff_id=?, staff_username=?, offender_id=?, offender_username=?, action_taken=?, description=? WHERE guild_id = ? AND id = ?';
        $wasSuccessful = $this->query($sql, [
            $object->getStaffId(),
            $object->getStaffUsername(),
            $object->getOffenderId(),
            $object->getOffenderUsername(),
            $object->getActionTaken(),
            $object->getDescription(),
            $object->getGuildId(),
            $object->getId(),
        ]);

        return $wasSuccessful;
    }

    /**
     * Delete the Incident from persistent storage
     *
     * @param DomainObjectAbstract $object
     */
    protected function _delete(&$object)
    {
        $sql = 'DELETE FROM incidents WHERE guild_id=? AND id=?';
        $this->query($sql, [
            $object->getGuildId(),
            $object->getId(),
        ]);

        unset($object);
    }

    /**
     * Populate the Incident (DomainObject) with the data array.
     *
     * @param  DomainObjectAbstract $object
     * @param  array                $data
     * @return void
     */
    public function populate(&$object, array $data)
    {
        if (is_null($object))
        {
            return false;
        }

        if (isset($data['id']))
        {
            $object->setId($data['id']);
        }

        if (isset($data['guild_id']))
        {
            $object->setGuildId($data['guild_id']);
        }

        if (isset($data['staff_id']))
        {
            $object->setStaffId($data['staff_id']);
        }

        if (isset($data['staff_username']))
        {
            $object->setStaffUsername($data['staff_username']);
        }

        if (isset($data['offender_id']))
        {
            $object->setOffenderId($data['offender_id']);
        }

        if (isset($data['offender_username']))
        {
            $object->setOffenderUsername($data['offender_username']);
        }

        if (isset($data['action_taken']))
        {
            $object->setActionTaken($data['action_taken']);
        }

        if (isset($data['description']))
        {
            $object->setDescription($data['description']);
        }
    }

    /**
     * Retrieves incident by its snowflake id.
     *
     * @param  int      $incidentId
     * @return Incident (false if not found)
     */
    public function findById($incidentId)
    {
        $sql = 'SELECT * FROM incidents WHERE guild_id = ? AND id = ?';

        $data = $this->query($sql, [$this->guildId, $incidentId], true);

        // Gets the first element of the associative array. Thus the first and only incident.
        $data = reset($data);

        $incident = false;

        if ($data != false)
        {
            $incident = $this->create($data);
        }

        return $incident;
    }

    /**
     * Retrieves all incidents.
     * 
     * @param  string $search  Any whole or partial search for username or their user id.
     * @param  int    $perPage Maximum amount of incidents to be returned.
     * @param  int    $page    Current page number.
     * @param  string $after   Fetches all incidents after this incident id.
     * @param  string $before  Fetches all incidents before this incident id.
     * @return array           (empty array if none found)
     */
    public function findAll($search = null, $perPage = null, $page = null, $after = null, $before = null)
    {
        if (is_null($before) || $before < 1)
            $before = (round(microtime(true) * 1000) - FIBRIL_EPOCH) << 22;

        if (is_null($after) || $after > $before)
            $after = 0;

        $search = is_null($search) || empty($search) ? '%' : '%' . strtolower($search) . '%';

        // Amount of incidents per page defaults to 30 if below 1, null, or NaN.
        $perPage = intval(is_null($perPage) || $perPage < 1 ? 30 : ($perPage > 100 ? 100 : $perPage));

        // Page number defaults to 1 if below 1, null, or NaN.
        $page = intval(is_null($page) || $page < 1 ? 1 : $page);

        // Amount of incidents returned maxes out at 100 incidents per page.
        $offset = (intval($page) - 1) * intval($perPage);

        $sql = 'SELECT * FROM incidents WHERE (
            LOWER(incidents.staff_username) LIKE ? OR
            LOWER(incidents.staff_id) LIKE ? OR
            LOWER(incidents.offender_username) LIKE ? OR
            LOWER(incidents.offender_id) LIKE ?) AND
            incidents.guild_id = ? AND
            incidents.id BETWEEN ? AND ?
            ORDER BY incidents.id DESC LIMIT ? OFFSET ?';

        $results = $this->query($sql, [
            $search,
            $search,
            $search,
            $search,
            $this->guildId,
            $after,
            $before,
            $perPage,
            $offset,
        ], true);

        $incidents = [];
        foreach ($results as $result)
        {
            $incident = $this->create($result);

            // TODO: Add some hal+json like pagination to each incident before they all get pushed together.
            array_push($incidents, $incident);
        }

        return $incidents;
    }

    public function getActivity($after, $before)
    {
        if (is_null($before) || $before < 1)
            $before = (round(microtime(true) * 1000) - FIBRIL_EPOCH) << 22;

        if (is_null($after) || $after > $before)
            $after = 0;

        $sql = 'SELECT id FROM incidents WHERE guild_id = ? AND id BETWEEN ? AND ?';

        $data = $this->query($sql, [$this->guildId, $after, $before], true);

        $result = false;

        if ($data != false)
            $result = $data;

        return $result;
    }
}
