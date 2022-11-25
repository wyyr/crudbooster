<div class="main-sidebar sidebar-style-2">
    <aside id="sidebar-wrapper">
        <div class="sidebar-brand">
            <a href="{{ url(config('crudbooster.ADMIN_PATH')) }}" title="{{ session('appname') }}">
                {{ CRUDBooster::getSetting('appname') }}
            </a>
        </div>
        <div class="sidebar-brand sidebar-brand-sm">
            <a href="{{ url(config('crudbooster.ADMIN_PATH')) }}" title="{{ session('appname') }}">
                {{ substr(CRUDBooster::getSetting('appname'), 0, 2) }}
            </a>
        </div>

        <!-- Sidebar Menu -->
        <ul class="sidebar-menu">
            <li class="menu-header">{{ cbLang('menu_navigation') }}</li>
            <!-- Optionally, you can add icons to the links -->

            <?php $dashboard = CRUDBooster::sidebarDashboard();?>
            @if($dashboard)
                <li data-id="{{ $dashboard->id }}" class="{{ (request()->is(config('crudbooster.ADMIN_PATH'))) ? 'active' : '' }}">
                    <a href="{{ CRUDBooster::adminPath() }}" class="{{ ($dashboard->color) ? 'text-' . $dashboard->color : '' }}">
                        <i class="fas fa-tachometer-alt"></i> <span>{{ cbLang('text_dashboard') }}</span>
                    </a>
                </li>
            @endif

            @foreach(CRUDBooster::sidebarMenu() as $menu)
                <li data-id="{{ $menu->id }}" class="{{ (!empty($menu->children)) ? 'dropdown' : '' }} {{ (request()->is($menu->url_path.'*')) ? 'active' : '' }}">
                    <a href='{{ ($menu->is_broken)?"javascript:alert('".cbLang('controller_route_404')."')":$menu->url }}'
                       class="{{ ($menu->color) ? 'text-' . $menu->color : ''}} {{ !empty($menu->children) ? 'has-dropdown' : '' }}">
                        <i class="{{ $menu->icon }} {{ ($menu->color) ? 'text-' . $menu->color : '' }}"></i> <span>{{ $menu->name }}</span>
                    </a>
                    @if(!empty($menu->children))
                    <ul class="dropdown-menu">
                        @foreach($menu->children as $child)
                            <li data-id="{{ $child->id }}" class="{{(request()->is($child->url_path .= !Str::endsWith(Request::decodedPath(), $child->url_path) ? '/*' : ''))?'active':''}}">
                                <a href='{{ ($child->is_broken)?"javascript:alert('".cbLang('controller_route_404')."')":$child->url}}'
                                   class="{{ ($child->color) ? 'text-' . $child->color : '' }}">
                                    <i class="{{ $child->icon }}"></i> <span>{{ $child->name }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                    @endif
                </li>
            @endforeach

            @if(CRUDBooster::isSuperadmin())
                <li class="menu-header">{{ cbLang('SUPERADMIN') }}</li>
                <li class="dropdown">
                    <a href="#" class="nav-link has-dropdown">
                        <i class="fas fa-key"></i> <span>{{ cbLang('Privileges_Roles') }}</span>
                    </a>
                    <ul class="dropdown-menu">
                        <li class="{{ (request()->is(config('crudbooster.ADMIN_PATH') . '/privileges/add*')) ? 'active' : '' }}">
                            <a class="nav-link" href="{{ Route('PrivilegesControllerGetAdd') }}">
                                <i class="fas fa-plus"></i> <span>{{ cbLang('Add_New_Privilege') }}</span>
                            </a>
                        </li>
                        <li class="{{ (request()->is(config('crudbooster.ADMIN_PATH') . '/privileges')) ? 'active' : '' }}">
                            <a class="nav-link" href="{{ Route('PrivilegesControllerGetIndex') }}">
                                <i class="fas fa-bars"></i> <span>{{ cbLang('List_Privilege') }}</span>
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="dropdown">
                    <a href="#" class="nav-link has-dropdown">
                        <i class="fas fa-users"></i> <span>{{ cbLang('Users_Management') }}</span>
                    </a>
                    <ul class="dropdown-menu">
                        <li class="{{ (request()->is(config('crudbooster.ADMIN_PATH') . '/users/add*')) ? 'active' : '' }}">
                            <a href="{{ Route('AdminCmsUsersControllerGetAdd') }}">
                                <i class="fas fa-plus"></i> <span>{{ cbLang('add_user') }}</span>
                            </a>
                        </li>
                        <li class="{{ (request()->is(config('crudbooster.ADMIN_PATH') . '/users')) ? 'active' : '' }}">
                            <a href="{{ Route('AdminCmsUsersControllerGetIndex') }}">
                                <i class="fas fa-bars"></i> <span>{{ cbLang('List_users') }}</span>
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="{{ (request()->is(config('crudbooster.ADMIN_PATH') . '/menu_management*')) ? 'active' : '' }}">
                    <a href="{{ Route('MenusControllerGetIndex') }}">
                        <i class="fas fa-bars"></i> <span>{{ cbLang('Menu_Management') }}</span>
                    </a>
                </li>
                <li class="dropdown">
                    <a href="#" class="nav-link has-dropdown">
                        <i class="fas fa-wrench"></i> <span>{{ cbLang('settings') }}</span>
                    </a>
                    <ul class="dropdown-menu">
                        <li class="{{ (request()->is(config('crudbooster.ADMIN_PATH') . '/settings/add*')) ? 'active' : '' }}">
                            <a href="{{ route('SettingsControllerGetAdd') }}">
                                <i class="fas fa-plus"></i> <span>{{ cbLang('Add_New_Setting') }}</span>
                            </a>
                        </li>

                        <?php
                        $groupSetting = DB::table('cms_settings')->groupby('group_setting')->pluck('group_setting');
                        foreach($groupSetting as $gs):
                        ?>
                        <li class="<?=($gs == Request::get('group')) ? 'active' : ''?>"><a
                                    href='{{route("SettingsControllerGetShow")}}?group={{urlencode($gs)}}&m=0'><i class='fa fa-wrench'></i>
                                <span>{{$gs}}</span></a></li>
                        <?php endforeach;?>
                    </ul>
                </li>
                <li class="dropdown">
                    <a href="#" class="nav-link has-dropdown">
                        <i class="fas fa-th"></i> <span>{{ cbLang('Module_Generator') }}</span>
                    </a>
                    <ul class="dropdown-menu">
                        <li class="{{ (request()->is(config('crudbooster.ADMIN_PATH') . '/module_generator/step1')) ? 'active' : '' }}">
                            <a href="{{ Route('ModulsControllerGetStep1') }}">
                                <i class="fas fa-plus"></i> <span>{{ cbLang('Add_New_Module') }}</span>
                            </a>
                        </li>
                        <li class="{{ (request()->is(config('crudbooster.ADMIN_PATH') . '/module_generator')) ? 'active' : '' }}">
                            <a href="{{ Route('ModulsControllerGetIndex') }}">
                                <i class="fas fa-bars"></i> <span>{{ cbLang('List_Module') }}</span>
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="dropdown">
                    <a href="#" class="nav-link has-dropdown">
                        <i class="fas fa-tachometer-alt"></i> <span>{{ cbLang('Statistic_Builder') }}</span>
                    </a>
                    <ul class="dropdown-menu">
                        <li class="{{ (request()->is(config('crudbooster.ADMIN_PATH') . '/statistic_builder/add')) ? 'active' : '' }}">
                            <a href="{{ Route('StatisticBuilderControllerGetAdd') }}">
                                <i class="fas fa-plus"></i> <span>{{ cbLang('Add_New_Statistic') }}</span>
                            </a>
                        </li>
                        <li class="{{ (request()->is(config('crudbooster.ADMIN_PATH') . '/statistic_builder')) ? 'active' : '' }}">
                            <a href="{{ Route('StatisticBuilderControllerGetIndex') }}">
                                <i class="fas fa-bars"></i> <span>{{ cbLang('List_Statistic') }}</span>
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="dropdown">
                    <a href="#" class="nav-link has-dropdown">
                        <i class="fas fa-fire"></i> <span>{{ cbLang('API_Generator') }}</span>
                    </a>
                    <ul class="dropdown-menu">
                        <li class="{{ (request()->is(config('crudbooster.ADMIN_PATH') . '/api_generator/generator*')) ? 'active' : '' }}">
                            <a href="{{ Route('ApiCustomControllerGetGenerator') }}">
                                <i class="fas fa-plus"></i> {{ cbLang('Add_New_API') }}
                            </a>
                        </li>
                        <li class="{{ (request()->is(config('crudbooster.ADMIN_PATH') . '/api_generator')) ? 'active' : '' }}">
                            <a href="{{ Route('ApiCustomControllerGetIndex') }}">
                                <i class="fas fa-bars"></i> {{ cbLang('list_API') }}
                            </a>
                        </li>
                        <li class="{{ (request()->is(config('crudbooster.ADMIN_PATH') . '/api_generator/screet-key*')) ? 'active' : '' }}">
                            <a href="{{ Route('ApiCustomControllerGetScreetKey') }}">
                                <i class="fas fa-bars"></i> {{ cbLang('Generate_Screet_Key') }}
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="dropdown">
                    <a href="#" class="nav-link has-dropdown">
                        <i class="fas fa-envelope"></i> <span>{{ cbLang('Email_Templates') }}</span>
                    </a>
                    <ul class="dropdown-menu">
                        <li class="{{ (request()->is(config('crudbooster.ADMIN_PATH') . '/email_templates/add*')) ? 'active' : '' }}">
                            <a href="{{ Route('EmailTemplatesControllerGetAdd') }}">
                                <i class="fas fa-plus"></i> <span>{{ cbLang('Add_New_Email') }}</span>
                            </a>
                        </li>
                        <li class="{{ (request()->is(config('crudbooster.ADMIN_PATH') . '/email_templates')) ? 'active' : '' }}">
                            <a href="{{ Route('EmailTemplatesControllerGetIndex') }}">
                                <i class="fas fa-bars"></i> <span>{{ cbLang('List_Email_Template') }}</span>
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="{{ (request()->is(config('crudbooster.ADMIN_PATH') . '/logs*')) ? 'active' : '' }}">
                    <a href="{{ Route('LogsControllerGetIndex') }}">
                        <i class="fas fa-flag"></i> <span>{{ cbLang('Log_User_Access') }}</span>
                    </a>
                </li>
            @endif
        </ul>
    </aside>
</div>