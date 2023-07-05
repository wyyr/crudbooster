<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no" name="viewport">
    <title>{{ cbLang('page_title_forgot') }} : {{ session('appname') }}</title>
    <meta name="robots" content="noindex,nofollow">
    <link rel="shortcut icon"
          href="{{ CRUDBooster::getSetting('favicon')?asset(CRUDBooster::getSetting('favicon')):asset('crudbooster/assets/logo_crudbooster.png') }}">

    <!-- IMPORT STYLES -->
    @if (config('crudbooster.styles') && count(config('crudbooster.styles')))
        @foreach (config('crudbooster.styles') as $path)
            <link rel="stylesheet" type="text/css" href="{{ asset($path) }}">
        @endforeach
    @endif

    <style>
        .section-center {
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-top: -100px;
        }
    </style>
</head>

<body>
<div id="app">
    <section class="section section-center">
        <div class="container mt-5">
            <div class="row">
                <div class="col-12 col-sm-8 offset-sm-2 col-md-6 offset-md-3 col-lg-6 offset-lg-3 col-xl-4 offset-xl-4">
                    <div class="login-brand">
                        <img src="{{ CRUDBooster::getSetting("logo") ? \Illuminate\Support\Facades\Storage::disk(config('crudbooster.filesystem_driver'))->url(CRUDBooster::getSetting('logo')) : asset('crudbooster/assets/logo_crudbooster.png') }}"
                             alt="logo" style="max-width: 100%;max-height: 170px;">
                    </div>

                    <div class="card card-primary">
                        <div class="card-header">
                            <h4>Forgot Password</h4>
                        </div>

                        <div class="card-body">
                            @if (session('message') != '')
                                <div class='alert alert-warning'>
                                    {{ session('message') }}
                                </div>
                            @endif

                            <p class="text-muted">{{ cbLang('forgot_message') }}</p>
                            <form method="POST" action="{{ route('postForgot') }}">
                                @csrf

                                <div class="form-group">
                                    <label for="email">Email</label>
                                    <input id="email" type="email" class="form-control" name="email" tabindex="1" required autofocus>
                                </div>

                                <div class="form-group">
                                    <button type="submit" class="btn btn-primary btn-lg btn-block" tabindex="4">
                                        {{ cbLang('button_submit') }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="mt-5 text-muted text-center">
                        {{ cbLang('forgot_text_try_again') }} <a href="{{ route('getLogin') }}">{{ cbLang('click_here') }}</a>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

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
