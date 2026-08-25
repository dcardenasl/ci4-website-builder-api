<?php

declare(strict_types=1);

namespace App\DTO\Request\Support;

trait TracksProvidedFields
{
    /** @var array<string, bool> */
    private readonly array $providedFields;

    /**
     * @param array<string, mixed> $data
     */
    protected function trackProvidedFields(array $data): void
    {
        $providedFields = [];

        foreach (array_keys($data) as $field) {
            $providedFields[(string) $field] = true;
        }

        $this->providedFields = $providedFields;
    }

    protected function fieldWasProvided(string $field): bool
    {
        return $this->providedFields[$field] ?? false;
    }

    /**
     * Keep explicit nulls while omitting nullable fields that were absent.
     *
     * @param array<string, mixed> $fields
     * @return array<string, mixed>
     */
    protected function filterProvidedFields(array $fields, bool $keepEmptyValues = true): array
    {
        return array_filter(
            $fields,
            function (mixed $value, int|string $field) use ($keepEmptyValues): bool {
                if ($value === null) {
                    return $this->fieldWasProvided((string) $field);
                }

                return $keepEmptyValues || $value !== '';
            },
            ARRAY_FILTER_USE_BOTH
        );
    }
}
