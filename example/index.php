<?php declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

use LDL\FS\File\Collection\Validator\Exception\FileValidatorException;
use LDL\Http\Core\Request\Request;
use LDL\Http\Core\Request\RequestInterface;
use LDL\Http\Core\Response\Response;
use LDL\Http\Core\Response\ResponseInterface;
use LDL\Http\Router\Middleware\DispatcherRepository;
use LDL\Http\Router\Response\Parser\Repository\ResponseParserRepository;
use LDL\Http\Router\Route\Config\Parser\RouteConfigParserRepository;
use LDL\Http\Router\Route\Factory\RouteFactory;
use LDL\Http\Router\Route\Group\RouteGroup;
use LDL\Http\Router\Router;
use LDL\Http\Router\Middleware\AbstractMiddleware;
use LDL\Http\Router\Plugin\LDL\Schema\Dispatcher\SchemaRequestPreDispatch;
use LDL\Http\Router\Plugin\LDL\Schema\Dispatcher\SchemaResponsePostDispatch;

use LDL\Http\Router\Plugin\LDL\Schema\Repository\SchemaRepository;
use LDL\Http\Router\Plugin\LDL\Schema\Config\RouteSchemaConfigParser;

use Symfony\Component\HttpFoundation\ParameterBag;

class Dispatcher extends AbstractMiddleware
{
    public function _dispatch(
        RequestInterface $request,
        ResponseInterface $response,
        Router $router,
        ParameterBag $urlParams = null
    ): ?array
    {
        //return json_decode($request->getContent(), true);

        return [
            'age' => (int) $request->get('age'),
            'name' => $request->get('name')
        ];
    }
}

$schemaRepo = new SchemaRepository();

try{
    $schemaRepo->append(__DIR__.'/schema/header-schema.json', 'header-parameters.schema');
    $schemaRepo->append(__DIR__.'/schema/response-content-schema.json', 'response-content-parameters.schema');
    $schemaRepo->append(__DIR__.'/schema/response-content-error-schema.json', 'response-content-parameters-error.schema');
    $schemaRepo->append(__DIR__.'/schema/response-header-schema.json', 'response-header-parameters.schema');
    $schemaRepo->append(__DIR__.'/schema/parameter-schema.json', 'request-parameters.schema');
    $schemaRepo->append(__DIR__.'/schema/url-parameters-schema.json', 'url-parameters.schema');
    //$schemaRepo->append(__DIR__.'/schema/body-schema.json', 'request-body.schema');
}catch(FileValidatorException $e){

}

$configParserRepository = new RouteConfigParserRepository();
$configParserRepository->append(new RouteSchemaConfigParser($schemaRepo));

$response = new Response();

$router = new Router(
    Request::createFromGlobals(),
    $response,
    $configParserRepository,
    null,
    new ResponseParserRepository()
);

$dispatcherRepository = new DispatcherRepository();
$dispatcherRepository->append(new Dispatcher('dispatcher'))
->append(new SchemaRequestPreDispatch())
->append(new SchemaResponsePostDispatch());

try{
    $routes = RouteFactory::fromJsonFile(
        __DIR__.'/routes.json',
        $router,
        $dispatcherRepository
    );
}catch(\Exception $e){
    return $e->getMessage();
}

$group = new RouteGroup('Test group', 'test', $routes);

$router->addGroup($group);

$router->dispatch()->send();
