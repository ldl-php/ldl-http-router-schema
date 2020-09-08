<?php declare(strict_types=1);

namespace LDL\Http\Router\Plugin\LDL\Schema\Validator;

use LDL\Http\Core\Response\ResponseInterface;
use LDL\Http\Router\Plugin\LDL\Schema\Config\RouteSchemaConfig;
use LDL\Http\Router\Plugin\LDL\Schema\Model\SchemaResponseModelInterface;
use Swaggest\JsonSchema\Context;
use Swaggest\JsonSchema\SchemaContract;

class ResponseSchemaValidator
{
    /**
     * @var RouteSchemaConfig
     */
    private $config;

    /**
     * @var ResponseInterface
     */
    private $response;

    /**
     * @var string
     */
    private $error;

    public function __construct(
        RouteSchemaConfig $schemaConfig,
        ResponseInterface $response
    )
    {
        $this->config = $schemaConfig;
        $this->response = $response;
    }

    public function validate() : void
    {
        $code = $this->response->getStatusCode();

        $headers = $this->config->getResponseSchemaHeader();
        $contents = $this->config->getResponseSchemaContent();

        /**
         * @var SchemaResponseModelInterface $header
         */
        foreach($headers as $header){
            if($code !== $header->getCode()){
                continue;
            }

            $this->parseResponseHeaderSchema($header->getSchema());
        }

        /**
         * @var SchemaResponseModelInterface $content
         */
        foreach($contents as $content){
            if($code !== $content->getCode()){
                continue;
            }

            $this->parseResponseContentSchema($content->getSchema());
        }
    }

    public function getError() : ?string
    {
        return $this->error;
    }

    // <editor-fold desc="Private methods">

    private function parseResponseHeaderSchema(SchemaContract $schema) : void
    {
        if(!$schema){
            return;
        }

        try{
            $context = new Context();
            $context->tolerateStrings = true;

            $headers = new \stdClass();

            foreach($this->response->getHeaderBag()->getIterator() as $name => $value){
                $headers->$name = is_array($value) ? $value[0] : $value;
            }

            $schema->in(
                $headers,
                $context
            );
        }catch(\Exception $e){
            $this->error = sprintf('In "%s" section, "%s"', 'response headers', $e->getMessage());
            $this->response->setStatusCode(ResponseInterface::HTTP_CODE_BAD_REQUEST);
        }
    }

    private function parseResponseContentSchema(SchemaContract $schema) : void
    {
        if(!$schema){
            return;
        }

        $content = $this->response->getContent() ?: '[]';

        try{
            $content = json_decode($content,false,2048,\JSON_THROW_ON_ERROR);

            $context = new Context();
            $context->tolerateStrings = true;

            $schema->in(
                $content,
                $context
            );
        }catch(\Exception $e){
            $this->error = sprintf('In "%s" section, "%s"', 'response content', $e->getMessage());
            $this->response->setStatusCode(ResponseInterface::HTTP_CODE_BAD_REQUEST);
        }
    }

    //</editor-fold>
}