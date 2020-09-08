<?php declare(strict_types=1);

namespace LDL\Http\Router\Plugin\LDL\Schema\Config;

use LDL\Http\Router\Plugin\LDL\Schema\Model\SchemaResponseCollectionInterface;
use LDL\Http\Router\Plugin\LDL\Schema\Repository\SchemaRepositoryInterface;
use Swaggest\JsonSchema\SchemaContract;

class RouteSchemaConfig
{
    /**
     * @var SchemaContract
     */
    private $requestParameters;

    /**
     * @var SchemaContract
     */
    private $urlParameters;

    /**
     * @var SchemaContract
     */
    private $bodySchema;

    /**
     * @var SchemaContract
     */
    private $requestHeaderSchema;

    /**
     * @var SchemaResponseCollectionInterface
     */
    private $responseSchemaHeader;

    /**
     * @var SchemaResponseCollectionInterface
     */
    private $responseSchemaContent;

    public function __construct(
        SchemaContract $requestParameters = null,
        SchemaContract $urlParameters = null,
        SchemaContract $requestHeaderSchema = null,
        SchemaContract $bodySchema = null,
        SchemaResponseCollectionInterface $responseSchemaHeader = null,
        SchemaResponseCollectionInterface $responseSchemaContent = null
    )
    {
        $this->requestParameters = $requestParameters;
        $this->urlParameters = $urlParameters;
        $this->requestHeaderSchema = $requestHeaderSchema;
        $this->bodySchema = $bodySchema;
        $this->responseSchemaHeader = $responseSchemaHeader;
        $this->responseSchemaContent = $responseSchemaContent;
    }

    public function getRequestParameters() : ?SchemaContract
    {
        return $this->requestParameters;
    }

    public function getUrlParameters() : ?SchemaContract
    {
        return $this->urlParameters;
    }

    public function getRequestHeaderSchema() : ?SchemaContract
    {
        return $this->requestHeaderSchema;
    }

    public function getBodySchema() : ?SchemaContract
    {
        return $this->bodySchema;
    }

    public function getResponseSchemaHeader() : ?SchemaResponseCollectionInterface
    {
        return $this->responseSchemaHeader;
    }

    public function getResponseSchemaContent() : ?SchemaResponseCollectionInterface
    {
        return $this->responseSchemaContent;
    }
}