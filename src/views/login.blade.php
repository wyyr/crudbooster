<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no" name="viewport">
    <title>{{ cbLang('page_title_login') }} : {{ session('appname') }}</title>
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
                        <div class="card-body">
                            @if (session('message') != '')
                                <div class='alert alert-warning'>
                                    {{ session('message') }}
                                </div>
                            @endif

                            <p class="text-muted">{{ cbLang('login_message') }}</p>
                            <form method="POST" action="{{ route('postLogin') }}" class="needs-validation" novalidate="">
                                @csrf

                                <div class="form-group">
                                    <label for="email">Email</label>
                                    <input id="email" type="email" class="form-control" name="email" tabindex="1" required autofocus>
                                    <div class="invalid-feedback">
                                        Please fill in your email
                                    </div>
                                </div>

                                <div class="form-group">
                                    <div class="d-block">
                                        <label for="password" class="control-label">Password</label>
                                        <div class="float-right">
                                            <a href="{{ route('getForgot') }}" class="text-small">
                                                Forgot Password?
                                            </a>
                                        </div>
                                    </div>
                                    <input id="password" type="password" class="form-control" name="password" tabindex="2" required>
                                    <div class="invalid-feedback">
                                        please fill in your password
                                    </div>
                                </div>

                                <div class="form-group">
                                    <button type="submit" class="btn btn-primary btn-lg btn-block" tabindex="4">
                                        {{ cbLang('button_sign_in') }}
                                    </button>
                                </div>
                            </form>
                            @if(!empty(config('services.google')) || !empty(config('services.facebook')))
                                <div class="text-center mt-4 mb-3">
                                    <div class="text-job text-muted">Login With Social</div>
                                </div>
                                <div class="row justify-content-center sm-gutters">
                                    @if(!empty(config('services.google')))
                                        <div class="col-6">
                                            <a class="btn btn-block btn-social btn-google">
                                                <span class="fab fa-google"></span> Google
                                            </a>
                                        </div>
                                    @endif
                                    @if(!empty(config('services.facebook')))
                                        <div class="col-6">
                                            <a class="btn btn-block btn-social btn-facebook">
                                                <span class="fab fa-facebook"></span> Facebook
                                            </a>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>
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
