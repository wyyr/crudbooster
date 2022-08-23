<!-- navbar background color -->
<div class="navbar-bg"></div>

<!-- navbar -->
<nav class="navbar navbar-expand-lg main-navbar">
    <!-- toggle button -->
    <ul class="navbar-nav mr-3">
        <li><a href="#" data-toggle="sidebar" class="nav-link nav-link-lg"><i class="fas fa-bars"></i></a></li>
    </ul>

    <ul class="navbar-nav navbar-right ml-auto">
        <li class="dropdown dropdown-list-toggle notifications-menu">
            <a href="#" data-toggle="dropdown" class="nav-link notification-toggle nav-link-lg" title="Notifications">
                <i class="far fa-bell"></i>
            </a>
            <div class="dropdown-menu dropdown-list dropdown-menu-right">
                <div class="dropdown-header header">{{ cbLang("text_no_notification") }}</div>
                <div id="list_notifications" class="dropdown-list-content dropdown-list-icons"></div>
                <div class="dropdown-footer text-center">
                    <a href="{{ route('NotificationsControllerGetIndex') }}">{{ cbLang('text_view_all_notification') }} <i class="fas fa-chevron-right"></i></a>
                </div>
            </div>
        </li>
        <li class="dropdown"><a href="#" data-toggle="dropdown" class="nav-link dropdown-toggle nav-link-lg nav-link-user">
                <img alt="image" src="{{ CRUDBooster::myPhoto() }}" class="rounded-circle mr-1">
                <div class="d-sm-none d-lg-inline-block">Hi, {{ CRUDBooster::myName() }}</div></a>
            <div class="dropdown-menu dropdown-menu-right">
{{--                <div class="dropdown-title">Logged in 5 min ago</div>--}}
                <a href="{{ route('AdminCmsUsersControllerGetProfile') }}" class="dropdown-item has-icon">
                    <i class="far fa-user"></i> {{ cbLang('label_button_profile') }}
                </a>
                <div class="dropdown-divider"></div>
                <a href="javascript:void(0)" class="dropdown-item has-icon text-danger"
                    title="{{ cbLang('button_logout') }}"
                    onclick="Swal.fire({
                        title: 'Are you sure ?',
                        text: '',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Yes, Log out'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = '{{ route("getLogout") }}';
                        }
                    })">
                    <i class="fas fa-sign-out-alt"></i> {{ cbLang('button_logout') }}
                </a>
            </div>
        </li>
    </ul>
</nav>