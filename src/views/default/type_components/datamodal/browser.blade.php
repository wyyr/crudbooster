<!-- Bootstrap 3.3.2 -->
<link href="{{ asset("crudbooster/assets/adminlte/bootstrap/css/bootstrap.min.css") }}" rel="stylesheet" type="text/css"/>
<!-- Font Awesome Icons -->
<link href="{{asset("crudbooster/assets/adminlte/font-awesome/css")}}/font-awesome.min.css" rel="stylesheet" type="text/css"/>
<!-- Ionicons -->
<link href="{{asset("crudbooster/ionic/css/ionicons.min.css")}}" rel="stylesheet" type="text/css"/>
<!-- Theme style -->
<link href="{{ asset("crudbooster/assets/adminlte/dist/css/AdminLTE.min.css")}}" rel="stylesheet" type="text/css"/>
<link href="{{ asset("crudbooster/assets/adminlte/dist/css/skins/_all-skins.min.css")}}" rel="stylesheet" type="text/css"/>

@include('crudbooster::admin_template_plugins')

<?php
$name = $name_column ?? Request::get('name_column');
$coloms_alias = explode(',', 'ID,'.($columns_name_alias ?? Request::get('columns_name_alias')));
if (count($coloms_alias) < 2 && isset($columns)) {
    $coloms_alias = $columns;
}
?>
<form method='get' action="">
    {!! CRUDBooster::getUrlParameters(['q']) !!}
    <input type="text" placeholder="{{cbLang('datamodal_search_and_enter')}}" name="q" title="{{cbLang('datamodal_enter_to_search')}}"
           value="{{Request::get('q')}}" class="form-control">
</form>

<table id='table_dashboard' class='table table-striped table-bordered table-condensed' style="margin-bottom: 0px">
    <thead>
    @foreach($coloms_alias as $col)
        <th>{{ $col }}</th>
    @endforeach
    <th width="5%">{{cbLang('datamodal_select')}}</th>
    </thead>
    <tbody>
    @foreach($result as $row)
        <tr>
            @foreach($columns as $col)
                @php
                    $value = (string) ($row->$col ?? '');
                    $img_extension = ['jpg', 'jpeg', 'png', 'gif', 'bmp'];
                    $ext = pathinfo($value, PATHINFO_EXTENSION);
                @endphp
                @if ($ext && in_array(strtolower($ext), $img_extension, true))
                    <td><a href="{{ asset($value) }}" data-lightbox="roadtrip"><img src="{{ asset($value) }}" width="50px" height="30px"/></a></td>
                @else
                    <td>{{ Str::of(strip_tags($value))->limit(50) }}</td>
                @endif
            @endforeach
            @php
                $select_data_result = [
                    'datamodal_id' => $row->id,
                    'datamodal_label' => $row->{$columns[1]} ?: $row->id,
                ];
                $select_data = array_filter(array_map('trim', explode(',', $select_to ?? Request::get('select_to', ''))));
                foreach ($select_data as $mapping) {
                    [$field_name, $target_field_name] = array_pad(explode(':', $mapping, 2), 2, null);
                    if (
                        $field_name
                        && $target_field_name
                        && in_array($field_name, $columns, true)
                        && preg_match('/^[A-Za-z_][A-Za-z0-9_-]*$/', $target_field_name) === 1
                    ) {
                        $select_data_result[$target_field_name] = $row->$field_name;
                    }
                }
            @endphp
            <td><a class='btn btn-primary' href='javascript:void(0)' onclick='parent.selectAdditionalData{{$name}}({!! json_encode($select_data_result, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!})'><i
                            class='fa fa-check-circle'></i> {{cbLang('datamodal_select')}}</a></td>
        </tr>
    @endforeach
    </tbody>
</table>
<div align="center">{!! str_replace("/?","?",$result->appends(Request::all())->render()) !!}</div>
