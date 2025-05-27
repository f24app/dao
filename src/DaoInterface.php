<?php
namespace SoampliApps\Dao;

interface DaoInterface
{
    /**
     * Data Access Object constructor
     * @param array $container dependency injection container - this is where we will get the database layer from
     * @return void
     */
    public function __construct($container);

    /**
     * Create from unique reference
     * @param mixed $unique_reference a unique reference such as a primary key to get data for
     * @param object $model Optional parameter of the model to use, if null method will have to populate
     */
    public function createFromUniqueReference($unique_reference, ModelInterface $model = null);

    /**
     * Creates a collection of models based of a named query and some db parameters
     * @param string $named_query the reference of the query (should relate to a private method within DAO class)
     * @param array $parameters query parameters to be passed to the named query method
     * @param IteratorAggregate $collection Optional pre-existing collection for these models to go into
     */
    public function createCollectionFromNamedQuery($named_query, array $paramaters = array(), \IteratorAggregate $collection = null);

    /**
     * Save a model in the database
     * @param object $model
     * @return void
     * @throws OutOfBoundsException
     * @throws \LogicException
     */
    public function save(ModelInterface $model);

    /**
     * Delete a model from the database
     * @param object $model
     * @return void
     * @throws \OutOfBoundsException
     */
    public function delete(ModelInterface $object);

    /**
     * Save multiple records in the database in one go
     * @param array $collection a collection of models
     * @return void
     * @throws \OutOfBoundsException
     */
    public function saveMany($collection);

    /**
     * Get properties for a model from database fields
     * @return array
     */
    public function getProperties();

    /**
     * Get the field used as unique reference in the database, typically PK
     * @return string
     */
    public function getUniqueReferenceField();
}
