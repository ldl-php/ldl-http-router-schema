<?php declare(strict_types=1);

namespace LDL\Http\Router\Plugin\LDL\Schema\Config;

use LDL\Http\Router\Plugin\LDL\Schema\Dispatcher\PostDispatch;
use LDL\Http\Router\Plugin\LDL\Schema\Dispatcher\PreDispatch;
use LDL\Http\Router\Plugin\LDL\Schema\Model\SchemaResponseCollection;
use LDL\Http\Router\Plugin\LDL\Schema\Model\SchemaResponseCollectionInterface;
use LDL\Http\Router\Plugin\LDL\Schema\Model\SchemaResponseModel;
use LDL\Http\Router\Plugin\LDL\Schema\Model\SchemaResponseModelInterface;
use LDL\Http\Router\Plugin\LDL\Schema\Repository\SchemaRepository;
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

        $routeSchemaConfig = new RouteSchemaConfig(
            $this->getParameters(),
            $this->getUrlParameters(),
            $this->getRequestHeadersSchema(),
            $this->getBodySchema(),
            $this->getResponseSchemaHeader(),
            $this->getResponseSchemaContent()
        );

        /**
         * Append the pre dispatcher middleware to the route
         */
        $route->getConfig()->getPreDispatchMiddleware()->append(
            new PreDispatch(true,1, $routeSchemaConfig)
        );

        /**
         * Append the post dispatcher middleware to the route
         */
        $route->getConfig()->getPostDispatchMiddleware()->append(
            new PostDispatch(true, 1, $routeSchemaConfig)
        );
    }

    private function getRequestHeadersSchema() : ?SchemaContract
    {
        if (false === array_key_exists('headers', $this->data['request'])) {
            return null;
        }

        if (false === array_key_exists('schema', $this->data['request']['headers'])) {
            return null;
        }

        return $this->getSchema(
            $this->data['request']['headers']['schema'],
            'request headers'
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

    private function getResponseSchemaHeader(): ? SchemaResponseCollectionInterface
    {
        if (false === array_key_exists('headers', $this->data['response'])) {
            return null;
        }

        $schemaResponseCollection = new SchemaResponseCollection();

        foreach($this->data['response']['headers'] as $code => $config){
            $schemaResponseCollection->append(SchemaResponseModel::fromArray([
                'code' => (int) $code,
                'schema' => $this->getSchema($config['schema'],'response headers')
            ]));
        }

        return $schemaResponseCollection;
    }

    private function getResponseSchemaContent(): ? SchemaResponseCollectionInterface
    {
        if (false === array_key_exists('content', $this->data['response'])) {
            return null;
        }

        $schemaResponseCollection = new SchemaResponseCollection();

        foreach($this->data['response']['content'] as $code => $config){
            $schemaResponseCollection->append(SchemaResponseModel::fromArray([
                'code' => (int) $code,
                'schema' => $this->getSchema($config['schema'],'response content')
            ]));
        }

        return $schemaResponseCollection;
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