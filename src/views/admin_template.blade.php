<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{{ ($page_title)?get_setting('appname').': '.strip_tags($page_title):"Admin Area" }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}"/>
    <meta name='generator' content='CRUDBooster {{ \crocodicstudio\crudbooster\commands\CrudboosterVersionCommand::$version }}'/>
    <meta name='robots' content='noindex,nofollow'/>
    <link rel="shortcut icon"
          href="{{ CRUDBooster::getSetting('favicon')?asset(CRUDBooster::getSetting('favicon')):asset('crudbooster/assets/logo_crudbooster.png') }}">
    <meta content='width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no' name='viewport'>

    <!-- IMPORT STYLES -->
    @if (config('crudbooster.styles') && count(config('crudbooster.styles')))
        @foreach (config('crudbooster.styles') as $path)
            <link rel="stylesheet" type="text/css" href="{{ asset($path) }}">
        @endforeach
    @endif

    <!-- support rtl-->
    @if (in_array(App::getLocale(), ['ar', 'fa']))
        <link rel="stylesheet" href="//cdn.rawgit.com/morteza/bootstrap-rtl/v3.3.4/dist/css/bootstrap-rtl.min.css">
        <link href="{{ asset("crudbooster/assets/rtl.css")}}" rel="stylesheet" type="text/css"/>
    @endif

    <!-- load css -->
    <style type="text/css">
        @if($style_css)
            {!! $style_css !!}
        @endif
    </style>
    @if($load_css)
        @foreach($load_css as $css)
            <link href="{{$css}}" rel="stylesheet" type="text/css"/>
        @endforeach
    @endif

    <style type="text/css">
        .dropdown-menu-action {
            left: -130%;
        }

        .btn-group-action .btn-action {
            cursor: default
        }

        #box-header-module {
            box-shadow: 10px 10px 10px #dddddd;
        }

        .sub-module-tab li {
            background: #F9F9F9;
            cursor: pointer;
        }

        .sub-module-tab li.active {
            background: #ffffff;
            box-shadow: 0px -5px 10px #cccccc
        }

        .nav-tabs > li.active > a, .nav-tabs > li.active > a:focus, .nav-tabs > li.active > a:hover {
            border: none;
        }

        .nav-tabs > li > a {
            border: none;
        }

        .breadcrumb {
            margin: 0 0 0 0;
            padding: 0 0 0 0;
        }

        .form-group > label:first-child {
            display: block
        }

        #table_dashboard.table-bordered, #table_dashboard.table-bordered thead tr th, #table_dashboard.table-bordered tbody tr td {
            border: 1px solid #bbbbbb !important;
        }
    </style>

    @stack('head')
