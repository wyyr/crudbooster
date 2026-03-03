{{-- 1. Loading Assets --}}
@php $asset_already = []; @endphp
@foreach ($forms as $form)
    @php $type = $form['type'] ?? 'text'; @endphp

    @if (in_array($type, $asset_already))
        @continue
    @endif

    @php
        $asset_vendor = "vendor/wyyr/crudbooster/src/views/default/type_components/$type/asset.blade.php";
        $asset_resource = "views/vendor/crudbooster/type_components/$type/asset.blade.php";
    @endphp

    @if (file_exists(base_path($asset_vendor)))
        @include('crudbooster::default.type_components.' . $type . '.asset')
    @elseif (file_exists(resource_path($asset_resource)))
        @include('vendor.crudbooster.type_components.' . $type . '.asset')
    @endif

    @php $asset_already[] = $type; @endphp
@endforeach

@push('head')
    <style type="text/css">
        #table-detail tr td:first-child {
            font-weight: bold;
            width: 25%;
        }
    </style>
@endpush

{{-- 2. Detail Table --}}
<div class='table-responsive'>
    <table id='table-detail' class='table table-striped'>
        @foreach ($forms as $index => $form)
            @php
                $name = $form['name'] ?? null;
                $showInDetail = $form['showInDetail'] ?? true;

                if (!$name || $showInDetail == false) {
                    continue;
                }

                $type = $form['type'] ?? 'text';
                $value = $form['value'] ?? '';
                $value = isset($row) && isset($row->{$name}) ? $row->{$name} : $value;
                $join = $form['join'] ?? null;

                // Form Attributes
                $required = $form['required'] ?? false ? 'required' : '';
                $readonly = $form['readonly'] ?? false ? 'readonly' : '';
                $disabled = $form['disabled'] ?? false ? 'disabled' : '';
                $jquery = $form['jquery'] ?? null;
                $placeholder = $form['placeholder'] ?? false ? "placeholder='{$form['placeholder']}'" : '';

                // Handle Callbacks
                if (isset($form['callback_php'])) {
                    try {
                        @eval("\$value = " . $form['callback_php'] . ';');
                    } catch (\Throwable $e) {
                        $value = 'Error in callback_php';
                    }
                }

                if (isset($form['callback']) && is_callable($form['callback'])) {
                    $value = call_user_func($form['callback'], $row);
                }

                if (isset($form['default_value'])) {
                    $value = $form['default_value'];
                }

                // Handle Joins
                if ($join && isset($row)) {
                    $join_arr = array_map('trim', explode(',', $join));
                    $join_table = $join_arr[0] ?? null;
                    $join_title = $join_arr[1] ?? null;

                    if ($join_table && $join_title) {
                        $join_fk = CB::getForeignKey($table, $join_table);
                        $fk_value = $row->{$join_fk} ?? null;

                        if ($fk_value) {
                            $join_data = DB::table($join_table)
                                ->select($join_title)
                                ->where(CB::pk($join_table), $fk_value)
                                ->first();
                            $value = $join_data->{$join_title} ?? null;
                        }
                    }
                }

                $file_loc = base_path(
                    "vendor/wyyr/crudbooster/src/views/default/type_components/$type/component_detail.blade.php",
                );
                $user_loc = resource_path("views/vendor/crudbooster/type_components/$type/component_detail.blade.php");

                $final_view = null;
                $actual_path = null;

                if (file_exists($file_loc)) {
                    $final_view = "crudbooster::default.type_components.$type.component_detail";
                    $actual_path = $file_loc;
                } elseif (file_exists($user_loc)) {
                    $final_view = "vendor.crudbooster.type_components.$type.component_detail";
                    $actual_path = $user_loc;
                }
            @endphp

            @if ($final_view)
                @php
                    $is_table_row = false;
                    if (is_readable($actual_path)) {
                        $content = file_get_contents($actual_path);
                        $is_table_row = substr(trim($content), 0, 4) === '<tr>';
                    }
                @endphp

                @if ($is_table_row)
                    @include($final_view)
                @else
                    <tr>
                        <td>{{ $form['label'] }}</td>
                        <td>@include($final_view)</td>
                    </tr>
                @endif
            @endif
        @endforeach
    </table>
</div>
