@extends('crudbooster::admin_template')
@section('content')
    @push('head')
        <style>
            .select2-container--default .select2-selection--single {
                border-radius: 0px !important
            }

            .select2-container .select2-selection--single {
                height: 35px
            }

            .select2-container .select2-selection__rendered i {
                margin-top: 13px !important;
                margin-right: 13px;
            }
        </style>
    @endpush

    @push('bottom')
        <script>
            $(function() {
                function format(icon) {
                    var originalOption = icon.element;
                    var label = $(originalOption).text();
                    var val = $(originalOption).val();
                    if (!val) return label;
                    var $resp = $('<span><i style="margin-top:5px" class="float-right ' + $(originalOption).val() +
                        '"></i> ' + $(originalOption).data('label') + '</span>');
                    return $resp;
                }

                $('.select2').select2();

                $('#icon').select2({
                    width: "100%",
                    templateResult: format,
                    templateSelection: format
                });
            })
            $(function() {
                $('select[name=table]').change(function() {
                    var v = $(this).val().replace(".", "_");
                    $.get("{{ CRUDBooster::mainpath('check-slug') }}/" + v, function(resp) {
                        if (resp.total == 0) {
                            $('input[name=path]').val(v);
                        } else {
                            v = v + resp.lastid;
                            $('input[name=path]').val(v);
                        }
                    })

                })
            })
        </script>
    @endpush

    <ul class="nav nav-tabs nav-primary mb-1">
        @if ($id)
            <li class="nav-item" role="presentation">
                <a class="nav-link active" href="{{ Route('ModulControllerGetStep1') . '/' . $id }}">
                    <i class="fa fa-info"></i> Step 1 - Module Information
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link" href="{{ Route('ModulControllerGetStep2') . '/' . $id }}">
                    <i class="fa fa-table"></i> Step 2 - Table Display
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link" href="{{ Route('ModulControllerGetStep3') . '/' . $id }}">
                    <i class="fa fa-plus-square"></i> Step 3 - Form Display
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link" href="{{ Route('ModulControllerGetStep4') . '/' . $id }}">
                    <i class="fa fa-wrench"></i> Step 4 - Configuration
                </a>
            </li>
        @else
            <li class="nav-item" role="presentation">
                <a class="nav-link active" href="#"><i class='fa fa-info'></i> Step 1 - Module Information</a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link" href="#"><i class='fa fa-table'></i> Step 2 - Table Display</a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link" href="#"><i class='fa fa-plus-square'></i> Step 3 - Form Display</a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link" href="#"><i class='fa fa-wrench'></i> Step 4 - Configuration</a>
            </li>
        @endif
    </ul>

    <form method="post" action="{{ Route('ModulControllerPostStep2') }}">
        <div class="card card-light">
            <div class="card-header">
                <h6 class="card-title">Module Information</h6>
            </div>
            <div class="card-body">
                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                <input type="hidden" name="id" value="{{ $row->id ?? '' }}">
                <div class="form-group">
                    <label for="table">Table</label>
                    <select name="table" id="table" required class="select2 form-control">
                        <option value="">{{ cbLang('text_prefix_option') }} Table</option>
                        @foreach ($tables_list as $table)
                            <option {{ isset($row) && $table == $row->table_name ? 'selected' : '' }}
                                value="{{ $table }}">
                                {{ $table }}
                            </option>
                        @endforeach
                    </select>
                    <div class="help-block">
                        Do not use cms_* as prefix on your tables name
                    </div>
                </div>
                <div class="form-group">
                    <label for="name">Module Name</label>
                    <input type="text" id="name" class="form-control" required name="name"
                        value="{{ $row->name ?? '' }}">
                </div>
                <div class="form-group">
                    <label for="icon">Icon</label>
                    <select name="icon" id="icon" required class="select2 form-control">
                        @foreach ($fontawesome as $f)
                            <option value="fas fa-{{ $f }}"
                                {{ isset($row) && $row->icon == 'fas fa-' . $f ? 'selected' : '' }}
                                data-label='{{ $f }}'>
                                {{ $f }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label for="slug">Module Slug</label>
                    <input id="slug" type="text" class="form-control" required name="path"
                        value="{{ $row->path ?? '' }}">
                    <div class="help-block">Please alpha numeric only, without space instead _ and or special character
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <input checked type="checkbox" name="create_menu" value="1" /> Also create menu for this module
                <a href="#" title="If you check this, we will create the menu for this module">(?)</a>

                <div class="float-right">
                    <a class="btn btn-outline-secondary mr-2"
                        href="{{ Route('ModulControllerGetIndex') }}">{{ cbLang('button_back') }}</a>
                    <input type="submit" class="btn btn-primary" value="Step 2 &raquo;">
                </div>
            </div>
        </div>
    </form>
@endsection
