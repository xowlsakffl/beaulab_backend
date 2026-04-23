<?php

namespace App\Common\OpenApi\Scramble;

use Dedoc\Scramble\Extensions\OperationExtension;
use Dedoc\Scramble\Support\Generator\Operation;
use Dedoc\Scramble\Support\Generator\Parameter;
use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Support\Generator\Types\ArrayType;
use Dedoc\Scramble\Support\Generator\Types\StringType;
use Dedoc\Scramble\Support\Generator\Types\Type;
use Dedoc\Scramble\Support\RouteInfo;

final class NormalizeQueryArrayParameters extends OperationExtension
{
    public function handle(Operation $operation, RouteInfo $routeInfo): void
    {
        if (strtolower($operation->method) !== 'get') {
            return;
        }

        foreach ($operation->parameters as $parameter) {
            if (! $parameter instanceof Parameter) {
                continue;
            }

            if ($parameter->in !== 'query' || ! str_ends_with($parameter->name, '[]')) {
                continue;
            }

            $schemaType = $parameter->schema?->type;

            if (! $schemaType instanceof ArrayType) {
                continue;
            }

            $itemType = $schemaType->items;
            $stringType = new StringType();

            if ($itemType instanceof Type) {
                $stringType->addProperties($itemType);
            }

            $stringType->nullable($schemaType->nullable);

            $parameter
                ->setName(substr($parameter->name, 0, -2))
                ->setSchema(Schema::fromType($stringType))
                ->setStyle('form')
                ->setExplode(false);

            if ($stringType->enum !== []) {
                $parameter->example($stringType->enum[0]);
            }

            $note = '값 하나 또는 쉼표로 구분한 여러 값을 입력합니다.';
            $parameter->description(trim($parameter->description . ' ' . $note));
        }
    }
}
