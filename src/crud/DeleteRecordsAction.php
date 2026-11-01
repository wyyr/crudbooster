<?php

namespace crocodicstudio\crudbooster\crud;

use Illuminate\Support\Facades\DB;

class DeleteRecordsAction
{
    /**
     * @param  array<int, int|string>|int|string  $ids
     */
    public function execute(string $table, string $primaryKey, array|int|string $ids, bool $softDelete): void
    {
        $query = DB::table($table);

        if (is_array($ids)) {
            $query->whereIn($primaryKey, $ids);
        } else {
            $query->where($primaryKey, $ids);
        }

        if ($softDelete) {
            $query->update(['deleted_at' => date('Y-m-d H:i:s')]);

            return;
        }

        $query->delete();
    }
}
