<?php
namespace SoampliApps\Dao;

interface ModelInterface
{
    /**
     * Standard model constructor
     * @param array|ArrayAccess $container a dependency injection container - must be array or implement array access
     * @param mixed $unique_reference a unique reference for a single
     */
    public function __construct($container, $unique_reference = null);

    /**
     * Set the value of the unique reference field (typically PK value)
     * @param mixed $value
     * @return void
     */
    public function setUniqueReferenceFieldValue($value);

    public function getUniqueReferenceValue();

    /**
     * Check if the data represented in a model is from the database
     * Useful when doing a bulk insert or update
     * we can use this to segregate models into two groups
     * those that need to be inserted (method returns false)
     * those that need to be updated (method returns true)
     * @return boolean
     */
    public function existsInDatabase();

    /**
     * Check if a model is valid
     * In many cases this may well just alias the above
     * designed to let you discover if a model has been hydrated from a PK reference
     * @return boolean
     */
    public function isValid();

    /**
     * Set if the model is valid
     * @param boolean $valid
     * @return void
     */
    public function setValid($valid);

    /**
     * Set if the model exists in the database
     * @param boolean $valid
     * @return void
     */
    public function setExistsInDatabase($exists_in_db);

    /**
     * Save the model into the database (inserts or updates)
     * @return boolean
     */
    public function save();

    /**
     * Delete the model from the database
     * @return boolean
     */
    public function delete();

    /**
     * Get properties and models from a model (key = field, value = db value)
     * @return array
     */
    public function getProperties();

    /**
     * Hydrate an the object using the contents of an array (key is converted to field, value = property value)
     * @param array
     * @return void
     */
    public function hydrate($array);
}
