<?php

namespace LDL\Http\Router\Plugin\LDL\Schema\Repository;

use LDL\Type\Collection\Interfaces\CollectionInterface;
use LDL\Type\Collection\Interfaces\Validation\HasKeyValidatorChainInterface;
use LDL\Type\Collection\Interfaces\Validation\HasValidatorChainInterface;

interface SchemaRepositoryInterface extends CollectionInterface, HasValidatorChainInterface, HasKeyValidatorChainInterface
{
    /**
     * Returns a previously added schema by name
     *
     * @param string $schemaName
     * @return array
     */
    public function getSchema(string $schemaName) : array;
}