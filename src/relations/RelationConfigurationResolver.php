<?php

namespace crocodicstudio\crudbooster\relations;

class RelationConfigurationResolver
{
    public function __construct(private array $fields) {}

    public function findDataTable(string $table, string $label, string $foreignKey): ?array
    {
        foreach ($this->configuredFields() as $field) {
            if (empty($field['datatable']) || empty($field['parent_select'])) {
                continue;
            }

            $datatable = array_map('trim', explode(',', $field['datatable']));
            $parentSelect = array_map('trim', explode(',', $field['parent_select']));

            if (
                count($datatable) >= 2
                && $datatable[0] === $table
                && $datatable[1] === $label
                && ($parentSelect[0] ?? null) === $foreignKey
            ) {
                return $field;
            }
        }

        return null;
    }

    public function findDataModal(string $table, string $columns): ?array
    {
        $requestedColumns = array_map('trim', explode(',', $columns));

        foreach ($this->configuredFields() as $field) {
            if (empty($field['datamodal_table']) || empty($field['datamodal_columns'])) {
                continue;
            }

            $configuredColumns = array_map('trim', explode(',', $field['datamodal_columns']));
            $expectedColumns = array_merge(['id'], $configuredColumns);

            if ($field['datamodal_table'] === $table && $requestedColumns === $expectedColumns) {
                return $field;
            }
        }

        return null;
    }

    private function configuredFields(): array
    {
        return $this->collectConfiguredFields($this->fields);
    }

    private function collectConfiguredFields(array $fields): array
    {
        $configuredFields = [];

        foreach ($fields as $field) {
            if (! is_array($field)) {
                continue;
            }

            $configuredFields[] = $field;
            if (is_array($field['columns'] ?? null)) {
                $configuredFields = array_merge($configuredFields, $this->collectConfiguredFields($field['columns']));
            }
        }

        return $configuredFields;
    }
}
