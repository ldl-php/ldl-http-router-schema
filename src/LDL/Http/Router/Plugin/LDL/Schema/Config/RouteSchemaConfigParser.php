<?php declare(strict_types=1);

namespace LDL\Http\Router\Plugin\LDL\Schema\Config;

use LDL\Http\Router\Plugin\LDL\Schema\Dispatcher\PostDispatch;
use LDL\Http\Router\Plugin\LDL\Schema\Dispatcher\PreDispatch;
use LDL\Http\Router\Route\Config\Parser\RouteConfigParserInterface;
use LDL\Http\Router\Route\Route;
use LDL\Http\Router\Plugin\LDL\Schema\Repository\SchemaRepositoryInterface;
use Psr\Container\ContainerInterface;

use Swaggest\JsonSchema\Schema;
use Swaggest\JsonSchema\SchemaContract;

class RouteSchemaConfigParser implements RouteConfigParserInterface
{
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
                true,
                1,
                new RouteSchemaConfig(
                    $this->getParameters(),
                    $this->getUrlParameters(),
                    $this->getHeadersSchema(),
                    $this->getBodySchema()
                )
            )
        );

        $route->getConfig()->getPostDispatchMiddleware()->append(
            new PostDispatch(
                true,
                1,
                $this->getResponseHeaderSchema(),
                $this->getResponseContentSchema()
            )
        );

    }

    private function getHeadersSchema() : ?SchemaContract
    {
        if (false === array_key_exists('headers', $this->data['request'])) {
            return null;
        }

        if (false === array_key_exists('schema', $this->data['request']['headers'])) {
            return null;
        }

        return $this->getSchema(
            $this->data['request']['headers']['schema'],
            'headers'
        );
    }

    private function getUrlParameters(): ?SchemaContract
    {
        if (false === array_key_exists('parameters', $this->data['url'])) {
            return null;
        }

        if (false === array_key_exists('schema', $this->data['url']['parameters'])) {
            return null;
        }

        return $this->getSchema(
            $this->data['url']['parameters']['schema'],
            'parameters',
        );
    }

    private function getParameters() : ?SchemaContract
    {
        if (false === array_key_exists('parameters', $this->data['request'])) {
            return null;
        }

        if (false === array_key_exists('schema', $this->data['request']['parameters'])) {
            return null;
        }

        return $this->getSchema(
            $this->data['request']['parameters']['schema'],
            'parameters'
        );
    }

    private function getResponseContentSchema() : ?ResponseSchemaCollection
    {
        if(!array_key_exists('response', $this->data)){
            return null;
        }

        if(!array_key_exists('content', $this->data['response'])){
            return null;
        }

        if(!array_key_exists('schema', $this->data['response']['content'])){
            return null;
        }

        $schema = $this->data['response']['content']['schema'];

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
        if(!array_key_exists('response', $this->data)){
            return null;
        }

        if(!array_key_exists('header', $this->data['response'])){
            return null;
        }

        if(!array_key_exists('schema', $this->data['response']['header'])){
            return null;
        }

        $schema = $this->data['response']['header']['schema'];

        if(!is_array($schema)) {
            $msg = 'Response header schema must be an array';
            throw new Exception\SchemaSectionError($this->exceptionMessage([$msg]));
        }

        $responseSchema = new ResponseSchemaCollection();

        foreach($schema as $httpStatusCode => $value){
            $responseSchema->append(
                $this->getSchema($value, 'response header schema'),
                $httpStatusCode
            );
        }

        return count($responseSchema) > 0 ? $responseSchema : null;
    }

    private function getBodySchema(): ?SchemaContract
    {
        if (!array_key_exists('body', $this->data['request'])) {
            return null;
        }

        if (!array_key_exists('schema', $this->data['request']['body'])) {
            return null;
        }

        return $this->getSchema($this->data['request']['body']['schema'], 'body');
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
                if (null === $this->schemaRepo) {
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