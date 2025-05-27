<?php
namespace SoampliApps\Dao;

abstract class AbstractFactory
{
    protected $container;
    protected $modelClass = null;

    public function __construct($container)
    {
        $this->container = $container;

        if (is_null($this->modelClass)) {
            throw new \LogicException("Factory instantiated without a model class reference");
        }
    }

    protected function getCollection()
    {
        // Created this method, so sub-classes can use their own collections if they wish
        // This method will only work if the container contains a collection with key collection, so override if not
        $collection = $this->container['collection']();
        return $collection;
    }
}
