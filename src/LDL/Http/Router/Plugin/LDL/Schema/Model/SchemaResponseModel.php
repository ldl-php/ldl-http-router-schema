<?php declare(strict_types=1);

namespace LDL\Http\Router\Plugin\LDL\Schema\Model;

use Swaggest\JsonSchema\SchemaContract;

class SchemaResponseModel implements SchemaResponseModelInterface
{
    /**
     * @var int
     */
    private $code;

    /**
     * @var SchemaContract
     */
    private $schema;

    /**
     * @param array $array
     * @return SchemaResponseModelInterface
     */
    public static function fromArray(array $array): SchemaResponseModelInterface
    {
        $default = get_class_vars(__CLASS__);

        $merge = array_merge($default, $array);

        $instance = new static();

        return $instance->setCode($merge['code'])
            ->setSchema($merge['schema']);
    }

    /**
     * @return int
     */
    public function getCode(): int
    {
        return $this->code;
    }

    /**
     * @param int $code
     * @return SchemaResponseModel
     */
    private function setCode(int $code): SchemaResponseModelInterface
    {
        $this->code = $code;
        return $this;
    }

    /**
     * @return SchemaContract
     */
    public function getSchema(): SchemaContract
    {
        return $this->schema;
    }

    /**
     * @param SchemaContract $schema
     * @return SchemaResponseModel
     */
    private function setSchema(SchemaContract $schema): SchemaResponseModelInterface
    {
        $this->schema = $schema;
        return $this;
    }
}