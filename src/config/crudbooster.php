<?php

return [

    'ADMIN_PATH' => 'admin',

    /*
        To Allowed Specific User Agent Only
        E.g : ['Android','OkHttp','Mozilla','Mac']
    */
    'API_USER_AGENT_ALLOWED' => [],

    'USER_TABLE' => 'cms_users',

    'IMAGE_FIELDS_CANDIDATE' => 'image,picture,photo,photos,foto,gambar,thumbnail',

    'PASSWORD_FIELDS_CANDIDATE' => 'password,pass,pwd,passwrd,sandi,pin',

    'DATE_FIELDS_CANDIDATE' => 'date,tanggal,tgl,created_at,updated_at,deleted_at',

    'EMAIL_FIELDS_CANDIDATE' => 'email,mail,email_address',

    'PHONE_FIELDS_CANDIDATE' => 'phone,phonenumber,phone_number,telp,hp,no_hp,no_telp',

    'NAME_FIELDS_CANDIDATE' => 'name,nama,person_name,person,fullname,full_name,nickname,nick,nick_name,title,judul,content',

    'URL_FIELDS_CANDIDATE' => 'url,link',

    'UPLOAD_TYPES' => 'jpg,png,jpeg,gif,bmp,pdf,xls,xlsx,doc,docx,txt,zip,rar,7z',

    'DEFAULT_THUMBNAIL_WIDTH' => 0,

    'DEFAULT_UPLOAD_MAX_SIZE' => 1000, //in KB

    'IMAGE_EXTENSIONS' => 'jpg,png,jpeg,gif,bmp',

    'MAIN_DB_DATABASE' => env('DB_DATABASE'), //Very useful if you use config:cache

    'MULTIPLE_DATABASE_MODULE' => [],

    /*
    * Layout for the Admin LTE backend theme
    *
    * Fixed:               use the class .fixed to get a fixed header and sidebar.
    *                      This makes scrolling affect the content only and put the sidebar and header in a fixed position.
    *
    * Collapsed Sidebar:   use the class .sidebar-collapse to have a collapsed sidebar upon loading.
    *                      Use this if you want the sidebar to be hidden by default.
    *
    * Boxed Layout:        use the class .layout-boxed to get a boxed layout that stretches only to 1250px.
    *                      Provides spaces on both sides of the screen, if the screen is big enough.
    *
    * Top Navigation:      use the class .layout-top-nav to remove the sidebar and have your links at the top navbar.
    *                      Makes the sidebar hover the content when expanded.
    *
    * Sidebar Mini:        Shows the only the icons of the sidebar items when collapsed. Sidebar will not fully collapse.
    *
    * Available options:
    *
    * fixed
    * sidebar-collapse
    * layout-boxed
    * layout-top-nav
    * sidebar-mini
    *
    * Note: you cannot use both layout-boxed and fixed at the same time. Anything else can be mixed together.
    */

    'ADMIN_LAYOUT' => '',

    /*
    * NOTE :
    * Make sure your clear your config cache by using command : php artisan config:clear
    */

    // --------------
    // STYLES
    // --------------

    // CSS files that are loaded in all pages, using Laravel's asset() helper
    'styles' => [
        // Base css
        'crudbooster/ionic/css/ionicons.min.css',
        'crudbooster/assets/css/main.css',

        // Bootstrap Datepicker
        'crudbooster/assets/adminlte/plugins/datepicker/datepicker3.css',
        'crudbooster/assets/adminlte/plugins/daterangepicker/daterangepicker-bs3.css',

        // Bootstrap Time Picker
        'crudbooster/assets/adminlte/plugins/timepicker/bootstrap-timepicker.min.css',

        // Lightbox
        'crudbooster/assets/lightbox/dist/css/lightbox.min.css',

        // Sweetalert 2
        'crudbooster/assets/sweetalert2/sweetalert2@11.min.css',

        // Datatables
        'crudbooster/assets/adminlte/plugins/datatables/dataTables.bootstrap.css',

        // Select2
        'crudbooster/assets/select2/dist/css/select2.min.css',

        'https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css',
        'https://use.fontawesome.com/releases/v5.7.2/css/all.css',
        'https://cdnjs.cloudflare.com/ajax/libs/bootstrap-social/5.1.1/bootstrap-social.min.css',

        // Stisla
        'crudbooster/themes/stisla/css/style.css',
        'crudbooster/themes/stisla/css/components.css',
    ],

    // --------------
    // SCRIPTS
    // --------------

    // JS files that are loaded in all pages, using Laravel's asset() helper
    'scripts' => [
        // Base JS
        'https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js',
        'https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js',
        'https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js',
        'https://cdnjs.cloudflare.com/ajax/libs/jquery.nicescroll/3.7.6/jquery.nicescroll.min.js',
        'https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.24.0/moment.min.js',
        'crudbooster/themes/stisla/js/stisla.js',
        'crudbooster/themes/stisla/js/scripts.js',
        'crudbooster/themes/stisla/js/custom.js',
        'crudbooster/assets/js/main.js',

        // Bootstrap Datepicker
        'crudbooster/assets/adminlte/plugins/datepicker/bootstrap-datepicker.js',
        'crudbooster/assets/adminlte/plugins/daterangepicker/daterangepicker.js',

        // Bootstrap Time Picker
        'crudbooster/assets/adminlte/plugins/timepicker/bootstrap-timepicker.min.js',

        // Lightbox
        'crudbooster/assets/lightbox/dist/js/lightbox.min.js',

        // Sweetalert 2
        'crudbooster/assets/sweetalert2/sweetalert2@11.min.js',

        // Money format
        'crudbooster/jquery.price_format.2.0.min.js',

        // Datatables
        'crudbooster/assets/adminlte/plugins/datatables/jquery.dataTables.min.js',
        'crudbooster/assets/adminlte/plugins/datatables/dataTables.bootstrap.min.js',

        // Select2
        'crudbooster/assets/select2/dist/js/select2.full.min.js',

        // Jquery sortables
        'crudbooster/assets/jquery-sortable-min.js',
    ],

    // filesystem for storing files
    'filesystem_driver' => env('CB_FILESYSTEM_DRIVER', 'local'),

    'prefix_upload' => env('CB_PREFIX_UPLOAD'),
];