<?php
namespace SoampliApps\Dao;

abstract class AbstractPdoDao implements DaoInterface
{
    // TODO: See if array is a suitable type hint (does pimple / array access work, or does array implement arrayaccess instead)
    // key to access the database engine from the container
    protected $databaseEngineReference = 'pdo';
    protected $databaseEngine;
    protected $tableName = '';
    protected $tableAlias = null;
    protected $aliasPrefix = null;
    protected $uniqueReferenceField = 'id';
    // Type of unique reference field, should be int or string
    protected $uniqueReferenceFieldType = 'int';
    // key => value pairs of field name and PDO data type
    protected $fields = array();

    /**
     * Data Access Object constructor
     * @param array|ArrayAccess $container dependency injection container - this is where we will get the database layer from
     * @return void
     */
    public function __construct($container)
    {
        $this->databaseEngine = $container[$this->databaseEngineReference];
    }

    /**
     * Create from unique reference
     * @param mixed $unique_reference a unique reference such as a primary key to get data for
     * @param object $model Optional parameter of the model to use, if null method will have to populate
     */
    public function createFromUniqueReference($unique_reference, ModelInterface $model = null)
    {
        $sql = "SELECT
                    *
                FROM
                    `{$this->tableName}`
                WHERE
                    `{$this->uniqueReferenceField}`=:{$this->uniqueReferenceField}
                LIMIT
                    1";

        $statement = $this->databaseEngine->prepare($sql);
        $statement->bindParam(':' . $this->uniqueReferenceField, $unique_reference, ('int' == $this->uniqueReferenceFieldType) ? \PDO::PARAM_INT : \PDO::PARAM_STR);
        $statement->execute();

        if (1 == $statement->rowCount()) {
            $statement->setFetchMode(\PDO::FETCH_INTO, $model);
            $statement->fetch();
        } else {
            throw new \OutOfBoundsException("Record in table '{$this->tableName}' with reference of '{$unique_reference}' was not found in the database");
        }

        return $model;
    }

    /**
     * Creates a collection of models based of a named query and some db parameters
     * @param string $named_query the reference of the query (should relate to a private method within DAO class)
     * @param array $parameters query parameters to be passed to the named query method
     * @param IteratorAggregate $collection Optional pre-existing collection for these models to go into
     */
    public function createCollectionFromNamedQuery($named_query, array $paramaters = array(), \IteratorAggregate $collection = null)
    {
        throw new \LogicException("The createCollectionFromNamedQuery must be implemented if you want to use it for a specific model");
    }

    /**
     * Save the model in the database
     * @param ModelInterface $model
     * @return void
     * @throws \OutOfBoundsException
     * @throws \LogicException
     */
    public function save(ModelInterface $model)
    {
        // todo: complete implementation
        // todo: catch exception
        // todo: throw exception
        if ($model->existsInDatabase()) {
            $this->update($model);
        } else {
            $this->insert($model);
        }
    }

    /**
     * Insert the model data in the database
     * @param ModelInterface $model
     * @return void
     * @throws \LogicException
     */
    protected function insert(ModelInterface $model)
    {
        // todo: complete implementation
        // todo: catch exception
        // todo: throw exception
        $fields = array();
        $params = array();
        $iteratable_fields = (empty($this->fields)) ? $model->getProperties() : $this->fields;

        foreach ($model->getProperties() as $field => $value) {
            // Limitation of the PDO DAO I've made
            if ($field != $this->uniqueReferenceField) {
                $fields[] = "`" . $field . "`";
                $params[] = ":" . $field;
            }
        }

        $fields = implode(',', $fields);
        $params = implode(',', $params);
        $sql = "INSERT INTO
                        `{$this->tableName}`
                    ({$fields})
                    VALUES
                    ({$params})";

        $statement = $this->databaseEngine->prepare($sql);

        $i = 0;
        foreach ($iteratable_fields as $field => $type) {
            if ($field != $this->uniqueReferenceField) {
                $value_field = $value . $i;
                $$value_field = $model->$field; // needed to prevent some overload issue, guessing its passed to pdo by reference
                $statement->bindParam(":" . $field, $$value_field, (isset($this->fields[$field])) ? $this->fields[$field] : \PDO::PARAM_STR );
                $i++;
            }
        }
        $statement->execute();

        if (1 != $statement->rowCount()) {
            throw new \LogicException("Unable to insert into database");
        }

        $model->setUniqueReferenceFieldValue($this->databaseEngine->lastInsertId());
    }

