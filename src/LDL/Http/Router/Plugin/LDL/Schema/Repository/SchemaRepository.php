<?php

namespace LDL\Http\Router\Plugin\LDL\Schema\Repository;

use LDL\Type\Collection\AbstractCollection;
use LDL\Type\Collection\Traits\Validator\ValueValidatorChainTrait;
use LDL\Type\Collection\Validator\File\ReadableFileValidator;
use LDL\Type\Collection\Interfaces\Validation\HasValidatorChainInterface;

class SchemaRepository extends AbstractCollection implements HasValidatorChainInterface, SchemaRepositoryInterface
{
    use ValueValidatorChainTrait;

    public function __construct(iterable $items = null)
    {
        parent::__construct($items);
        $this->getValidatorChain()
            ->append(new ReadableFileValidator());
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