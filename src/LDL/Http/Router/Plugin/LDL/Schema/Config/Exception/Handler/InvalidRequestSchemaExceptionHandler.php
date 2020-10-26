<?php declare(strict_types=1);

namespace LDL\Http\Router\Plugin\LDL\Schema\Config\Exception\Handler;

use LDL\Framework\Base\Traits\IsActiveInterfaceTrait;
use LDL\Http\Core\Response\ResponseInterface;
use LDL\Http\Router\Handler\Exception\AbstractExceptionHandler;
use LDL\Http\Router\Plugin\LDL\Schema\Validator\Exception\InvalidRequestSchemaException;
use LDL\Http\Router\Router;
use Symfony\Component\HttpFoundation\ParameterBag;

class InvalidRequestSchemaExceptionHandler extends AbstractExceptionHandler
{
    private const NAME = 'ldl.invalid.request.schema.exception.handler';
    private const DEFAULT_IS_ACTIVE = true;
    public const DEFAULT_PRIORITY = 1;

    use IsActiveInterfaceTrait;

    public function __construct(bool $isActive = null, int $priority = self::DEFAULT_PRIORITY)
    {
        parent::__construct(self::NAME, $priority);
        $this->_tActive = $isActive ?? self::DEFAULT_IS_ACTIVE;
    }

    public function handle(
        Router $router,
        \Exception $e,
        ParameterBag $urlParameters=null
    ): ?int
    {
        return $e instanceof InvalidRequestSchemaException ? ResponseInterface::HTTP_CODE_BAD_REQUEST : null;
    }
}