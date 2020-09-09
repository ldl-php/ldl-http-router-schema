<?php

namespace LDL\Http\Router\Plugin\LDL\Schema\Repository;

use LDL\Type\Collection\AbstractCollection;
use LDL\Type\Collection\Interfaces;
use LDL\Type\Exception\TypeMismatchException;

class SchemaRepository extends AbstractCollection implements SchemaRepositoryInterface
{
    public function append($item, $key = null): Interfaces\CollectionInterface
    {
        if(null === $key){
            throw new \InvalidArgumentException("Schema must have a name");
        }

        if($this->offsetExists($key)){
            throw new \InvalidArgumentException("Duplicated schema name: \"$key\"");
        }

        return parent::append($item, $key);
    }

    public function getSchema(string $name) : array
    {
        try {
            $file = $this->offsetGet($name);
        }catch(\Exception $e){
            $msg = "Could not find schema with name: \"$name\"";
            throw new Exception\SchemaNotFoundException($msg);
        }

        $data = file_get_contents($file);

        try {

            return json_decode($data, true, 1024, \JSON_THROW_ON_ERROR);

        }catch(\Exception $e){

            $msg = "Could not decode schema file: \"$file\"";
            throw new Exception\SchemaDecodeException($msg);

        }

    }

    /**
     * Validate the item to be added to the collection
     *
     * @param $item
     * @return mixed
     *
     * @throws \Exception
     */
    public function validateItem($file): void
    {
        if(!is_string($file)){
            $msg = sprintf(
                'Item must be a string, "%s" was given',
                gettype($file)
            );
            throw new TypeMismatchException($msg);
        }

        if(!file_exists($file)){
            $msg = "Schema file \"$file\" not found!";
            throw new Exception\SchemaNotFoundException($msg);
        }

        if(!is_readable($file)){
            $msg = "Could not read schema file: \"$file\", permission denied";
            throw new Exception\SchemaUnreadableException($msg);
        }
    }
}