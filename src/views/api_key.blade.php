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
                            <form method="POST" action="{{ CRUDBooster::mainpath('status-apikey') }}" class="d-inline">
                                @csrf
                                <input type="hidden" name="id" value="{{ $row->id }}">
                                <input type="hidden" name="status" value="{{ $row->status == 'active' ? 0 : 1 }}">
                                <button class="btn btn-sm {{ $row->status == 'active' ? 'btn-secondary' : 'btn-success' }}" type="submit">
                                    {{ $row->status == 'active' ? 'Disable' : 'Enable' }}
                                </button>
                            </form>

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
                    $.ajaxSetup({
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        }
                    });

                    function generate_screet_key() {
                        $.post("<?php echo route('ApiCustomControllerPostGenerateScreetKey')?>", function (resp) {
                            lastno += 1;
                            var statusForm = "<form method='POST' action='{{CRUDBooster::mainpath('status-apikey')}}' class='d-inline'>" +
                                "<input type='hidden' name='_token' value='{{ csrf_token() }}'>" +
                                "<input type='hidden' name='id' value='" + resp.id + "'>" +
                                "<input type='hidden' name='status' value='0'>" +
                                "<button class='btn btn-xs btn-default' type='submit'>Non Active</button></form>";
                            $('#table-apikey').append("<tr><td>" + lastno + "</td><td>" + resp.key + "</td><td>0</td><td><span class='label label-success'>Active</span></td><td>" +
                                statusForm + " <a class='btn btn-xs btn-danger' href='javascript:void(0)' onclick='deleteApi(" + resp.id + ")'>Delete</a> </td></tr>"
                            );
                            $('.no-screetkey').remove();
                            Swal.fire("Success!", "Your new screet key has been generated successfully", "success");
                        })
                    }

                    function deleteApi(id) {
                        Swal.fire({
                            title: "Are you sure ?",
                            text: "You will not be able to recover this data!",
                            icon: "warning",
                            showCancelButton: true,
                            confirmButtonColor: "#DD6B55",
                            confirmButtonText: "Yes, delete it!"
                        }).then(function (result) {
                            if (!result.isConfirmed) {
                                return;
                            }

                            $.post("{{CRUDBooster::mainpath('delete-api-key')}}", {id: id}, function (resp) {
                                if (resp.status == 1) {
                                    Swal.fire("Success!", "The screet key has been deleted !", "success");
                                } else {
                                    Swal.fire("Upps!", "The screet key can't delete !", "warning");
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