</head>
<body class="@php echo (session('theme_color'))?:'skin-blue'; echo ' '; echo config('crudbooster.ADMIN_LAYOUT'); @endphp {{($sidebar_mode)?:''}}">
<div id="app" class="wrapper">

    <div class="main-wrapper main-wrapper-1">
        <!-- Header -->
        @include('crudbooster::partials.header')

        <!-- Sidebar -->
        @include('crudbooster::partials.sidebar')

        <!-- Content Wrapper. Contains page content -->
        <div class="main-content">
            <section class="section">
                <div class="section-header">
                    <?php
                        $module = CRUDBooster::getCurrentModule();
                    ?>
                    @if($module)
                    <h1>
                        <!--Now you can define $page_icon alongside $page_tite for custom forms to follow CRUDBooster theme style -->
                        <i class="{!! ($page_icon) ?: $module->icon !!}" style="font-size: 18px;"></i> {!! ucwords(($page_title) ?: $module->name) !!} &nbsp;&nbsp;

                        <!--START BUTTON -->

                        @if(CRUDBooster::getCurrentMethod() == 'getIndex')
                            @if($button_show)
                                <a href="{{ CRUDBooster::mainpath().'?'.http_build_query(Request::all()) }}" id='btn_show_data' class="btn btn-sm btn-primary"
                                   title="{{cbLang('action_show_data')}}">
                                    <i class="fa fa-table"></i> {{cbLang('action_show_data')}}
                                </a>
                            @endif

                            @if($button_add && CRUDBooster::isCreate())
                                <a href="{{ CRUDBooster::mainpath('add').'?return_url='.urlencode(Request::fullUrl()).'&parent_id='.g('parent_id').'&parent_field='.$parent_field }}"
                                   id='btn_add_new_data' class="btn btn-sm btn-success" title="{{cbLang('action_add_data')}}">
                                    <i class="fa fa-plus-circle"></i> {{cbLang('action_add_data')}}
                                </a>
                            @endif
                        @endif


                        @if($button_export && CRUDBooster::getCurrentMethod() == 'getIndex')
                            <a href="javascript:void(0)" id='btn_export_data' data-url-parameter='{{$build_query}}' title='Export Data'
                               class="btn btn-sm btn-primary btn-export-data">
                                <i class="fa fa-upload"></i> {{cbLang("button_export")}}
                            </a>
                        @endif

                        @if($button_import && CRUDBooster::getCurrentMethod() == 'getIndex')
                            <a href="{{ CRUDBooster::mainpath('import-data') }}" id='btn_import_data' data-url-parameter='{{$build_query}}' title='Import Data'
                               class="btn btn-sm btn-primary btn-import-data">
                                <i class="fa fa-download"></i> {{cbLang("button_import")}}
                            </a>
                        @endif

                        <!-- Add Action -->
                        @if(!empty($index_button))

                            @foreach($index_button as $ib)
                                <a href='{{$ib["url"]}}' id='{{str_slug($ib["label"])}}' class='btn {{($ib['color'])?'btn-'.$ib['color']:'btn-primary'}} btn-sm'
                                   @if($ib['onClick']) onClick='return {{$ib["onClick"]}}' @endif
                                   @if($ib['onMouseOver']) onMouseOver='return {{$ib["onMouseOver"]}}' @endif
                                   @if($ib['onMouseOut']) onMouseOut='return {{$ib["onMouseOut"]}}' @endif
                                   @if($ib['onKeyDown']) onKeyDown='return {{$ib["onKeyDown"]}}' @endif
                                   @if($ib['onLoad']) onLoad='return {{$ib["onLoad"]}}' @endif
                                >
                                    <i class='{{$ib["icon"]}}'></i> {{$ib["label"]}}
                                </a>
                        @endforeach
                    @endif
                    <!-- END BUTTON -->
                    </h1>

                    <div class="section-header-breadcrumb">
                        <div class="breadcrumb-item">
                            <a href="{{ CRUDBooster::adminPath() }}">{{ cbLang('home') }}</a>
                        </div>
                        <div class="breadcrumb-item active">{{ $module->name }}</div>
                    </div>
                    @else
                    <h1>{{ session('appname') }}
                        <small> {{ cbLang('text_dashboard') }} </small>
                    </h1>
                    @endif
                </div>

                <!-- Main content -->
                <div class="section-body">
                    @if(@$alerts)
                        @foreach(@$alerts as $alert)
                            <div class="alert alert-{{$alert["type"]}}">
                                {!! $alert['message'] !!}
                            </div>
                        @endforeach
                    @endif

                    @if (session('message') != '')
                        <div class="alert alert-{{ session("message_type") }} alert-dismissible show fade">
                            <div class="alert-body">
                                <button class="close" data-dismiss="alert">
                                    <span>&times;</span>
                                </button>
                                <h5><i class="icon fa fa-info"></i> {{ cbLang('alert_' . session('message_type')) }}</h5>
                                {!! session('message') !!}
                            </div>
                        </div>
                    @endif

                    <!-- Your Page Content Here -->
                    @yield('content')
                </div>
            </section>
        </div>

        <!-- Footer -->
        @include('crudbooster::partials.footer')
    </div>

</div>


@include('crudbooster::admin_template_plugins')

<!-- load js -->
@if($load_js)
    @foreach($load_js as $js)
        <script src="{{ $js }}"></script>
    @endforeach
@endif
<script type="text/javascript">
    var site_url = "{{ url('/') }}";
    @if($script_js)
        {!! $script_js !!}
    @endif
</script>

@stack('bottom')

<!-- Optionally, you can add Slimscroll and FastClick plugins.
      Both of these plugins are recommended to enhance the
      user experience -->
</body>
</html>
