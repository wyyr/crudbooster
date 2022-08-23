@extends('crudbooster::admin_template')

@section('content')

    <!-- Custom Tabs -->
    <ul class="nav nav-tabs">
        <li class="nav-item">
            <a class="nav-link active" href="{{ CRUDBooster::mainpath('documentation') }}"><i class='fa fa-file'></i> API Documentation</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="{{ CRUDBooster::mainpath('screet-key') }}"><i class='fa fa-key'></i> API Screet Key</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="{{ CRUDBooster::mainpath('generator') }}"><i class='fa fa-cog'></i> API Generator</a>
        </li>
    </ul>

    <div class="card card0light">
        <div class="card-header"><h3 class='box-title'>API Documentation</h3></div>
        <div class="card-body">
            @include('crudbooster::api_documentation')
        </div>
    </div>

    <!-- nav-tabs-custom -->

@endsection