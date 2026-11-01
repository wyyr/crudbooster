<?php

namespace crocodicstudio\crudbooster\imports;

use crocodicstudio\crudbooster\helpers\CRUDBooster;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ImportWorkflow
{
    public const INVALID_MAPPING = 'The selected import columns are invalid.';

    /**
     * @return array{total_data_import: int, data_import_column: array<int, string>, table_columns: array<int, string>}
     */
    public function preview(string $filePath, string $table): array
    {
        $import = new DefaultImportExcel;
        Excel::import($import, $filePath);

        $countRows = array_sum(array_map('count', $import->sheetData));
        $dataImportColumn = [];

        foreach ($import->sheetData as $rows) {
            foreach ($rows as $value) {
                $dataImportColumn = array_keys($value);
                break;
            }
        }

        return [
            'total_data_import' => $countRows,
            'data_import_column' => $dataImportColumn,
            'table_columns' => Schema::getColumnListing($table),
        ];
    }

    public function resolveFilePath(mixed $encodedFile): ?string
    {
        if (! is_string($encodedFile) || $encodedFile === '') {
            return null;
        }

        $relativePath = base64_decode($encodedFile, true);
        $storagePath = realpath(Storage::disk('local')->path(''));

        if ($relativePath === false || $relativePath === '' || str_contains($relativePath, "\0") || $storagePath === false) {
            return null;
        }

        $filePath = realpath($storagePath.DIRECTORY_SEPARATOR.$relativePath);
        if ($filePath === false || ! is_file($filePath)) {
            return null;
        }

        $storagePrefix = rtrim($storagePath, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
        if (DIRECTORY_SEPARATOR === '\\') {
            $isInsideStorage = str_starts_with(strtolower($filePath), strtolower($storagePrefix));
        } else {
            $isInsideStorage = str_starts_with($filePath, $storagePrefix);
        }

        return $isInsideStorage ? $filePath : null;
    }

    /**
     * @param  array<int|string, mixed>  $selectedColumns
     */
    public function import(
        string $filePath,
        string $table,
        string $titleField,
        array $selectedColumns,
        string $fileKey,
    ): ?string {
        $import = new DefaultImportExcel;
        Excel::import($import, $filePath);

        $tableColumns = Schema::getColumnListing($table);
        if (! $this->hasValidMapping($selectedColumns, $tableColumns, $import->sheetData)) {
            return self::INVALID_MAPPING;
        }

        $hasCreatedAt = CRUDBooster::isColumnExists($table, 'created_at');
        $hasPassword = CRUDBooster::isColumnExists($table, 'password');
        $columnMappings = [];
        foreach ($selectedColumns as $columnIndex => $heading) {
            $columnName = $tableColumns[(int) $columnIndex];
            $columnMappings[] = [
                'column_name' => $columnName,
                'heading' => $heading,
                'is_foreign_key' => CRUDBooster::isForeignKey($columnName),
            ];
        }

        foreach ($import->sheetData as $rows) {
            foreach ($rows as $value) {
                try {
                    $needsTransaction = false;
                    foreach ($columnMappings as $mapping) {
                        if (
                            $mapping['is_foreign_key']
                            && $value[$mapping['heading']] != ''
                            && ! intval($value[$mapping['heading']])
                        ) {
                            $needsTransaction = true;
                            break;
                        }
                    }

                    $wasImported = $this->importRow(
                        $value,
                        $table,
                        $titleField,
                        $columnMappings,
                        $hasCreatedAt,
                        $hasPassword,
                        $needsTransaction,
                    );

                    if ($wasImported) {
                        Cache::increment('success_'.$fileKey);
                    }
                } catch (Exception $exception) {
                    Cache::put('error_'.$fileKey, (string) $exception, 500);
                } finally {
                    Cache::increment('processed_'.$fileKey);
                }
            }
        }

        return null;
    }

    /**
     * @param  array<int|string, mixed>  $row
     * @param  list<array{column_name: string, heading: string, is_foreign_key: bool}>  $columnMappings
     */
    private function importRow(
        array $row,
        string $table,
        string $titleField,
        array $columnMappings,
        bool $hasCreatedAt,
        bool $hasPassword,
        bool $needsTransaction,
    ): bool {
        $persistRow = function () use (
            $row, $table, $titleField, $columnMappings, $hasCreatedAt, $hasPassword,
        ): bool {
            $data = [];
            foreach ($columnMappings as $mapping) {
                $columnName = $mapping['column_name'];
                $heading = $mapping['heading'];

                if ($mapping['is_foreign_key']) {
                    if ($row[$heading] == '') {
                        continue;
                    }

                    if (intval($row[$heading])) {
                        $data[$columnName] = $row[$heading];
                    } else {
                        $data[$columnName] = $this->resolveForeignKey($columnName, $row[$heading]);
                    }
                } else {
                    $data[$columnName] = $row[$heading];
                }
            }

            foreach ($data as $columnName => $valueForColumn) {
                if ($columnName == $titleField && $valueForColumn == '') {
                    return false;
                }
            }

            $data = array_map('trim', $data);

            if ($hasCreatedAt) {
                $data['created_at'] = date('Y-m-d H:i:s');
            }

            if ($hasPassword && isset($data['password']) && $data['password'] != '') {
                $data['password'] = bcrypt($data['password']);
            }

            DB::table($table)->insert($data);

            return true;
        };

        return $needsTransaction ? DB::transaction($persistRow) : $persistRow();
    }

    /**
     * @param  array<int|string, mixed>  $selectedColumns
     * @param  array<int, string>  $tableColumns
     * @param  array<int, array<int, array<string, mixed>>>  $sheetData
     */
    private function hasValidMapping(array $selectedColumns, array $tableColumns, array $sheetData): bool
    {
        if ($selectedColumns === []) {
            return false;
        }

        foreach ($selectedColumns as $columnIndex => $heading) {
            if (! is_numeric($columnIndex) || ! array_key_exists((int) $columnIndex, $tableColumns)) {
                return false;
            }

            if (in_array($tableColumns[(int) $columnIndex], ['id', 'created_at', 'updated_at', 'deleted_at'], true)) {
                return false;
            }

            if (! is_string($heading) || $heading === '') {
                return false;
            }

            foreach ($sheetData as $rows) {
                foreach ($rows as $row) {
                    if (! is_array($row) || ! array_key_exists($heading, $row)) {
                        return false;
                    }
                }
            }
        }

        return true;
    }

    private function resolveForeignKey(string $columnName, mixed $value): mixed
    {
        $relationTable = CRUDBooster::getTableForeignKey($columnName);
        $relationModule = DB::table('cms_moduls')->where('table_name', $relationTable)->first();
        $relationClass = 'crocodicstudio\\crudbooster\\controllers\\'.$relationModule->controller;

        if (! class_exists($relationClass)) {
            $relationClass = 'App\\Http\\Controllers\\CRUDBooster\\'.$relationModule->controller;
        }

        $relationController = new $relationClass;
        $relationController->cbLoader();
        $titleField = $relationController->title_field;
        $relationInsertData = [$titleField => $value];

        if (CRUDBooster::isColumnExists($relationTable, 'created_at')) {
            $relationInsertData['created_at'] = date('Y-m-d H:i:s');
        }

        $relationExists = DB::table($relationTable)->where($titleField, $value)->first();
        if ($relationExists) {
            $primaryKey = $relationController->primary_key;

            return $relationExists->$primaryKey;
        }

        return DB::table($relationTable)->insertGetId($relationInsertData);
    }
}
