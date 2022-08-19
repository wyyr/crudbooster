<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>::LOCKSCREEN::</title>
    <meta name='generator' content='CRUDBooster'/>
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

    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
    <script src="https://oss.maxcdn.com/libs/respond.js/1.3.0/respond.min.js"></script>
    <![endif]-->

    <style type="text/css">
        .lockscreen {
            background: {{ CRUDBooster::getSetting("login_background_color")?:'#dddddd'}} url('{{ CRUDBooster::getSetting("login_background_image")?asset(CRUDBooster::getSetting("login_background_image")):asset('crudbooster/assets/bg_blur3.jpg') }}');
            color: {{ CRUDBooster::getSetting("login_font_color")?:'#ffffff' }}  !important;
            background-repeat: no-repeat;
            background-position: center;
            background-size: cover;
        }
    </style>

</head>
<body class="lockscreen">
<!-- Automatic element centering -->
<div class="lockscreen-wrapper">
    <div class="lockscreen-logo">
        <a href="{{url('/')}}">
            <img title='{!!($appname == 'CRUDBooster')?"<b>CRUD</b>Booster":$appname!!}'
                 src='{{ CRUDBooster::getSetting("logo")?asset(CRUDBooster::getSetting('logo')):asset('crudbooster/assets/logo_crudbooster.png') }}'
                 style='max-width: 100%;max-height:170px'/>
        </a>
    </div>
    <!-- User name -->
    <div class="lockscreen-name">{{Session::get('admin_name')}}</div>

    <!-- START LOCK SCREEN ITEM -->
    <div class="lockscreen-item">
        <!-- lockscreen image -->
        <div class="lockscreen-image">
            <img src="{{ (Session::get('admin_photo'))?:asset("assets/adminlte/dist/img/user2-160x160.jpg") }}" alt="user image"/>
        </div>
        <!-- /.lockscreen-image -->

        <!-- lockscreen credentials (contains the form) -->
        <form class="lockscreen-credentials" method='post' action="{{url(config('crudbooster.ADMIN_PATH').'/unlock-screen')}}">
            <input type="hidden" name="_token" value="{{ csrf_token() }}"/>
            <div class="input-group">
                <input type="password" class="form-control" required name='password' placeholder="password"/>
                <div class="input-group-btn">
                    <button class="btn"><i class="fa fa-arrow-right text-muted"></i></button>
                </div>
            </div>
        </form><!-- /.lockscreen credentials -->

    </div><!-- /.lockscreen-item -->
    <div class="text-center">
        {{cbLang("text_enter_the_password")}}
    </div>
    <div class='text-center'>
        <a href="{{route("getLogout")}}">{{cbLang('text_or_sign_in')}}</a>
    </div>
    <div class='lockscreen-footer text-center'>
        Copyright &copy; {{date("Y")}}<br>
        All rights reserved
    </div>
</div><!-- /.center -->

<script>
    var ASSET_URL = "{{ asset('/') }}";
    var APP_NAME = "{{ Session::get('appname') }}";
    var ADMIN_PATH = '{{ url(config("crudbooster.ADMIN_PATH")) }}';
    var NOTIFICATION_JSON = "{{ route('NotificationsControllerGetLatestJson') }}";
    var NOTIFICATION_INDEX = "{{ route('NotificationsControllerGetIndex') }}";

    var NOTIFICATION_YOU_HAVE = "{{ cbLang('notification_you_have') }}";
    var NOTIFICATION_NOTIFICATIONS = "{{ cbLang('notification_notification') }}";
    var NOTIFICATION_NEW = "{{ cbLang('notification_new') }}";
</script>

<!-- IMPORT JS SCRIPTS -->
@if (config('crudbooster.scripts') && count(config('crudbooster.scripts')))
    @foreach (config('crudbooster.scripts') as $path)
        <script type="text/javascript" src="{{ asset($path) }}"></script>
    @endforeach
@endif
</body>
</html>