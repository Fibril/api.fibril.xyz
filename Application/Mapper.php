<?php

abstract class Mapper extends Database
{
    /**
     * Create a new instance of the domain object. If the $data parameter is specified
     * then the object will be populated with it.
     * @param array $data
     * @return DomainObjectAbstract
     */
    public function create(array $data = null)
    {
        $object = $this->_create();
        
        if ($data)
        {
            $this->populate($object, $data);
        }

        return $object;
    }
 
    /**
     * Save the domain object
     *
     * @param DomainObjectAbstract $object
     */
    public function save(&$object)
    {
        if (is_null($object->getId()))
        {
            return $this->_insert($object);
        }
        else
        {
            return $this->_update($object);
        }
    }
 
    /**
     * Delete the domain object
     * @param DomainObjectAbstract $object
     */
    public function delete(&$object)
    {
        $this->_delete($object);
    }
 
    /**
     * Populate the domain object with the values from the data array.
     *
     * @param DomainObjectAbstract &$object
     * @param array $data
     * @return void
     */
    abstract public function populate(&$object, array $data);
 
    /**
     * Create a new instance of a domain object
     *
     * @return DomainObjectAbstract
     */
    abstract protected function _create();
 
    /**
     * Insert the domain object into the database
     *
     * @param DomainObjectAbstract $object
     */
    abstract protected function _insert(&$object);
 
    /**
     * Update the domain object in persistent storage
     *
     * @param DomainObjectAbstract $object
     */
    abstract protected function _update(&$object);
 
    /**
     * Delete the domain object from peristent Storage
     *
     * @param DomainObjectAbstract $object
     */
    abstract protected function _delete(&$object);
}
