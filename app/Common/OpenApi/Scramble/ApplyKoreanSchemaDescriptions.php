<?php

namespace App\Common\OpenApi\Scramble;

use Dedoc\Scramble\Contracts\DocumentTransformer;
use Dedoc\Scramble\OpenApiContext;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\Reference;
use Dedoc\Scramble\Support\Generator\RequestBodyObject;
use Dedoc\Scramble\Support\Generator\Response;
use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Support\Generator\Types\ArrayType;
use Dedoc\Scramble\Support\Generator\Types\ObjectType;
use Dedoc\Scramble\Support\Generator\Types\Type;
use Dedoc\Scramble\Support\Generator\Combined\AllOf;
use Dedoc\Scramble\Support\Generator\Combined\AnyOf;

final class ApplyKoreanSchemaDescriptions implements DocumentTransformer
{
    public function handle(OpenApi $document, OpenApiContext $context): void
    {
        $requestAttributesBySchemaName = KoreanOpenApiDescriptions::requestAttributesBySchemaName();

        foreach ($document->components->schemas as $schemaName => $schema) {
            $this->describeSchema(
                $schema,
                $requestAttributesBySchemaName[class_basename($schemaName)] ?? []
            );
        }

        foreach ($document->paths as $path) {
            foreach ($path->operations as $operation) {
                if ($operation->requestBodyObject instanceof RequestBodyObject) {
                    foreach ($operation->requestBodyObject->content as $schema) {
                        $this->describeSchema($schema);
                    }
                }

                foreach ($operation->responses ?? [] as $response) {
                    if (! $response instanceof Response) {
                        continue;
                    }

                    foreach ($response->content as $schema) {
                        $this->describeSchema($schema);
                    }
                }
            }
        }
    }

    /**
     * @param array<string, string> $requestAttributes
     */
    private function describeSchema(Schema|Reference $schema, array $requestAttributes = []): void
    {
        if ($schema instanceof Reference) {
            $resolved = $schema->resolve();
            if ($resolved instanceof Schema) {
                $this->describeSchema($resolved, $requestAttributes);
            }

            return;
        }

        $this->describeType($schema->type, null, $requestAttributes);
    }

    /**
     * @param array<string, string> $requestAttributes
     */
    private function describeType(Type $type, ?string $fieldName = null, array $requestAttributes = []): void
    {
        if ($fieldName !== null && $type->description === '') {
            $description = KoreanOpenApiDescriptions::field($fieldName, $requestAttributes);
            if ($description !== null) {
                $type->setDescription($description);
            }
        }

        if ($type instanceof Reference) {
            $resolved = $type->resolve();
            if ($resolved instanceof Schema) {
                $this->describeSchema($resolved, $requestAttributes);
            }

            return;
        }

        if ($type instanceof ObjectType) {
            foreach ($type->properties as $propertyName => $propertyType) {
                if ($propertyType instanceof Type) {
                    $this->describeType($propertyType, $propertyName, $requestAttributes);
                }
            }

            return;
        }

        if ($type instanceof ArrayType) {
            if ($type->items instanceof Type) {
                $this->describeType($type->items, null, $requestAttributes);
            }

            foreach ($type->prefixItems as $item) {
                if ($item instanceof Type) {
                    $this->describeType($item, null, $requestAttributes);
                }
            }

            return;
        }

        if ($type instanceof AnyOf || $type instanceof AllOf) {
            foreach ($type->items as $item) {
                if ($item instanceof Type) {
                    $this->describeType($item, $fieldName, $requestAttributes);
                }
            }
        }
    }
}
