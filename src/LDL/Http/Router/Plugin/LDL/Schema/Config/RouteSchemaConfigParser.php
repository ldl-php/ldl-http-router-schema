<?php declare(strict_types=1);

namespace LDL\Http\Router\Plugin\LDL\Schema\Config;

use LDL\Http\Router\Plugin\LDL\Schema\Dispatcher\PostDispatch;
use LDL\Http\Router\Plugin\LDL\Schema\Dispatcher\PreDispatch;
use LDL\Http\Router\Plugin\LDL\Schema\Helper\SchemaParserHelper;
use LDL\Http\Router\Route\Config\Parser\RouteConfigParserInterface;
use LDL\Http\Router\Route\Route;
use LDL\Http\Router\Plugin\LDL\Schema\Repository\SchemaRepositoryInterface;
use Psr\Container\ContainerInterface;

use Swaggest\JsonSchema\Schema;
use Swaggest\JsonSchema\SchemaContract;

class RouteSchemaConfigParser implements RouteConfigParserInterface
{
    private const SCHEMA_REQUEST = 'request';
    private const SCHEMA_RESPONSE = 'response';
    private const SCHEMA_SCHEMA = 'schema';
    private const SCHEMA_PARAMETERS = 'parameters';
    private const SCHEMA_URL = 'url';
    private const SCHEMA_HEADERS = 'headers';
    private const SCHEMA_CONTENT = 'content';
    private const SCHEMA_BODY = 'body';
    private const SCHEMA_ACTIVE = 'active';
    private const SCHEMA_PRIORITY = 'priority';

    /**
     * @var array
     */
    private $data;

    /**
     * @var Route
     */
    private $route;

    /**
     * @var string
     */
    private $file;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var SchemaRepositoryInterface
     */
    private $schemaRepo;

    public function __construct(
        SchemaRepositoryInterface $schemaRepo
    )
    {
        $this->schemaRepo = $schemaRepo;
    }

    public function parse(
        array $data,
        Route $route,
        ContainerInterface $container = null,
        string $file = null
    ): void
    {
        $this->data = $data;
        $this->container = $container;
        $this->route = $route;
        $this->file = $file;

        /**
         * Append the pre dispatcher middleware to the route
         */
        $route->getConfig()->getPreDispatchMiddleware()->append(
            new PreDispatch(
                new RouteSchemaConfig(
                    $this->getParameters(),
                    $this->getUrlParameters(),
                    $this->getHeadersSchema(),
                    $this->getBodySchema()
                ),
                $this->getRequestActive(),
                $this->getRequestPriority()
            )
        );

        $route->getConfig()->getPostDispatchMiddleware()->append(
            new PostDispatch(
                $this->getResponseActive(),
                $this->getResponsePriority(),
                $this->getResponseHeaderSchema(),
                $this->getResponseContentSchema()
            )
        );

    }

    private function getHeadersSchema() : ?SchemaContract
    {
        $keys = [
            self::SCHEMA_REQUEST,
            self::SCHEMA_HEADERS,
            self::SCHEMA_SCHEMA
        ];

        if(false === SchemaParserHelper::routeHasSchema($this->data, $keys)){
            return null;
        }

        return $this->getSchema(
            $this->data[self::SCHEMA_REQUEST][self::SCHEMA_HEADERS][self::SCHEMA_SCHEMA],
            self::SCHEMA_HEADERS
        );
    }

    private function getUrlParameters(): ?SchemaContract
    {
        $keys = [
            self::SCHEMA_URL,
            self::SCHEMA_PARAMETERS,
            self::SCHEMA_SCHEMA
        ];

        if(false === SchemaParserHelper::routeHasSchema($this->data, $keys)){
            return null;
        }

        return $this->getSchema(
            $this->data[self::SCHEMA_URL][self::SCHEMA_PARAMETERS][self::SCHEMA_SCHEMA],
            self::SCHEMA_PARAMETERS,
        );
    }

    private function getParameters() : ?SchemaContract
    {
        $keys = [
            self::SCHEMA_REQUEST,
            self::SCHEMA_PARAMETERS,
            self::SCHEMA_SCHEMA
        ];

        if(false === SchemaParserHelper::routeHasSchema($this->data, $keys)){
            return null;
        }

        return $this->getSchema(
            $this->data[self::SCHEMA_REQUEST][self::SCHEMA_PARAMETERS][self::SCHEMA_SCHEMA],
            self::SCHEMA_PARAMETERS
        );
    }

    private function getResponseContentSchema() : ?ResponseSchemaCollection
    {
        $keys = [
            self::SCHEMA_RESPONSE,
            self::SCHEMA_CONTENT,
            self::SCHEMA_SCHEMA
        ];

        if(false === SchemaParserHelper::routeHasSchema($this->data, $keys)){
            return null;
        }

        $schema = $this->data[self::SCHEMA_RESPONSE][self::SCHEMA_CONTENT][self::SCHEMA_SCHEMA];

        if(!is_array($schema)) {
            $msg = 'Response content schema must be an array';
            throw new Exception\SchemaSectionError($this->exceptionMessage([$msg]));
        }

        $responseSchema = new ResponseSchemaCollection();

        foreach($schema as $httpStatusCode => $value){
            $responseSchema->append(
                $this->getSchema($value, 'response content schema'),
                $httpStatusCode
            );
        }

        return count($responseSchema) > 0 ? $responseSchema : null;
    }

