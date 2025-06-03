<?php
namespace SoampliApps\Dao;

abstract class AbstractModel implements ModelInterface, MagicModelInterface
{
    protected $container;
    protected $dao;
    // SHOULD DO: consider changing this to a constant, and accessing within the constructor via constant(get_class($this)."::CONST_NAME")
    protected $daoContainerKey;
    protected $valid = false;
    protected $existsInDatabase = false;
    protected $partiallyHydrated = false;

    // If the getters and setters are not implemented, the default magic methods will use this array as a bucket
    protected $properties = array();

    public function __construct($container = null, $unique_reference = null)
    {
        if (!is_array($container) && !$container instanceof \ArrayAccess) {
            throw new \InvalidArgumentException("Container should be an array or an object which implements ArrayAccess");
        }

        $this->container = $container;
        $this->dao = $container['data_access_objects'][$this->daoContainerKey];

        if ($this->dao instanceof DaoInterface) {
            if (!is_null($unique_reference)) {
                try {
                    $this->dao->createFromUniqueReference($unique_reference, $this);
                    $this->setValid(true);
                    $this->setExistsInDatabase(true);
                } catch (\Exception $e) {
                    $this->existsInDatabase = false;
                    $this->false = false;
                }
            }
        } else {
            throw new \RuntimeException("Container injected DAO does not implement DaoInterface");
        }
    }

    public function setUniqueReferenceFieldValue($value)
    {
        $this->properties[$this->dao->getUniqueReferenceField()] = $value;
    }

    public function getUniqueReferenceValue()
    {
        return $this->properties[$this->dao->getUniqueReferenceField()];
    }

    public function setValid($valid)
    {
        $this->valid = $valid;
    }

    public function setExistsInDatabase($exists_in_db)
    {
        $this->existsInDatabase = $exists_in_db;
    }

    public function existsInDatabase()
    {
        return $this->existsInDatabase;
    }

    public function isValid()
    {
        return $this->isValid;
    }

    public function getProperties()
    {
        $properties = array();
        foreach ($this->properties as $key => $value) {
            $properties[$this->propertyToFieldName($key)] = $value;
        }

        return $properties;
    }

    public function save()
    {
        if ($this->isPartiallyHydrated()) {
            throw new \LogicException("This model is only partially hydrated and cannot be saved");
        }

        try {
            $this->dao->save($this);
            $this->setValid(true);
            $this->setExistsInDatabase(true);

            return true;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function delete()
    {
        if ($this->isPartiallyHydrated()) {
            throw new \LogicException("This model is only partially hydrated and cannot be deleted");
        }

        try {
            $this->dao->delete($this);
            $this->setValid(false);
            $this->setExistsInDatabase(false);

            return true;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function __set($name, $value)
    {
        //$this->properties[$property] = $value;
        /**
         * Used if we want to set actual properties, not magic db ones
         * $name = str_replace('_', ' ', $name);
         * $setter = 'set' . str_replace(' ', '', ucwords($name));
         * return $this->$setter($value);
        */
        $property = $this->fieldNameToProperty($name);
        $this->properties[$property] = $value;
    }

    public function __get($name)
    {
        /**
         * Getter for if we want to get actual properties and not magic db ones
         * $name = str_replace('_', ' ', $name);
         *   $name = ucwords($name);
         *   $name = str_replace(' ', '', $name);
         *   $getter = 'get' . $name;
         *   return $this->$getter();
         */
        $property = $this->fieldNameToProperty($name);

        return $this->properties[$property];
    }

    public function __call($name, $arguments)
    {
        if (strpos($name, 'set') === 0 && strlen($name) > 3) {
            /*
                This comment block is used if we want to set properties directly
                $property = lcfirst(substr($name, 2));
                $this->$property = $arguments[0];
             */
             $property = $this->methodNameToProperty($name);
             $this->properties[$property] = $arguments[0];
        } elseif (strpos($name, 'get') === 0 && strlen($name) > 3) {
            /*
             * This comment block is used if we want to set properties directly
             * $property = lcfirst(substr($name, 2));
             * return $this->$property;
             */
            $property = $this->methodNameToProperty($name);

            return isset($this->properties[$property]) ? $this->properties[$property] : null;
        }
    }

    public function hydrate($array)
    {
        foreach ($array as $key => $value) {
            $this->properties[$key] = $value;
        }

        return $this->properties;
    }

    public function partiallyHydrate($data)
    {
        $this->hydrate($data);
        $this->partiallyHydrated = true;
    }

    public function isPartiallyHydrated()
    {
        return (bool) $this->partiallyHydrated;
    }

    public function __clone()
    {
        $pkf = $this->dao->getUniqueReferenceField();
        $this->$pkf = null;
        $this->valid = false;
        $this->existsInDatabase = false;
    }

    /**
     * Convert a field name to a property name
     * @param String $name the name of the database field e.g. my_database_field
     * @return String the name of the property e.g. myDatabaseField
     */
    protected function fieldNameToProperty($name)
    {
        $property = implode((array_map('ucfirst', explode('_', $name))));

        return lcfirst($property);
    }

    /**
     * Converts a property name into a field name
     * @param String $property the name of the object property e.g. id, name, someProperty
     * @return String e.g. id, name, some_property
     */
    protected function propertyToFieldName($property)
    {
        return strtolower(preg_replace("/([A-Z])/", '_\\1', $property));
    }

    /**
     * Get the name of an object property from a method name, pased from the __call() method above
     * @param String $method_name
     * @return String
     */
    protected function methodNameToProperty($method_name)
    {
        return lcfirst(substr($method_name, 3, strlen($method_name)));
    }
}
