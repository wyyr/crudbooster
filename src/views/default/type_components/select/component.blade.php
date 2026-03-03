@php
    $default = !empty($form['default']) ? $form['default'] : cbLang('text_prefix_option') . ' ' . $form['label'];
    $parent_select_raw = $form['parent_select'] ?? null;
@endphp

@if ($parent_select_raw)
    @php
        $parent_select =
            count(explode(',', $parent_select_raw)) > 1 ? explode(',', $parent_select_raw) : $parent_select_raw;
        $parent = is_array($parent_select) ? $parent_select[0] : $parent_select;
        $add_field = is_array($parent_select) ? $parent_select[1] : '';
    @endphp

    @push('bottom')
        <script type="text/javascript">
            $(function() {
                $('#{{ $parent }}, input:radio[name={{ $parent }}]').change(function() {
                    var $current = $("#{{ $name }}");
                    var parent_id = $(this).val();
                    var fk_name = "{{ $parent }}";
                    var fk_value = $(this).val();
                    var datatable = "{{ $form['datatable'] ?? '' }}".split(',');

                    @if (!empty($add_field))
                        var add_field = ($("#{{ $add_field }}").val()) ?
                            $("#{{ $add_field }}").val() : "";
                    @endif

                    var datatableWhere = "{{ $form['datatable_where'] ?? '' }}";
                    @if (!empty($add_field))
                        if (datatableWhere) {
                            if (add_field) {
                                datatableWhere = datatableWhere + " and {{ $add_field }} = " + add_field;
                            }
                        } else {
                            if (add_field) {
                                datatableWhere = "{{ $add_field }} = " + add_field;
                            }
                        }
                    @endif

                    var table = datatable[0] ? datatable[0].trim() : '';
                    var label = datatable[1] ? datatable[1].trim() : '';
                    var value = "{{ $value }}";

                    if (fk_value != '') {
                        $current.html("<option value=''>{{ cbLang('text_loading') }} {{ $form['label'] }}");
                        $.get("{{ CRUDBooster::mainpath('data-table') }}?table=" + table + "&label=" + label +
                            "&fk_name=" + fk_name + "&fk_value=" + fk_value + "&datatable_where=" +
                            encodeURI(datatableWhere),
                            function(response) {
                                if (response) {
                                    $current.html("<option value=''>{{ $default }}");
                                    $.each(response, function(i, obj) {
                                        var selected = (value && value == obj.select_value) ?
                                            "selected" : "";
                                        $("<option " + selected + " value='" + obj.select_value +
                                                "'>" + obj.select_label + "</option>")
                                            .appendTo("#{{ $name }}");
                                    })
                                    $current.trigger('change');
                                }
                            });
                    } else {
                        $current.html("<option value=''>{{ $default }}");
                    }
                })

                $('#{{ $parent }}').trigger('change');
                $("input[name='{{ $parent }}']:checked").trigger("change");
                $("#{{ $name }}").trigger('change');
            })
        </script>
    @endpush
@endif

<div class='form-group {{ $header_group_class }} {{ $errors->first($name) ? 'has-error' : '' }}'
    id='form-group-{{ $name }}' style="{{ $form['style'] ?? '' }}">
    <label class='control-label col-sm-2'>{{ $form['label'] }}
        @if ($required)
            <span class='text-danger' title='{!! cbLang('this_field_is_required') !!}'>*</span>
        @endif
    </label>

    <div class="{{ $col_width ?: 'col-sm-10' }}">
        <select class='form-control' id="{{ $name }}" data-value='{{ $value }}' {{ $required }}
            {!! $placeholder !!} {{ $readonly }} {{ $disabled }} name="{{ $name }}">
            <option value=''>{{ $default }}</option>

            {{-- Perbaikan: Cek parent_select_raw agar tidak error --}}
            @if (!$parent_select_raw)
                @if (!empty($form['dataquery']))
                    @php $query = DB::select(DB::raw($form['dataquery'])); @endphp
                    @foreach ($query as $q)
                        <option {{ $value == $q->value ? 'selected' : '' }} value='{{ $q->value }}'>
                            {{ $q->label }}</option>
                    @endforeach
                @endif

                @if (!empty($form['dataenum']))
                    @php
                        $dataenum = is_array($form['dataenum']) ? $form['dataenum'] : explode(';', $form['dataenum']);
                    @endphp
                    @foreach ($dataenum as $d)
                        @php
                            if (strpos($d, '|') !== false) {
                                $draw = explode('|', $d);
                                $val = $draw[0];
                                $lab = $draw[1];
                            } else {
                                $val = $lab = $d;
                            }
                        @endphp
                        <option {{ $value == $val ? 'selected' : '' }} value='{{ $val }}'>
                            {{ $lab }}</option>
                    @endforeach
                @endif

                @if (!empty($form['datatable']))
                    @php
                        $raw = explode(',', $form['datatable']);
                        $table1 = $raw[0] ?? null;
                        $column1 = $raw[1] ?? null;
                        $table2 = $raw[2] ?? null;
                        $column2 = $raw[3] ?? null;
                        $table3 = $raw[4] ?? null;
                        $column3 = $raw[5] ?? null;

                        $selects_data = DB::table($table1)->select($table1 . '.id');

                        if (\Schema::hasColumn($table1, 'deleted_at')) {
                            $selects_data->whereNull($table1 . '.deleted_at');
                        }

                        if (!empty($form['datatable_where'])) {
                            $selects_data->whereRaw($form['datatable_where']);
                        }

                        $orderby_table = $table1;
                        $orderby_column = $column1;

                        if ($table2 && $column2) {
                            $selects_data->join($table2, $table2 . '.id', '=', $table1 . '.' . $column1);
                            $orderby_table = $table2;
                            $orderby_column = $column2;
                        }
                        if ($table3 && $column3) {
                            $selects_data->join($table3, $table3 . '.id', '=', $table2 . '.' . $column2);
                            $orderby_table = $table3;
                            $orderby_column = $column3;
                        }

                        if (!empty($form['datatable_format'])) {
                            $format = str_replace('&#039;', "'", $form['datatable_format']);
                            $selects_data->addSelect(DB::raw("CONCAT($format) as label"));
                            $order = explode(',', $form['datatable_order'] ?? '');
                            $selects_data->orderBy(
                                empty($order[0]) ? DB::raw("CONCAT($format)") : $order[0],
                                $order[1] ?? 'asc',
                            );
                        } else {
                            $selects_data->addSelect($orderby_table . '.' . $orderby_column . ' as label');
                            $selects_data->orderBy($orderby_table . '.' . $orderby_column, 'asc');
                        }
                    @endphp
                    @foreach ($selects_data->get() as $d)
                        <option {{ $value == $d->id ? 'selected' : '' }} value='{{ $d->id }}'>
                            {{ $d->label }}</option>
                    @endforeach
                @endif
            @endif
        </select>
        <div class="text-danger">{!! $errors->first($name) ? "<i class='fa fa-info-circle'></i> " . $errors->first($name) : '' !!}</div>
        <p class='help-block'>{{ $form['help'] ?? '' }}</p>
    </div>
</div>
