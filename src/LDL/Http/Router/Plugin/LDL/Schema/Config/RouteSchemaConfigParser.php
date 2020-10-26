<?php declare(strict_types=1);

namespace LDL\Http\Router\Plugin\LDL\Schema\Config;

use LDL\Http\Router\Plugin\LDL\Schema\Config\Exception\Handler\InvalidRequestSchemaExceptionHandler;
use LDL\Http\Router\Plugin\LDL\Schema\Config\Exception\Handler\InvalidResponseSchemaExceptionHandler;
use LDL\Http\Router\Plugin\LDL\Schema\Dispatcher\PostDispatch;
use LDL\Http\Router\Plugin\LDL\Schema\Dispatcher\PreDispatch;
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

    public function __construct(
        SchemaRepositoryInterface $schemaRepo
    )
    {
        $this->schemaRepo = $schemaRepo;
    }

    public function parse(
        RouteInterface $route
    ): void
    {
        $routePreDispatchers = $route->getPreDispatchChain()->filterByClass(PreDispatch::class);
        $routePostDispatchers = $route->getPostDispatchChain()->filterByClass(PostDispatch::class);

        $hasPreDispatchers = count($routePreDispatchers) > 0;
        $hasPostDispatchers = count($routePostDispatchers) > 0;

        $this->config = $route->getConfig()->getRawConfig();

        $route->getExceptionHandlers()->append(new InvalidRequestSchemaExceptionHandler())
            ->append(new InvalidResponseSchemaExceptionHandler());

        $routeSchemaConfig = new RouteSchemaConfig(
            $this->getParameters(),
            $this->getUrlParameters(),
            $this->getHeadersSchema(),
            $this->getBodySchema()
        );

        if(false === $hasPreDispatchers){
            $preDispatch = new PreDispatch();
            $preDispatch->init(
                $routeSchemaConfig,
                $this->getRequestActive(),
                $this->getRequestPriority()
            );

            $route->getRouter()->getPreDispatchChain()->append($preDispatch);
        }

        if((false === $hasPostDispatchers) && (bool) $this->getResponseActive()) {
            $postDispatch = new PostDispatch();
            $postDispatch->init(
                $this->getResponsePriority(),
                $this->getResponseHeaderSchema(),
                $this->getResponseContentSchema()
            );

            $route->getRouter()->getPostDispatchChain()->append($postDispatch);
        }

        /**
         * @var PreDispatch $preDispatch
         */
        foreach($routePreDispatchers as $preDispatch){
            $preDispatch->init(
                $routeSchemaConfig,
                $this->getRequestActive(),
                $this->getRequestPriority()
            );
        }

        /**
         * @var PostDispatch $postDispatch
         */
        foreach($routePostDispatchers as $postDispatch){
            $postDispatch->init(
                $this->getResponsePriority(),
                $this->getResponseHeaderSchema(),
                $this->getResponseContentSchema()
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