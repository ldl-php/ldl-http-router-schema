<?php

namespace LDL\Http\Router\Plugin\LDL\Schema\Config;

use LDL\Type\Collection\Interfaces\Namespaceable\NamespaceableInterface;
use LDL\Type\Collection\Types\Object\ObjectCollection;
use LDL\Type\Collection\Types\Object\Validator\InterfaceComplianceItemValidator;
use LDL\Type\Collection\Traits\Namespaceable\NamespaceableTrait;
use Swaggest\JsonSchema\SchemaContract;

class ResponseSchemaCollection extends ObjectCollection implements NamespaceableInterface
{
    use NamespaceableTrait;

    public function __construct(iterable $items = null)
    {
        parent::__construct($items);

        $this->getValidatorChain()
            ->append(new InterfaceComplianceItemValidator(SchemaContract::class))
            ->lock();
    }
}