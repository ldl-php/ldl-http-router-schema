<?php

namespace LDL\Http\Router\Plugin\LDL\Schema\Config;

use LDL\Type\Collection\Types\Object\ObjectCollection;
use LDL\Type\Exception\TypeMismatchException;
use Swaggest\JsonSchema\SchemaContract;

class ResponseSchemaCollection extends ObjectCollection
{

    public function validateItem($item) : void
    {
        parent::validateItem($item);

        if($item instanceof SchemaContract){
            return;
        }

        $msg = sprintf(
            'Item must be an instance of "%s", "%s" was given',
            SchemaContract::class,
            get_class($item)
        );

        throw new TypeMismatchException($msg);
    }
}