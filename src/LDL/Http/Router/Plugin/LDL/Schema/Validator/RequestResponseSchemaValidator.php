<?php declare(strict_types=1);

namespace LDL\Http\Router\Plugin\LDL\Schema\Validator;

use LDL\Http\Core\Request\RequestInterface;
use LDL\Http\Core\Response\ResponseInterface;
use LDL\Http\Router\Plugin\LDL\Schema\Config\RouteSchemaConfig;
use LDL\Http\Router\Plugin\LDL\Schema\Parameter\Exception\InvalidParameterException;
use LDL\Http\Router\Route\Route;
use Phroute\Phroute\RouteParser;
use Swaggest\JsonSchema\Context;

class RequestResponseSchemaValidator
{
    /**
     * @var Route
     */
    private $route;

    /**
     * @var RouteSchemaConfig
     */
    private $config;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var ResponseInterface
     */
    private $response;

    /**
     * @var string
     */
    private $error;

    /**
     * @var array
     */
    private $urlArguments;

    public function __construct(
        Route $route,
        RouteSchemaConfig $schemaConfig,
        RequestInterface $request,
        ResponseInterface $response,
        array $urlArguments = []
    )
    {
        $this->route = $route;
        $this->config = $schemaConfig;
        $this->request = $request;
        $this->response = $response;
        $this->urlArguments = $urlArguments;
    }

    public function validate() : void
    {
        $this->parseRequestParameterSchema();
        $this->parseRequestBodySchema();
        $this->parseRequestHeaderSchema();
        $this->parseRequestUrlSchema();
    }

    public function getError() : ?string
    {
        return $this->error;
    }

    // <editor-fold desc="Private methods">
    private function parseRequestParameterSchema() : void
    {
        $requestParameters = (object) $this->request->getQuery()->all();

        $schema = $this->config->getRequestParameters();

        if(null === $schema){
            return;
        }

        foreach($schema->getProperties()->toArray() as $name => $param){
            $default = $schema->getProperties()->$name->getDefault();

            if(!isset($requestParameters->$name) && $default){
                $requestParameters->$name = $default;
            }
        }

        try{
            $context = new Context();
            $context->tolerateStrings = true;
            $schema->in($requestParameters, $context);
        }catch(\Exception $e){
            $this->error = sprintf('In "%s" section, "%s"', 'request parameters', $e->getMessage());
            $this->response->setStatusCode(ResponseInterface::HTTP_CODE_BAD_REQUEST);
        }

    }

    private function parseRequestBodySchema() : void
    {
        $bodySchema = $this->config->getBodySchema();

        if(!$bodySchema) {
            return;
        }

        $content = $this->response->getContent() ?: '[]';

        try{
            $content = json_decode($content,false,null,\JSON_THROW_ON_ERROR);

            $context = new Context();
            $context->tolerateStrings = true;

            $bodySchema->in(
                $content,
                $context
            );
        }catch(\Exception $e){
            $this->error = sprintf('In "%s" section, "%s"', 'request body', $e->getMessage());
            $this->response->setStatusCode(ResponseInterface::HTTP_CODE_BAD_REQUEST);
        }
    }

    private function parseRequestUrlSchema() : void
    {
        $args = $this->urlArguments;
        $schema = $this->config->getUrlParameters();

        if(null === $schema){
            return;
        }

        $parser = new RouteParser();
        $parsed = $parser->parse($this->route->getConfig()->getPrefix());

        $variableParameters = [];

        foreach($parsed[1] as $part){
            if(false === $part['variable']){
                continue;
            }

            $variableParameters[$part['name']] = current($args);
            next($args);
        }

        try{
            $context = new Context();
            $context->tolerateStrings = true;

            $schema->in(
                (object) $variableParameters,
                $context
            );
        }catch(\Exception $e){
            $this->error = sprintf('In "%s" section, "%s"', 'request URL parameters', $e->getMessage());
            $this->response->setStatusCode(ResponseInterface::HTTP_CODE_BAD_REQUEST);
        }

    }

    private function parseRequestHeaderSchema() : void
    {
        $schema = $this->config->getHeaderSchema();

        if(!$schema) {
            return;
        }

        try{
            $context = new Context();
            $context->tolerateStrings = true;

            $headers = new \stdClass();

            foreach($this->request->getHeaderBag()->getIterator() as $name => $value){
                $headers->$name = is_array($value) ? $value[0] : $value;
            }

            $schema->in(
                $headers,
                $context
            );
        }catch(\Exception $e){
            $this->error = sprintf('In "%s" section, "%s"', 'headers', $e->getMessage());
            $this->response->setStatusCode(ResponseInterface::HTTP_CODE_BAD_REQUEST);
        }
    }

    //</editor-fold>
}