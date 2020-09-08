<?php declare(strict_types=1);

namespace LDL\Http\Router\Plugin\LDL\Schema\Model;

use Swaggest\JsonSchema\SchemaContract;

interface SchemaResponseModelInterface
{
    /**
     * @param array $array
     * @return SchemaResponseModelInterface
     */
    public static function fromArray(array $array): SchemaResponseModelInterface;

    /**
     * @return int
     */
    public function getCode(): int;

    /**
     * @return SchemaContract
     */
    public function getSchema(): SchemaContract;
}