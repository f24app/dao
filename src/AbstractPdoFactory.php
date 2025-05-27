<?php
namespace SoampliApps\Dao;

abstract class AbstractPdoFactory extends AbstractFactory
{
    protected function buildFromPdoStatement(\PdoStatement $statement)
    {
        return $this->buildFromPdoStatementLogic($statement, null);
    }

    protected function buildFromPdoStatementWithCallback(\PdoStatement $statement, callable $callback)
    {
        return $this->buildFromPdoStatementLogic($statement, $callback);
    }

    protected function buildFromPdoStatementLogic(\PdoStatement $statement, callable $callback = null)
    {
        $collection = $this->getCollection();
        $statement->execute();

        while ($row = $statement->fetch(\PDO::FETCH_ASSOC)) {
            $model = new $this->modelClass($this->container);

            if (!($model instanceof ModelInterface)) {
                throw new \LogicException("The abstract factory only supports models which implement ModelInterface and have a hydrate method");
            }

            $model->setExistsInDatabase(true);

            if (!is_null($callback)) {
                $model = call_user_func_array($callback, array($model, &$row));
            }

            $model->hydrate($row);
            $collection->add($model);
        }

        return $collection;
    }

    protected function incorporateChildModel($primary_model, &$row, $prefix, $primary_model_setter, $child_model, $partial = true, $test_condition = 'id')
    {
        // If the test condition (i.e. ID for a child model) exists and isn't null, then populate the child model
        if (array_key_exists($prefix . $test_condition, $row) && !is_null($row[$prefix . $test_condition])) {
            $properties = array_keys($child_model->getProperties());
            $hydration = array();

            foreach ($properties as $property) {
                if (array_key_exists($prefix . $property, $row)) {
                    $hydration[$property] = $row[$prefix . $property];
                    unset($row[$prefix . $property]);
                }
            }

            if (true == $partial) {
                $child_model->partiallyHydrate($hydration);
            } else {
                $child_model->hydrate($hydration);
                $child_model->setExistsInDatabase(true);
            }

            $primary_model->$primary_model_setter($child_model);
        }

        return $primary_model;
    }
}
