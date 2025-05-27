<?php
namespace SoampliApps\Dao;

abstract class AbstractMethodBasedModel extends AbstractModel
{
    // This model is designed for models which have defined properties and getter/setter methods
    // In this case we don't use a 'bucket' of properties we use properties directly

    public function __get($name)
    {
        $name = str_replace('_', ' ', $name);
        $name = ucwords($name);
        $name = str_replace(' ', '', $name);
        $getter = 'get' . $name;

        return $this->$getter();
    }

    public function __set($name, $value)
    {
        $name = str_replace('_', ' ', $name);
        $setter = 'set' . str_replace(' ', '', ucwords($name));

        return $this->$setter($value);
    }

    public function __call($name, $arguments)
    {
        if (strpos($name, 'set') === 0 && strlen($name) > 3) {
             $property = $this->methodNameToProperty($name);
             $this->$property = $arguments[0];
        } elseif (strpos($name, 'get') === 0 && strlen($name) > 3) {
            $property = $this->methodNameToProperty($name);

            return isset($this->$property) ? $this->$property : null;
        }
    }

    public function hydrate($array)
    {
        foreach ($array as $key => $value) {
            $this->$key = $value;
        }
    }

    public function setUniqueReferenceFieldValue($value)
    {
        $property = $this->fieldNameToProperty($this->dao->getUniqueReferenceField());
        $this->$property = $value;
    }

    public function getUniqueReferenceValue()
    {
        $property = $this->fieldNameToProperty($this->dao->getUniqueReferenceField());

        return $this->$property;
    }

    public function getProperties()
    {
        $properties = array();
        $field_names = $this->dao->getProperties();
        foreach ($field_names as $field_name) {
            $property = $this->fieldNameToProperty($field_name);
            $value = $this->$property;
            $properties[$field_name] = $value;
        }

        return $properties;
    }
}
