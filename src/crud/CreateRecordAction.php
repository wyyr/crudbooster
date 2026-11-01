<?php

namespace crocodicstudio\crudbooster\crud;

use crocodicstudio\crudbooster\helpers\CRUDBooster;
use Illuminate\Support\Facades\DB;

class CreateRecordAction
{
    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<int, array<string, mixed>>  $fields
     */
    public function execute(string $table, string $primaryKey, array $attributes, array $fields): int|string
    {
        return DB::transaction(function () use ($table, $primaryKey, $attributes, $fields): int|string {
            $id = DB::table($table)->insertGetId($attributes);

            if (isset($attributes[$primaryKey]) && $attributes[$primaryKey] != $id) {
                $id = $attributes[$primaryKey];
            }

            foreach ($fields as $field) {
                $name = $field['name'] ?? null;
                $type = $field['type'] ?? null;

                if (! $name || ! $type) {
                    continue;
                }

                $inputData = request($name);

                if (in_array($type, ['checkbox', 'select2'], true) && ! empty($field['relationship_table'])) {
                    $datatable = explode(',', $field['datatable'])[0];
                    $relationshipTable = $field['relationship_table'];
                    $foreignKey = CRUDBooster::getForeignKey($table, $relationshipTable);
                    $relatedForeignKey = CRUDBooster::getForeignKey($datatable, $relationshipTable);

                    DB::table($relationshipTable)->where($foreignKey, $id)->delete();

                    if (is_array($inputData)) {
                        foreach ($inputData as $relationId) {
                            DB::table($relationshipTable)->insert([
                                $foreignKey => $id,
                                $relatedForeignKey => $relationId,
                            ]);
                        }
                    }
                }

                if ($type !== 'child') {
                    continue;
                }

                $slugName = str_slug($field['label'], '');
                $columns = $field['columns'];
                $foreignKey = $field['foreign_key'];
                $firstColumnData = request($slugName.'-'.$columns[0]['name']);

                if (! is_array($firstColumnData)) {
                    continue;
                }

                $childRows = [];
                foreach ($firstColumnData as $index => $value) {
                    $childRow = [];
                    foreach ($columns as $column) {
                        $columnName = $column['name'];
                        $columnValue = request($slugName.'-'.$columnName)[$index] ?? null;

                        if (isset($columnValue)) {
                            $childRow[$columnName] = $columnValue;
                        }
                    }

                    if (! empty($childRow)) {
                        $childRow[$foreignKey] = $id;
                        $childRows[] = $childRow;
                    }
                }

                if (! empty($childRows)) {
                    $childTable = CRUDBooster::parseSqlTable($field['table'])['table'];
                    DB::table($childTable)->insert($childRows);
                }
            }

            return $id;
        });
    }
}
