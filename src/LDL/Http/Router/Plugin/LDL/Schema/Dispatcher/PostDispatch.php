<?php declare(strict_types=1);

namespace LDL\Http\Router\Plugin\LDL\Schema\Dispatcher;

use LDL\Http\Core\Request\RequestInterface;
use LDL\Http\Core\Response\ResponseInterface;
use LDL\Http\Router\Middleware\PostDispatchMiddlewareInterface;
use LDL\Http\Router\Plugin\LDL\Schema\Config\ResponseSchemaCollection;
use LDL\Http\Router\Route\Route;
use Swaggest\JsonSchema\Context;
use Swaggest\JsonSchema\SchemaContract;

class PostDispatch implements PostDispatchMiddlewareInterface
{
    private const NAMESPACE = 'LDLPlugin';
    private const NAME = 'SchemaValidator';
    private const DEFAULT_IS_ACTIVE = true;
    private const DEFAULT_PRIORITY = 9999;

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
        bool $isActive = null,
        int $priority = null,
        ResponseSchemaCollection $headers=null,
        ResponseSchemaCollection $content=null
    )
    {
        $this->isActive = $isActive ?? self::DEFAULT_IS_ACTIVE;
        $this->priority = $priority ?? self::DEFAULT_PRIORITY;
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

        $validateHeaders = $this->validateHeaders($response);

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
            $schema = $this->content->offsetGet($response->getStatusCode());
        }catch(\Exception $e){
            return null;
        }

        try{
            $context = new Context();
            $context->tolerateStrings = true;

            $data = json_decode(json_encode($data));

            $schema->in(
                $data,
                $context
            );
            return null;
        }catch(\Exception $e){
            $response->setStatusCode(ResponseInterface::HTTP_CODE_BAD_REQUEST);
            return sprintf('In "%s" section, "%s"', 'response content', $e->getMessage());
        }
    }

    private function validateHeaders(ResponseInterface $response) : ?string
    {
        if(null === $this->headers){
            return null;
        }

        try{
            /**
             * @var SchemaContract $schema
             */
            $schema = $this->headers->offsetGet($response->getStatusCode());
        }catch(\Exception $e){
            return null;
        }

        try{
            $context = new Context();
            $context->tolerateStrings = true;

            $headers = new \stdClass();

            foreach($response->getHeaderBag()->getIterator() as $name => $value){
                $headers->$name = is_array($value) ? $value[0] : $value;
            }

            $schema->in(
                $headers,
                $context
            );
            return null;
        }catch(\Exception $e){
            $response->setStatusCode(ResponseInterface::HTTP_CODE_BAD_REQUEST);
            return sprintf('In "%s" section, "%s"', 'response headers', $e->getMessage());
        }
    }
}