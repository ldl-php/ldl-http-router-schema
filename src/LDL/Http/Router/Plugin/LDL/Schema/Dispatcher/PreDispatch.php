<?php declare(strict_types=1);

namespace LDL\Http\Router\Plugin\LDL\Schema\Dispatcher;

use LDL\Http\Core\Request\RequestInterface;
use LDL\Http\Core\Response\ResponseInterface;
use LDL\Http\Router\Plugin\LDL\Schema\Config\RouteSchemaConfig;
use LDL\Http\Router\Plugin\LDL\Schema\Validator\RequestResponseSchemaValidator;
use LDL\Http\Router\Route\Middleware\MiddlewareInterface;
use LDL\Http\Router\Route\Route;

class PreDispatch implements MiddlewareInterface
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
     * @var RouteSchemaConfig
     */
    private $config;

    public function __construct(
        bool $isActive,
        int $priority,
        RouteSchemaConfig $config
    )
    {
        $this->isActive = $isActive;
        $this->priority = $priority;
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