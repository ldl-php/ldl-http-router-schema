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
use LDL\Type\Collection\Validator\File\Exception\FileValidatorException;

class Dispatcher implements RouteDispatcherInterface
{
    public function dispatch(
        RequestInterface $request,
        ResponseInterface $response
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
    $schemaRepo->append(__DIR__.'/schema/response-header-schema.json', 'response-header-parameters.schema');
    $schemaRepo->append(__DIR__.'/schema/parameter-schema.json', 'request-parameters.schema');
    $schemaRepo->append(__DIR__.'/schema/url-parameters-schema.json', 'url-parameters.schema');
    //$schemaRepo->append(__DIR__.'/schema/body-schema.json', 'request-body.schema');
}catch(FileValidatorException $e){

}

$parserCollection = new RouteConfigParserCollection();
$parserCollection->append(new RouteSchemaConfigParser($schemaRepo));

$response = new Response();

$router = new Router(
    Request::createFromGlobals(),
    $response
);

try{
    $routes = RouteFactory::fromJsonFile(
        __DIR__.'/routes.json',
        $router,
        null,
        $parserCollection
    );
}catch(\Exception $e){
    return $e->getMessage();
}

$group = new RouteGroup('student', 'student', $routes);

$router->addGroup($group);

$router->dispatch()->send();