    private function getResponseHeaderSchema() : ?ResponseSchemaCollection
    {
        $keys = [
            self::SCHEMA_RESPONSE,
            self::SCHEMA_HEADERS,
            self::SCHEMA_SCHEMA
        ];

        if(false === SchemaParserHelper::routeHasSchema($this->data, $keys)){
            return null;
        }

        $schema = $this->data[self::SCHEMA_RESPONSE][self::SCHEMA_HEADERS][self::SCHEMA_SCHEMA];

        if(!is_array($schema)) {
            $msg = 'Response headers schema must be an array';
            throw new Exception\SchemaSectionError($this->exceptionMessage([$msg]));
        }

        $responseSchema = new ResponseSchemaCollection();

        foreach($schema as $httpStatusCode => $value){
            $responseSchema->append(
                $this->getSchema($value, 'response headers schema'),
                $httpStatusCode
            );
        }

        return count($responseSchema) > 0 ? $responseSchema : null;
    }

    private function getBodySchema(): ?SchemaContract
    {
        $keys = [
            self::SCHEMA_REQUEST,
            self::SCHEMA_BODY,
            self::SCHEMA_SCHEMA
        ];

        if(false === SchemaParserHelper::routeHasSchema($this->data, $keys)){
            return null;
        }

        return $this->getSchema(
            $this->data[self::SCHEMA_REQUEST][self::SCHEMA_BODY][self::SCHEMA_SCHEMA],
            self::SCHEMA_BODY
        );
    }

    private function getRequestActive() : ?bool
    {
        $keys = [
            self::SCHEMA_REQUEST,
            self::SCHEMA_ACTIVE
        ];

        if(false === SchemaParserHelper::routeHasSchema($this->data, $keys)){
            return null;
        }

        return (bool) $this->data[self::SCHEMA_REQUEST][self::SCHEMA_ACTIVE];
    }

    private function getRequestPriority() : ?int
    {
        $keys = [
            self::SCHEMA_REQUEST,
            self::SCHEMA_PRIORITY
        ];

        if(false === SchemaParserHelper::routeHasSchema($this->data, $keys)){
            return null;
        }

        return (int) $this->data[self::SCHEMA_REQUEST][self::SCHEMA_PRIORITY];
    }

    private function getResponseActive() : ?bool
    {
        $keys = [
            self::SCHEMA_RESPONSE,
            self::SCHEMA_ACTIVE
        ];

        if(false === SchemaParserHelper::routeHasSchema($this->data, $keys)){
            return null;
        }

        return (bool) $this->data[self::SCHEMA_RESPONSE][self::SCHEMA_ACTIVE];
    }

    private function getResponsePriority() : ?int
    {
        $keys = [
            self::SCHEMA_RESPONSE,
            self::SCHEMA_PRIORITY
        ];

        if(false === SchemaParserHelper::routeHasSchema($this->data, $keys)){
            return null;
        }

        return (int) $this->data[self::SCHEMA_RESPONSE][self::SCHEMA_PRIORITY];
    }

    private function getSchema(
        $schema,
        string $section
    ): ?SchemaContract
    {
        if (!is_array($schema)) {
            $msg = "No schema specification, in section: \"$section\", must specify repository or inline";
            throw new Exception\SchemaSectionError($this->exceptionMessage([$msg]));
        }

        $type = strtolower(key($schema));

        switch ($type) {
            case 'repository':
                if (false === $this->schemaRepo->offsetExists($schema['repository'])) {
                    $msg = "Schema repository specified but no repository was given, in section: $section";
                    throw new Exception\SchemaSectionError($this->exceptionMessage([$msg]));
                }

                $schemaData = $this->schemaRepo->getSchema($schema['repository']);
                break;

            case 'inline':
                $schemaData = $schema['inline'];
                break;

            default:
                $msg = "Bad schema specification: \"$type\", in section: \"$section\", must specify repository or inline";
                throw new Exception\SchemaSectionError($this->exceptionMessage([$msg]));
                break;
        }

        try {
            return Schema::import(
                json_decode(
                    json_encode(
                        $schemaData,
                        \JSON_THROW_ON_ERROR
                    ),
                    false,
                    2048,
                    \JSON_THROW_ON_ERROR
                )
            );
        } catch (\Exception $e) {
            throw new Exception\SchemaSectionError(
                $this->exceptionMessage([$e->getMessage(), "In section: \"$section\""])
            );
        }
    }

    private function exceptionMessage(array $messages): string
    {
        if (null === $this->file) {
            return sprintf('%s', implode(', ', $messages));
        }

        return sprintf(
            'In file: "%s",%s',
            $this->file,
            implode(', ', $messages)
        );
    }
}