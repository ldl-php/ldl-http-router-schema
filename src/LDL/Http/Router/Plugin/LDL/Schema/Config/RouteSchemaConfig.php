<?php declare(strict_types=1);

namespace LDL\Http\Router\Plugin\LDL\Schema\Config;

use LDL\Http\Router\Plugin\LDL\Schema\Parameter\ParameterCollection;
use Swaggest\JsonSchema\SchemaContract;

class RouteSchemaConfig
{
    /**
     * @var ParameterCollection
     */
    private $requestParameters;

    /**
     * @var ParameterCollection
     */
    private $urlParameters;

    /**
     * @var SchemaContract
     */
    private $bodySchema;

    /**
     * @var SchemaContract
     */
    private $headerSchema;

    public function __construct(
        SchemaContract $requestParameters=null,
        SchemaContract $urlParameters = null,
        SchemaContract $headerSchema = null,
        SchemaContract $bodySchema = null
    )
    {
        $this->requestParameters = $requestParameters;
        $this->urlParameters = $urlParameters;
        $this->headerSchema = $headerSchema;
        $this->bodySchema = $bodySchema;
    }

    public function getRequestParameters() : ?SchemaContract
    {
        return $this->requestParameters;
    }

    public function getUrlParameters() : ?SchemaContract
    {
        return $this->urlParameters;
    }

    public function getHeaderSchema() : ?SchemaContract
    {
        return $this->headerSchema;
    }

    public function getBodySchema() : ?SchemaContract
    {
        return $this->bodySchema;
    }

}