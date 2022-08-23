@extends('crudbooster::admin_template')

@section('content')

    <ul class="nav nav-tabs mb-1">
        <li class="nav-item">
            <a class="nav-link" href="{{ CRUDBooster::mainpath() }}"><i class='fa fa-file'></i> API Documentation</a>
        </li>
        <li class="nav-item">
            <a class="nav-link active" href="{{ CRUDBooster::mainpath('screet-key') }}"><i class='fa fa-key'></i> API Secret Key</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="{{ CRUDBooster::mainpath('generator') }}"><i class='fa fa-cog'></i> API Generator</a>
        </li>
    </ul>

    <div class="card card-light">

        <div class="card-body">

            <p><a title='Generate API Key' class='btn btn-primary' href='javascript:void(0)' onclick='generate_screet_key()'><i class='fa fa-key'></i> Generate
                    Secret Key</a></p>

            <table id='table-apikey' class='table table-striped table-bordered'>
                <thead>
                <tr>
                    <th width="3%">No</th>
                    <th>Screet Key</th>
                    <th width="10%">Hit</th>
                    <th width="10%">Status</th>
                    <th width="20%">Action</th>
                </tr>
                </thead>
                <tbody>
                <?php $no = 0;?>
                @foreach($apikeys as $row)
                    <tr>
                        <td>{{ ++$no }}</td>
                        <td>{{ $row->screetkey }}</td>
                        <td>{{ $row->hit }}</td>
                        <td>{!! ($row->status=='active')?"<span class='badge badge-success'>Active</span>":"<span class='badge badge-secondary'>Non Active</span>" !!}</td>
                        <td>
                            @if($row->status == 'active')
                                <a class="btn btn-sm btn-secondary" href='{{ CRUDBooster::mainpath("status-apikey?id=$row->id&status=0") }}'>Disable</a>
                            @else
                                <a class="btn btn-sm btn-success" href='{{ CRUDBooster::mainpath("status-apikey?id=$row->id&status=1") }}'>Enable</a>
                            @endif

                            <a class="btn btn-sm btn-danger" href='javascript:void(0)' onclick='deleteApi({{$row->id}})'>Delete</a>
                        </td>
                    </tr>
                @endforeach
                @if(count($apikeys)==0)
                    <tr class='no-screetkey'>
                        <td colspan='5' align="center">There is no secret key found, <a href='javascript:void(0)' onclick='generate_screet_key()'>Click here to
                                generate one</a></td>
                    </tr>
                @endif
                </tbody>
            </table>

            @push('bottom')
                <script>
                    var lastno = <?=$no?>;

                    function generate_screet_key() {
                        $.get("<?php echo route('ApiCustomControllerGetGenerateScreetKey')?>", function (resp) {
                            lastno += 1;
                            $('#table-apikey').append("<tr><td>" + lastno + "</td><td>" + resp.key + "</td><td>0</td><td><span class='label label-success'>Active</span></td><td>" +
                                "<a class='btn btn-xs btn-default' href='{{CRUDBooster::mainpath("status-apikey")}}?id=" + resp.id + "&status=0'>Non Active</a> <a class='btn btn-xs btn-danger' href='javascript:void(0)' onclick='deleteApi(" + resp.id + ")'>Delete</a> </td></tr>"
                            );
                            $('.no-screetkey').remove();
                            swal("Success!", "Your new screet key has been generated successfully", "success");
                        })
                    }

                    function deleteApi(id) {
                        swal({
                            title: "Are you sure ?",
                            text: "You will not be able to recover this data!",
                            type: "warning", showCancelButton: true, confirmButtonColor: "#DD6B55", confirmButtonText: "Yes, delete it!", closeOnConfirm: false
                        }, function () {
                            $.get("{{CRUDBooster::mainpath('delete-api-key')}}?id=" + id, function (resp) {
                                if (resp.status == 1) {
                                    swal("Success!", "The screet key has been deleted !", "success");
                                } else {
                                    swal("Upps!", "The screet key can't delete !", "warning");
                                }
                                location.href = document.location.href;
                            })
                        })
                    }
                </script>
            @endpush

        </div><!--END BODY-->
    </div><!--END BOX-->

@endsection