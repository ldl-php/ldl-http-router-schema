<?php

namespace LDL\Http\Router\Plugin\LDL\Schema\Repository;

use LDL\FS\File\Collection\Validator\ReadableFileValidator;
use LDL\Type\Collection\AbstractCollection;
use LDL\Type\Collection\Interfaces;
use LDL\Type\Collection\Traits\Validator\KeyValidatorChainTrait;
use LDL\Type\Collection\Traits\Validator\ValueValidatorChainTrait;
use LDL\Type\Collection\Validator\UniqueKeyValidator;

class SchemaRepository extends AbstractCollection implements SchemaRepositoryInterface
{
    use ValueValidatorChainTrait;
    use KeyValidatorChainTrait;

    public function __construct(iterable $items = null)
    {
        parent::__construct($items);

        $this->getValidatorChain()
            ->append(new ReadableFileValidator())
            ->lock();

        $this->getKeyValidatorChain()
            ->append(new UniqueKeyValidator())
            ->lock();
    }

    public function append($item, $key = null): Interfaces\CollectionInterface
    {
        $key = $key ?? basename($item);
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
}