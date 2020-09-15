<?php declare(strict_types=1);

namespace LDL\Http\Router\Plugin\LDL\Schema\Dispatcher;

use LDL\Http\Core\Request\RequestInterface;
use LDL\Http\Core\Response\ResponseInterface;
use LDL\Http\Router\Middleware\PreDispatchMiddlewareInterface;
use LDL\Http\Router\Plugin\LDL\Schema\Config\RouteSchemaConfig;
use LDL\Http\Router\Plugin\LDL\Schema\Validator\RequestResponseSchemaValidator;
use LDL\Http\Router\Route\Route;

class PreDispatch implements PreDispatchMiddlewareInterface
{
    private const NAMESPACE = 'LDLPlugin';
    private const NAME = 'SchemaValidator';
    private const DEFAULT_IS_ACTIVE = true;
    private const DEFAULT_PRIORITY = 1;

    /**
     * @var bool
     */
    private $isActive;

    /**
     * @var int
     */
    private $priority;

    /**
     * @var RouteSchemaConfig
     */
    private $config;

    public function __construct(
        RouteSchemaConfig $config,
        bool $isActive = null,
        int $priority = null
    )
    {
        $this->isActive = $isActive ?? self::DEFAULT_IS_ACTIVE;
        $this->priority = $priority ?? self::DEFAULT_PRIORITY;
        $this->config = $config;
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
        array $urlArguments = []
    ) :?string
    {
        $validator = new RequestResponseSchemaValidator(
            $route,
            $this->config,
            $request,
            $response,
            $urlArguments
        );

        $validator->validate();

        return $validator->getError();
    }
}