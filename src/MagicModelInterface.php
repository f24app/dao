<?php
namespace SoampliApps\Dao;

interface MagicModelInterface
{
    public function __set($name, $value);
    public function __get($name);
}
