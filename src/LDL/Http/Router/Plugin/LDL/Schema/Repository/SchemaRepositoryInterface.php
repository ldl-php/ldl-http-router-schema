<?php

namespace LDL\Http\Router\Plugin\LDL\Schema\Repository;

use LDL\Type\Collection\Interfaces\CollectionInterface;

interface SchemaRepositoryInterface extends CollectionInterface
{
    /**
     * Returns a previously added schema by name
     *
     * @param string $schemaName
     * @return array
     */
    public function getSchema(string $schemaName) : array;
}