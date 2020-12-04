<?php declare(strict_types=1);


namespace LDL\Http\Router\Plugin\LDL\Schema\Dispatcher\Contract;

use LDL\Type\Collection\Types\Integer\IntegerCollection;

interface SchemaRequestValidatorInterface extends \JsonSerializable
{
    /**
     * Returns an array of schema file names (name only, without path)
     * this array of schema files must be a valid file appended to the
     * general SchemaRepository.
     *
     * @return array
     */
    public function getSchemas() : array;

    /**
     * Obtains to which HTTP status code the validation must take place
     *
     * @return int|null
     */
    public function getHttpStatusCode() : ?IntegerCollection;

    public function getData() : ?array;
}
