<?php declare(strict_types=1);

namespace LDL\Http\Router\Plugin\LDL\Schema\Helper;

class SchemaParserHelper
{
    public static function routeHasSchema(array $search, array $keys): bool
    {
        $data = $search;
        $exists = true;

        foreach($keys as $key){
            if(true === array_key_exists($key, $data)){
                $data = $data[$key];
                continue;
            }

            $exists = false;
            break;
        }

        return $exists;
    }
}