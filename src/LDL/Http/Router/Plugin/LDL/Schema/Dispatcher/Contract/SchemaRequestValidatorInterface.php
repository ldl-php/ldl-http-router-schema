<?php declare(strict_types=1);


namespace LDL\Http\Router\Plugin\LDL\Schema\Dispatcher\Contract;

interface SchemaRequestValidatorInterface extends \JsonSerializable
{

    public function getHttpStatusCode() : ?int;

    public function getData() : array;

}
