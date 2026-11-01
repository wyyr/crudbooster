<?php

namespace crocodicstudio\crudbooster\crud;

use crocodicstudio\crudbooster\helpers\CB;
use crocodicstudio\crudbooster\helpers\CRUDBooster;
use Illuminate\Support\Facades\DB;

class UpdateRecordAction
{
    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<int, array<string, mixed>>  $fields
     */
    public function execute(string $table, string $primaryKey, int|string $id, array $attributes, array $fields): void
    {
        DB::transaction(function () use ($table, $primaryKey, $id, $attributes, $fields): void {
            DB::table($table)->where($primaryKey, $id)->update($attributes);

            foreach ($fields as $field) {
                $name = $field['name'] ?? null;
                $type = $field['type'] ?? null;
                $relationshipTable = $field['relationship_table'] ?? null;
                $datatable = $field['datatable'] ?? null;

                if (! $name || ! $type) {
                    continue;
                }

                $inputData = request($name);

                if ($type === 'checkbox' && $relationshipTable && $datatable) {
                    $relatedTable = explode(',', $datatable)[0];
                    $relatedForeignKey = CRUDBooster::getForeignKey($relatedTable, $relationshipTable);
                    $foreignKey = CRUDBooster::getForeignKey($table, $relationshipTable);
                    DB::table($relationshipTable)->where($foreignKey, $id)->delete();

                    if ($inputData) {
                        foreach ($inputData as $inputId) {
                            DB::table($relationshipTable)->insert([
                                $foreignKey => $id,
                                $relatedForeignKey => $inputId,
                            ]);
                        }
                    }
                }

                if ($type === 'select2') {
                    $datatableOriginal = $field['datatable_orig'] ?? '';

                    if ($relationshipTable && $datatableOriginal === '') {
                        $relatedTable = explode(',', $datatable ?? '')[0];

                        if ($relatedTable) {
                            $relatedForeignKey = CRUDBooster::getForeignKey($relatedTable, $relationshipTable);
                            $foreignKey = CRUDBooster::getForeignKey($table, $relationshipTable);
                            DB::table($relationshipTable)->where($foreignKey, $id)->delete();

                            if ($inputData) {
                                foreach ($inputData as $inputId) {
                                    DB::table($relationshipTable)->insert([
                                        $foreignKey => $id,
                                        $relatedForeignKey => $inputId,
                                    ]);
                                }
                            }
                        }
                    }

                    if ($relationshipTable && $datatableOriginal !== '') {
                        $parameters = explode('|', $datatableOriginal);
                        $parameters[2] = $parameters[2] ?? 'id';
                        DB::table($parameters[0])
                            ->where($parameters[2], $id)
                            ->update([$parameters[1] => implode(',', (array) $inputData)]);
                    }
                }

                if ($type !== 'child') {
                    continue;
                }

                $label = $field['label'] ?? '';
                $slugName = str_slug($label, '');
                $columns = $field['columns'] ?? [];
                $childTableRaw = $field['table'] ?? null;
                $foreignKey = $field['foreign_key'] ?? null;

                if (! $childTableRaw || ! $foreignKey || empty($columns)) {
                    continue;
                }

                $firstColumnName = $columns[0]['name'] ?? null;
                $firstColumnData = request($slugName.'-'.$firstColumnName);
                $rowCount = is_array($firstColumnData) ? count($firstColumnData) - 1 : -1;
                $childRows = [];
                $childTable = CRUDBooster::parseSqlTable($childTableRaw)['table'];

                DB::table($childTable)->where($foreignKey, $id)->delete();

                if ($rowCount >= 0) {
                    $nextId = CRUDBooster::newId($childTable);
                    $childPrimaryKey = CB::pk($childTable);

                    for ($index = 0; $index <= $rowCount; $index++) {
                        $childRow = [];

                        foreach ($columns as $column) {
                            $columnName = $column['name'] ?? null;
                            if (! $columnName) {
                                continue;
                            }

                            $columnData = request($slugName.'-'.$columnName);
                            $columnValue = $columnData[$index] ?? null;

                            if (isset($columnValue)) {
                                $childRow[$columnName] = $columnValue;
                            }
                        }

                        if (! empty($childRow)) {
                            $childRow[$childPrimaryKey] = $nextId;
                            $childRow[$foreignKey] = $id;
                            $childRows[] = $childRow;
                            $nextId++;
                        }
                    }
                }

                if (! empty($childRows)) {
                    DB::table($childTable)->insert(array_reverse($childRows));
                }
            }
        });
    }
}
