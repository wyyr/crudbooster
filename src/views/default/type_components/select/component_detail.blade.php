<?php
if ($form['datatable']??null) {
    $datatable = explode(',', $form['datatable']);
    $table = $datatable[0];
    $field = $datatable[1];
    echo CRUDBooster::first($table, ['id' => $value])->$field;
}
if ($form['dataquery']??null) {
    $dataquery = $form['dataquery'];
    $query = DB::select(DB::raw($dataquery));
    if ($query) {
        foreach ($query as $q) {
            if ($q->value == $value) {
                echo $q->label;
                break;
            }
        }
    }
}
if(isset($form['dataenum'])) {
    echo $value;
}
?>