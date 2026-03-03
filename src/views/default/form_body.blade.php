{{-- Loading Assets --}}
@php $asset_already = []; @endphp
@foreach ($forms as $form)
    @php
        $type = $form['type'] ?? 'text';
        $name = $form['name'] ?? null;
    @endphp

    @if (!$name || in_array($type, $asset_already))
        @continue
    @endif

    @if (file_exists(base_path('/vendor/wyyr/crudbooster/src/views/default/type_components/' . $type . '/asset.blade.php')))
        @include('crudbooster::default.type_components.' . $type . '.asset')
    @elseif(file_exists(resource_path('views/vendor/crudbooster/type_components/' . $type . '/asset.blade.php')))
        @include('vendor.crudbooster.type_components.' . $type . '.asset')
    @endif

    @php $asset_already[] = $type; @endphp
@endforeach

{{-- Loading input components --}}
@php
    $header_group_class = '';
    $join_query_ = [];
@endphp

@foreach ($forms as $index => $form)
    @php
        $name = $form['name'] ?? null;
        $type = $form['type'] ?? 'text';
        $join = $form['join'] ?? null;

        // Value Assignment
        $value = $form['value'] ?? '';
        $value = $row->{$name} ?? $value;
        $old = old($name);
        $value = !empty($old) ? $old : $value;

        // Validation Parsing
        $validation = [];
        $validation_raw = isset($form['validation']) ? explode('|', $form['validation']) : [];
        foreach ($validation_raw as $vr) {
            $vr_a = explode(':', $vr);
            if (isset($vr_a[1])) {
                $validation[$vr_a[0]] = $vr_a[1];
            } else {
                $validation[$vr] = true;
            }
        }

        // Callbacks
        if (isset($form['callback_php'])) {
            try {
                @eval("\$value = " . $form['callback_php'] . ';');
            } catch (\Throwable $e) {
                $value = 'Error in callback_php';
            }
        }

        if (isset($form['callback'])) {
            $value = call_user_func($form['callback'], $row);
        }

        // Join Logic
        if ($join && isset($row)) {
            $join_arr = array_map('trim', explode(',', $join));
            $join_table = $join_arr[0];
            $join_title = $join_arr[1];
            $fk_name = 'id_' . $join_table;
            $fk_value = $row->{$fk_name} ?? null;

            if ($fk_value) {
                $join_query = DB::table($join_table)->select($join_title)->where('id', $fk_value)->first();
                $value = $join_query->{$join_title} ?? null;
            }
        }

        // Attributes logic
        $required =
            ($form['required'] ?? false) || strpos($form['validation'] ?? '', 'required') !== false ? 'required' : '';
        $readonly = $form['readonly'] ?? false ? 'readonly' : '';
        $disabled = $form['disabled'] ?? false ? 'disabled' : '';
        $placeholder = isset($form['placeholder']) ? "placeholder='" . $form['placeholder'] . "'" : '';
        $col_width = $form['width'] ?? 'col-sm-9';

        if ($parent_field == $name) {
            $type = 'hidden';
            $value = $parent_id;
        }

        if ($type == 'header') {
            $header_group_class = "header-group-$index";
        } else {
            $header_group_class = $header_group_class ?: "header-group-$index";
        }
    @endphp

    {{-- Render Component --}}
    @if (file_exists(base_path('/vendor/wyyr/crudbooster/src/views/default/type_components/' . $type . '/component.blade.php')))
        @include('crudbooster::default.type_components.' . $type . '.component')
    @elseif(file_exists(resource_path('views/vendor/crudbooster/type_components/' . $type . '/component.blade.php')))
        @include('vendor.crudbooster.type_components.' . $type . '.component')
    @else
        <p class='text-danger'>{{ $type }} is not found in type component system</p><br />
    @endif
@endforeach