    /**
     * Update the model data in the database
     * @param ModelInterface $model
     * @return void
     * @throws \OutOfBoundsException
     */
    protected function update(ModelInterface $model)
    {
        // TODO: cleanup
        // todo: complete implementation
        // todo: catch exception
        // todo: throw exception
        $fields = array();
        $params = array();
        $iteratable_fields = $model->getProperties();

        $sql = "UPDATE
                    `{$this->tableName}`
                SET ";

        $update_fields = array();
        foreach ($iteratable_fields as $field => $value) {
            if ($field != $this->uniqueReferenceField) {
                $update_fields[] = "`" . $field . "`=:" . $field;
            }
        }

        $sql .= implode(',', $update_fields);
        $sql .= " WHERE
                    `{$this->uniqueReferenceField}`=:{$this->uniqueReferenceField}
                 LIMIT 1";

        $statement = $this->databaseEngine->prepare($sql);
        $i = 0;

        foreach ($iteratable_fields as $field => $type) {
            $value_field = $field . $i;
            $$value_field = $model->$field; // needed to prevent some overload issue, guessing its passed to pdo by reference
            $statement->bindParam(":" . $field, $$value_field, (isset($this->fields[$field])) ? $this->fields[$field] : \PDO::PARAM_STR );
        }

        $statement->execute();
        if (1 != $statement->rowCount()) {
            // TODO: think this through, if no changes, then this is 0
            //throw new \OutOfBoundsException("Record not found in the database");
        }
    }

    /**
    * Delete the model from the database
    * @param ModelInterface $model
    * @return void
    * @throws \OutOfBoundsException
    */
    public function delete(ModelInterface $object)
    {
        $sql = "DELETE FROM
                    `{$this->tableName}`
                WHERE
                    `{$this->uniqueReferenceField}`=:unique_reference
                LIMIT
                    1";

        $statement = $this->databaseEngine->prepare($sql);
        $value = $object->getUniqueReferenceValue();
        $statement->bindParam(':unique_reference', $value, ('int' == $this->uniqueReferenceFieldType) ? \PDO::PARAM_INT : \PDO::PARAM_STR);
        $statement->execute();

        if (1 !== $statement->rowCount()) {
            throw new \OutOfBoundsException("Record in table '{$this->table}' with reference of '{$value}' was not found in the database when deleting");
        }
    }

    /**
     * Toggle a boolean value in a record
     * @param ModelInterface $model the model to toggle a property of
     * @param string $field_name the field to toggle
     * @throws \OutOfBoundsException
     */
    public function toggleBoolean(ModelInterface $object, $field_name)
    {
        $sql = "UPDATE
                    `{$this->tableName}`
                SET
                    `{$field_name}` = (1 - `{$field_name}`)
                WHERE
                    `{$this->uniqueReferenceField}`=:unique_reference
                ";

        $statement = $this->databaseEngine->prepare($sql);
        $value = $object->getUniqueReferenceValue();
        $statement->bindParam(':unique_reference', $value, ('int' == $this->uniqueReferenceFieldType) ? \PDO::PARAM_INT : \PDO::PARAM_STR);
        $statement->execute();

        if (1 !== $statement->rowCount()) {
            throw new \OutOfBoundsException("Record in table '{$this->table}' with reference of '{$value}' was not found in the database when toggling the " . $field_name . " field");
        }
    }

    /**
     * Save and update a collection of models in the database
     * @param array|ArrayAccess $collection
     * @return void
     * @throws \OutOfBoundsException
     * @throws \LogicException
     */
    public function saveMany($collection)
    {
        $bulk_inserts = array();
        $bulk_updates = array();

        foreach ($collection as $model) {
            if ($model->existsInDatabase()) {
                $bulk_updates[] = $model;
            } else {
                $bulk_inserts[] = $model;
            }
        }

        $this->bulkUpdate($bulk_updates);
        $this->bulkInsert($bulk_inserts);
    }

    /**
     * SUpdate a collection of models in the database
     * @param array $collection
     * @return void
     * @throws \OutOfBoundsException
     */
    protected function bulkUpdate(array $collection)
    {
        // todo: implement
    }

    /**
     * Insert a collection of models in the database
     * @param array $collection
     * @return void
     * @throws \LogicException
     */
    public function bulkInsert(array $collection)
    {
        // todo: implement
    }

    /**
     * Get the field used as unique reference in the database, typically PK
     * @return string
     */
    public function getUniqueReferenceField()
    {
        return $this->uniqueReferenceField;
    }

    /**
     * Get properties for a model from database fields
     * @return array
     */
    public function getProperties()
    {
        return array_keys($this->fields);
    }
}
