<?php declare(strict_types=1);

namespace LDL\Http\Router\Plugin\LDL\Schema\Dispatcher;

use LDL\Http\Core\Request\RequestInterface;
use LDL\Http\Core\Response\ResponseInterface;
use LDL\Http\Router\Middleware\AbstractMiddleware;
use LDL\Http\Router\Plugin\LDL\Schema\Config\RouteSchemaConfig;
use LDL\Http\Router\Plugin\LDL\Schema\Validator\Exception\InvalidRequestSchemaException;
use LDL\Http\Router\Plugin\LDL\Schema\Validator\RequestResponseSchemaValidator;
use LDL\Http\Router\Router;
use Symfony\Component\HttpFoundation\ParameterBag;

class PreDispatch extends AbstractMiddleware
{
    private const NAME = 'ldl.schema.validator.predispatch';
    private const DEFAULT_IS_ACTIVE = true;
    private const DEFAULT_PRIORITY = 1;

    /**
     * @var RouteSchemaConfig
     */
    private $config;

    public function __construct(?string $name = null)
    {
        parent::__construct($name ?? self::NAME);
    }

    public function init(
        RouteSchemaConfig $config,
        bool $isActive = null,
        int $priority = null
    ) : void
    {
        $this->setActive($isActive ?? self::DEFAULT_IS_ACTIVE);
        $this->setPriority($priority ?? self::DEFAULT_PRIORITY);
        $this->config = $config;
    }

    public function _dispatch(
        RequestInterface $request,
        ResponseInterface $response,
        Router $router,
        ParameterBag $parameterBag=null
    ) : ?array
    {
        $validator = new RequestResponseSchemaValidator(
            $router->getCurrentRoute(),
            $this->config,
            $request,
            $response,
            $parameterBag
        );

        $validator->validate();

        if($validator->getError()){
            throw new InvalidRequestSchemaException($validator->getError());
        }

        return null;
    }
}