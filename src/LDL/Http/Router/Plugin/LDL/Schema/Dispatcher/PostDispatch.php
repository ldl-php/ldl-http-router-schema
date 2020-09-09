<?php declare(strict_types=1);

namespace LDL\Http\Router\Plugin\LDL\Schema\Dispatcher;

use LDL\Http\Core\Request\RequestInterface;
use LDL\Http\Core\Response\ResponseInterface;
use LDL\Http\Router\Plugin\LDL\Schema\Config\ResponseSchemaCollection;
use LDL\Http\Router\Plugin\LDL\Schema\Config\RouteSchemaConfig;
use LDL\Http\Router\Plugin\LDL\Schema\Validator\RequestResponseSchemaValidator;
use LDL\Http\Router\Route\Middleware\PostDispatchMiddlewareInterface;
use LDL\Http\Router\Route\Route;
use Swaggest\JsonSchema\Context;
use Swaggest\JsonSchema\SchemaContract;

class PostDispatch implements PostDispatchMiddlewareInterface
{
    private const NAMESPACE = 'LDLPlugin';
    private const NAME = 'SchemaValidator';

    /**
     * @var bool
     */
    private $isActive;

    /**
     * @var int
     */
    private $priority;

    /**
     * @var ResponseSchemaCollection
     */
    private $headers;

    /**
     * @var ResponseSchemaCollection
     */
    private $content;

    public function __construct(
        bool $isActive,
        int $priority,
        ResponseSchemaCollection $headers=null,
        ResponseSchemaCollection $content=null
    )
    {
        $this->isActive = $isActive;
        $this->priority = $priority;
        $this->headers = $headers;
        $this->content = $content;
    }

    public function getNamespace(): string
    {
        return self::NAMESPACE;
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function dispatch(
        Route $route,
        RequestInterface $request,
        ResponseInterface $response,
        array $result = []
    ) :?string
    {
        $validateContent = $this->validateContent($result, $response);

        if(null !== $validateContent){
            return $validateContent;
        }

        $validateHeaders = $this->validateHeaders($result, $response);

        if(null !== $validateHeaders){
            return $validateHeaders;
        }

        return null;
    }

    private function validateContent(array $data, ResponseInterface $response) : ?string
    {
        if(null === $this->content){
            return null;
        }

        try{
            /**
             * @var SchemaContract $schema
             */
            $schema = $this->content[$response->getStatusCode()];
        }catch(\Exception $e){
            return null;
        }

        try{
            $context = new Context();
            $context->tolerateStrings = true;

            $schema->in(
                (object) $data,
                $context
            );
            return null;
        }catch(\Exception $e){
            $response->setStatusCode(ResponseInterface::HTTP_CODE_BAD_REQUEST);
            return sprintf('In "%s" section, "%s"', 'request content', $e->getMessage());
        }
    }

    private function validateHeaders(array $data, ResponseInterface $response) : ?string
    {
        if(null === $this->headers){
            return null;
        }

        try{
            /**
             * @var SchemaContract $schema
             */
            $schema = $this->headers[$response->getStatusCode()];
        }catch(\Exception $e){
            return null;
        }

        try{
            $context = new Context();
            $context->tolerateStrings = true;

            $schema->in(
                (object) $data,
                $context
            );
        }catch(\Exception $e){
            $response->setStatusCode(ResponseInterface::HTTP_CODE_BAD_REQUEST);
            return sprintf('In "%s" section, "%s"', 'request headers', $e->getMessage());
        }
    }
}