<?php declare(strict_types=1);

namespace LDL\Http\Router\Plugin\LDL\Schema\Dispatcher;

use LDL\Http\Core\Request\RequestInterface;
use LDL\Http\Core\Response\ResponseInterface;
use LDL\Http\Router\Middleware\AbstractMiddleware;
use LDL\Http\Router\Plugin\LDL\Schema\Config\ResponseSchemaCollection;
use LDL\Http\Router\Plugin\LDL\Schema\Validator\Exception\InvalidResponseSchemaException;
use LDL\Http\Router\Router;
use Swaggest\JsonSchema\Context;
use Swaggest\JsonSchema\SchemaContract;
use Symfony\Component\HttpFoundation\ParameterBag;

class SchemaResponsePostDispatch extends AbstractMiddleware
{
    private const NAME = 'ldl.schema.validator.response';
    private const DEFAULT_IS_ACTIVE = true;
    private const DEFAULT_PRIORITY = 9999;

    /**
     * @var ResponseSchemaCollection
     */
    private $headers;

    /**
     * @var ResponseSchemaCollection
     */
    private $content;

    public function __construct(?string $name = null)
    {
        parent::__construct($name ?? self::NAME);
    }

    public function init(
        int $priority = null,
        ResponseSchemaCollection $headers=null,
        ResponseSchemaCollection $content=null
    ) :void
    {
        $this->setActive(self::DEFAULT_IS_ACTIVE);
        $this->setPriority($priority ?? self::DEFAULT_PRIORITY);
        $this->headers = $headers;
        $this->content = $content;
    }

    public function _dispatch(
        RequestInterface $request,
        ResponseInterface $response,
        Router $router,
        ParameterBag $parameterBag=null
    ) :?array
    {
        $formatter = $router->getResponseFormatterRepository()->getSelectedItem();

        $formatter->format($router->getDispatcher()->getResult());

        $this->validateHeaders($response);
        $this->validateContent($response, $formatter->getResult());

        return null;
    }

    private function validateContent(ResponseInterface $response, array $result) : ?string
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

            $result = json_decode(json_encode($result, \JSON_THROW_ON_ERROR), $asArray = false, 2048, \JSON_THROW_ON_ERROR);

            $schema->in(
                $result,
                $context
            );

            return null;
        }catch(\Exception $e){
            $msg = sprintf('In "%s" section, "%s"', 'response content', $e->getMessage());
            throw new InvalidResponseSchemaException($msg);
        }
    }

    private function validateHeaders(ResponseInterface $response, ParameterBag $urlParameters=null) : ?string
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
            $msg = sprintf('In "%s" section, "%s"', 'response headers', $e->getMessage());
            throw new InvalidResponseSchemaException($msg);
        }
    }
}