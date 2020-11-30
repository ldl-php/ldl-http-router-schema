<?php declare(strict_types=1);

namespace LDL\Http\Router\Plugin\LDL\Schema\Config;

use LDL\Http\Router\Plugin\LDL\Schema\Config\Exception\Handler\InvalidRequestSchemaExceptionHandler;
use LDL\Http\Router\Plugin\LDL\Schema\Config\Exception\Handler\InvalidResponseSchemaExceptionHandler;
use LDL\Http\Router\Plugin\LDL\Schema\Dispatcher\SchemaResponsePostDispatch;
use LDL\Http\Router\Plugin\LDL\Schema\Dispatcher\SchemaRequestPreDispatch;
use LDL\Http\Router\Plugin\LDL\Schema\Helper\SchemaParserHelper;
use LDL\Http\Router\Route\Config\Helper\ResponseCodeHelper;
use LDL\Http\Router\Route\Config\Parser\RouteConfigParserInterface;
use LDL\Http\Router\Route\RouteInterface;
use LDL\Http\Router\Plugin\LDL\Schema\Repository\SchemaRepositoryInterface;

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
    private $config;

    /**
     * @var string
     */
    private $file;

    /**
     * @var SchemaRepositoryInterface
     */
    private $schemaRepo;

    public function __construct(SchemaRepositoryInterface $schemaRepo)
    {
        $this->schemaRepo = $schemaRepo;
    }

    public function parse(RouteInterface $route): void
    {
        /**
         * Detect if there's a schema config in place in the route, if there isn't any, this plugin must not be used
         */
        $this->config = $route->getConfig()->getRawConfig();

        $requestSchema = [
            'parameters' => $this->getParameters(),
            'urlParameters' => $this->getUrlParameters(),
            'headers' => $this->getHeadersSchema(),
            'body' => $this->getBodySchema()
        ];

        $responseSchema = [
            'headers' => $this->getResponseHeaderSchema(),
            'content' => $this->getResponseContentSchema()
        ];

        $hasRequestSchema = false;
        $hasResponseSchema = false;

        foreach($requestSchema as $part){
            if(null !== $part){
                $hasRequestSchema = true;
                break;
            }
        }

        foreach($responseSchema as $part){
            if(null !== $part){
                $hasResponseSchema = true;
                break;
            }
        }

        /**
         * No schema configuration was detected, return and exit
         */
        if(false === $hasRequestSchema && false === $hasResponseSchema){
            return;
        }

        $schemaPreDispatchers = $route->getPreDispatchChain()->filterByClassRecursive(SchemaRequestPreDispatch::class);
        $schemaPostDispatchers = $route->getPostDispatchChain()->filterByClassRecursive(SchemaResponsePostDispatch::class);

        $schemaPreDispatchCount = count($schemaPreDispatchers);
        $schemaPostDispatchCount = count($schemaPostDispatchers);

        if($schemaPreDispatchCount > 1){
            $msg = sprintf(
                'There can only be ONE schema pre dispatcher, "%s" were found',
                $schemaPreDispatchCount
            );

            throw new \LogicException($msg);
        }

        if($schemaPostDispatchCount > 1){
            $msg = sprintf(
                'There can only be ONE schema post dispatcher, "%s" were found',
                $schemaPreDispatchCount
            );

            throw new \LogicException($msg);
        }

        $preDispatch = $schemaPreDispatchers->getFirst();
        $postDispatch = $schemaPostDispatchers->getFirst();

        if($hasRequestSchema){
            if(0 === $schemaPreDispatchCount) {
                $preDispatch = new SchemaRequestPreDispatch();

                $route->getPreDispatchChain()
                    ->append($preDispatch);
            }

            if(
                0 === count($route->getExceptionHandlers()->filterByClass(InvalidRequestSchemaExceptionHandler::class)) &&
                0 === count($route->getRouter()->getExceptionHandlers()->filterByClass(InvalidRequestSchemaExceptionHandler::class))
            ){
                $route->getExceptionHandlers()
                    ->append(new InvalidResponseSchemaExceptionHandler());
            }

            /**
             * @var SchemaRequestPreDispatch $preDispatch
             */
            $preDispatch->init(
                new RouteSchemaConfig(
                    $requestSchema['parameters'],
                    $requestSchema['urlParameters'],
                    $requestSchema['headers'],
                    $requestSchema['body']
                ),
                $this->getRequestActive(),
                $this->getRequestPriority()
            );

        }

        if($hasResponseSchema){
            /**
             * No schema post dispatchers were found, auto append required classes
             */
            if(0 === $schemaPostDispatchCount) {
                $postDispatch = new SchemaResponsePostDispatch();

                $route->getPostDispatchChain()
                    ->append($postDispatch);

            }

            if(
                0 === count($route->getExceptionHandlers()->filterByClass(InvalidResponseSchemaExceptionHandler::class)) &&
                0 === count($route->getRouter()->getExceptionHandlers()->filterByClass(InvalidResponseSchemaExceptionHandler::class))
            ){
                $route->getExceptionHandlers()
                    ->append(new InvalidResponseSchemaExceptionHandler());
            }
            /**
             * @var SchemaResponsePostDispatch $postDispatch
             */
            $postDispatch->init(
                $this->getResponsePriority(),
                $responseSchema['headers'],
                $responseSchema['content']
            );

        }

    }

    private function getHeadersSchema() : ?SchemaContract
    {
        $keys = [
            self::SCHEMA_REQUEST,
            self::SCHEMA_HEADERS,
            self::SCHEMA_SCHEMA
        ];

        if(false === SchemaParserHelper::routeHasSchema($this->config, $keys)){
            return null;
        }

        return $this->getSchema(
            $this->config[self::SCHEMA_REQUEST][self::SCHEMA_HEADERS][self::SCHEMA_SCHEMA],
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

        if(false === SchemaParserHelper::routeHasSchema($this->config, $keys)){
            return null;
        }

        return $this->getSchema(
            $this->config[self::SCHEMA_URL][self::SCHEMA_PARAMETERS][self::SCHEMA_SCHEMA],
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

        if(false === SchemaParserHelper::routeHasSchema($this->config, $keys)){
            return null;
        }

        return $this->getSchema(
            $this->config[self::SCHEMA_REQUEST][self::SCHEMA_PARAMETERS][self::SCHEMA_SCHEMA],
            self::SCHEMA_PARAMETERS
        );
    }

    private function getResponseContentSchema() : ?ResponseSchemaCollection
    {
        $keys = [
            self::SCHEMA_RESPONSE,
            self::SCHEMA_SCHEMA,
            self::SCHEMA_CONTENT
        ];

        if(false === SchemaParserHelper::routeHasSchema($this->config, $keys)){
            return null;
        }

        $schema = $this->config[self::SCHEMA_RESPONSE][self::SCHEMA_SCHEMA][self::SCHEMA_CONTENT];

        if(!is_array($schema)) {
            $msg = 'Response content schema must be an array';
            throw new Exception\SchemaSectionError($this->exceptionMessage([$msg]));
        }

        $responseSchema = new ResponseSchemaCollection();
        $section = 'response -> content -> schema';

        foreach($schema as $responseCodes => $value){
            $schemaContract = $this->getSchema($value, $section);

            $codes = ResponseCodeHelper::generate(
                (string) $responseCodes,
                $section
            );

            foreach($codes as $code => $schemaValue){
                $responseSchema->append($schemaContract, $code);
            }
        }

        return count($responseSchema) > 0 ? $responseSchema : null;
    }

    private function getResponseHeaderSchema() : ?ResponseSchemaCollection
    {
        $keys = [
            self::SCHEMA_RESPONSE,
            self::SCHEMA_SCHEMA,
            self::SCHEMA_HEADERS
        ];

        if(false === SchemaParserHelper::routeHasSchema($this->config, $keys)){
            return null;
        }

        $schema = $this->config[self::SCHEMA_RESPONSE][self::SCHEMA_SCHEMA][self::SCHEMA_HEADERS];

        if(!is_array($schema)) {
            $msg = 'Response headers schema must be an array';
            throw new Exception\SchemaSectionError($this->exceptionMessage([$msg]));
        }

        $responseSchema = new ResponseSchemaCollection();
        $section = 'response -> headers -> schema';

        foreach($schema as $responseCodes => $value){
            $schemaContract = $this->getSchema($value, $section);

            $codes = ResponseCodeHelper::generate(
                (string) $responseCodes,
                $section
            );

            foreach($codes as $code => $schemaValue){
                $responseSchema->append($schemaContract, $code);
            }
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

        if(false === SchemaParserHelper::routeHasSchema($this->config, $keys)){
            return null;
        }

        return $this->getSchema(
            $this->config[self::SCHEMA_REQUEST][self::SCHEMA_BODY][self::SCHEMA_SCHEMA],
            self::SCHEMA_BODY
        );
    }

    private function getRequestActive() : ?bool
    {
        $keys = [
            self::SCHEMA_REQUEST,
            self::SCHEMA_ACTIVE
        ];

        if(false === SchemaParserHelper::routeHasSchema($this->config, $keys)){
            return null;
        }

        return (bool) $this->config[self::SCHEMA_REQUEST][self::SCHEMA_ACTIVE];
    }

    private function getRequestPriority() : ?int
    {
        $keys = [
            self::SCHEMA_REQUEST,
            self::SCHEMA_PRIORITY
        ];

        if(false === SchemaParserHelper::routeHasSchema($this->config, $keys)){
            return null;
        }

        return (int) $this->config[self::SCHEMA_REQUEST][self::SCHEMA_PRIORITY];
    }

    private function getResponseActive() : ?bool
    {
        $keys = [
            self::SCHEMA_RESPONSE,
            self::SCHEMA_SCHEMA,
            self::SCHEMA_ACTIVE
        ];

        if(false === SchemaParserHelper::routeHasSchema($this->config, $keys)){
            return null;
        }

        return (bool) $this->config[self::SCHEMA_RESPONSE][self::SCHEMA_SCHEMA][self::SCHEMA_ACTIVE];
    }

    private function getResponsePriority() : ?int
    {
        $keys = [
            self::SCHEMA_RESPONSE,
            self::SCHEMA_SCHEMA,
            self::SCHEMA_PRIORITY
        ];

        if(false === SchemaParserHelper::routeHasSchema($this->config, $keys)){
            return null;
        }

        return (int) $this->config[self::SCHEMA_RESPONSE][self::SCHEMA_SCHEMA][self::SCHEMA_PRIORITY];
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