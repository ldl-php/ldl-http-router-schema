<?php declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

use LDL\Http\Core\Request\Request;
use LDL\Http\Core\Request\RequestInterface;
use LDL\Http\Core\Response\Response;
use LDL\Http\Core\Response\ResponseInterface;
use LDL\Http\Router\Route\Config\Parser\RouteConfigParserCollection;
use LDL\Http\Router\Route\Dispatcher\RouteDispatcherInterface;
use LDL\Http\Router\Route\Factory\RouteFactory;
use LDL\Http\Router\Route\Group\RouteGroup;
use LDL\Http\Router\Router;

use LDL\Http\Router\Plugin\LDL\Schema\Repository\SchemaRepository;
use LDL\Http\Router\Plugin\LDL\Schema\Config\RouteSchemaConfigParser;

class Dispatcher implements RouteDispatcherInterface
{
    public function dispatch(
        RequestInterface $request,
        ResponseInterface $response
    )
    {
        return [
            'test'
        ];
    }
}

$schemaRepo = new SchemaRepository();

$schemaRepo->append(__DIR__.'/schema/header-schema.json', 'header-parameters.schema');
$schemaRepo->append(__DIR__.'/schema/parameter-schema.json', 'request-parameters.schema');
$schemaRepo->append(__DIR__.'/schema/url-parameters-schema.json', 'url-parameters.schema');

$parserCollection = new RouteConfigParserCollection();
$parserCollection->append(new RouteSchemaConfigParser($schemaRepo));

$routes = RouteFactory::fromJsonFile(
    __DIR__.'/routes.json',
    null,
    $parserCollection
);

$group = new RouteGroup('student', 'student', $routes);
$response = new Response();

$router = new Router(
    Request::createFromGlobals(),
    $response
);

$router->addGroup($group);

$router->dispatch()->send();
