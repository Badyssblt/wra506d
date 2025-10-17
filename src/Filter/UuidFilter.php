<?php

namespace App\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Uid\Uuid;

final class UuidFilter extends AbstractFilter
{
    protected function filterProperty(
        string $property,
        $value,
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        Operation $operation = null,
        array $context = []
    ): void {
        // If the property is not 'id' or the value is not a valid UUID, skip
        if ($property !== 'id' || !$this->isValidUuid($value)) {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];
        $parameterName = $queryNameGenerator->generateParameterName($property);

        $queryBuilder
            ->andWhere(sprintf('%s.%s = :%s', $alias, $property, $parameterName))
            ->setParameter($parameterName, $value);
    }

    public function getDescription(string $resourceClass): array
    {
        return [
            'id' => [
                'property' => 'id',
                'type' => 'string',
                'required' => false,
                'description' => 'Filter by UUID',
                'openapi' => [
                    'example' => '01234567-89ab-cdef-0123-456789abcdef',
                    'format' => 'uuid',
                ],
            ],
        ];
    }

    private function isValidUuid($value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        try {
            Uuid::fromString($value);
            return true;
        } catch (\InvalidArgumentException $e) {
            return false;
        }
    }
}
