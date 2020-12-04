<?php declare(strict_types=1);

namespace LDL\Http\Router\Plugin\LDL\Schema\Dispatcher;

class SchemaRequestValidator implements Contract\SchemaRequestValidatorInterface
{
    /**
     * @var array
     */
    private $repositories;

    /**
     * @var array
     */
    private $data;

    /**
     * @var int
     */
    private $httpStatusCode;

    public function __construct(
        array $repositories,
        array $data,
        int $httpStatusCode = null
    )
    {
        $this->repositories = $repositories;
        $this->data = $data;
        $this->httpStatusCode = $httpStatusCode;
    }

    public function getRepositories() : array
    {
        return $this->repositories;
    }

    public function getHttpStatusCode() : ?int
    {
        return $this->httpStatusCode;
    }

    public function getData() : ?array
    {
        return $this->data;
    }

    public function jsonSerialize()
    {
        return $this->data;
    }

}
