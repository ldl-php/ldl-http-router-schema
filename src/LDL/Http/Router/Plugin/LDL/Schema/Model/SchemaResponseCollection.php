<?php declare(strict_types=1);

namespace LDL\Http\Router\Plugin\LDL\Schema\Model;

class SchemaResponseCollection extends \ArrayObject implements SchemaResponseCollectionInterface
{
    protected function add($model): self
    {
        $type = gettype($model);
        if ('object' !== $type) {
            throw new \InvalidArgumentException("Object expected, $type was given");
        }

        if (false === ($model instanceof SchemaResponseModelInterface)) {
            throw new \InvalidArgumentException('Value must be an instance of SchemaResponseModel');
        }

        $this->append($model);

        return $this;
    }
}