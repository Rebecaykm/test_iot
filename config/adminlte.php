<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Title
    |--------------------------------------------------------------------------
    |
    | Here you can change the default title of your admin panel.
    |
    | For detailed instructions you can look the title section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Basic-Configuration
    |
    */

    'title' => '',
    'title_prefix' => '',
    'title_postfix' => '| YKM',

    /*
    |--------------------------------------------------------------------------
    | Favicon
    |--------------------------------------------------------------------------
    |
    | Here you can activate the favicon.
    |
    | For detailed instructions you can look the favicon section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Basic-Configuration
    |
    */

    'use_ico_only' => false,
    'use_full_favicon' => false,

    /*
    |--------------------------------------------------------------------------
    | Google Fonts
    |--------------------------------------------------------------------------
    |
    | Here you can allow or not the use of external google fonts. Disabling the
    | google fonts may be useful if your admin panel internet access is
    | restricted somehow.
    |
    | For detailed instructions you can look the google fonts section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Basic-Configuration
    |
    */

    'google_fonts' => [
        'allowed' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Admin Panel Logo
    |--------------------------------------------------------------------------
    |
    | Here you can change the logo of your admin panel.
    |
    | For detailed instructions you can look the logo section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Basic-Configuration
    |
    */

    'logo' => '',
    'logo_img' => 'images/ykm.png',
    'logo_img_class' => 'brand-image',
    'logo_img_xl' => null,
    'logo_img_xl_class' => 'brand-image-xs',
    'logo_img_alt' => 'YKM Logo',

    /*
    |--------------------------------------------------------------------------
    | Authentication Logo
    |--------------------------------------------------------------------------
    |
    | Here you can setup an alternative logo to use on your login and register
    | screens. When disabled, the admin panel logo will be used instead.
    |
    | For detailed instructions you can look the auth logo section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Basic-Configuration
    |
    */

    'auth_logo' => [
        'enabled' => false,
        'img' => [
            'path' => 'images/ykm.png',
            'alt' => 'YKM Logo',
            'class' => '',
            'width' => 50,
            'height' => 50,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Preloader Animation
    |--------------------------------------------------------------------------
    |
    | Here you can change the preloader animation configuration. Currently, two
    | modes are supported: 'fullscreen' for a fullscreen preloader animation
    | and 'cwrapper' to attach the preloader animation into the content-wrapper
    | element and avoid overlapping it with the sidebars and the top navbar.
    |
    | For detailed instructions you can look the preloader section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Basic-Configuration
    |
    */

    'preloader' => [
        'enabled' => false,
        'mode' => 'fullscreen',
        'img' => [
            'path' => 'images/ykm.png',
            'alt' => 'YKM Preloader Image',
            'effect' => 'animation__shake',
            'width' => 60,
            'height' => 60,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Menu
    |--------------------------------------------------------------------------
    |
    | Here you can activate and change the user menu.
    |
    | For detailed instructions you can look the user menu section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Basic-Configuration
    |
    */

    'usermenu_enabled' => true,
    'usermenu_header' => false,
    'usermenu_header_class' => 'bg-primary',
    'usermenu_image' => false,
    'usermenu_desc' => false,
    'usermenu_profile_url' => false,

    /*
    |--------------------------------------------------------------------------
    | Layout
    |--------------------------------------------------------------------------
    |
    | Here we change the layout of your admin panel.
    |
    | For detailed instructions you can look the layout section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Layout-and-Styling-Configuration
    |
    */

    'layout_topnav' => null,
    'layout_boxed' => null,
    'layout_fixed_sidebar' => true,
    'layout_fixed_navbar' => true,
    'layout_fixed_footer' => null,
    'layout_dark_mode' => null,

    /*
    |--------------------------------------------------------------------------
    | Authentication Views Classes
    |--------------------------------------------------------------------------
    |
    | Here you can change the look and behavior of the authentication views.
    |
    | For detailed instructions you can look the auth classes section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Layout-and-Styling-Configuration
    |
    */

    'classes_auth_card' => 'card-outline card-primary',
    'classes_auth_header' => '',
    'classes_auth_body' => '',
    'classes_auth_footer' => '',
    'classes_auth_icon' => '',
    'classes_auth_btn' => 'btn-flat btn-primary',

    /*
    |--------------------------------------------------------------------------
    | Admin Panel Classes
    |--------------------------------------------------------------------------
    |
    | Here you can change the look and behavior of the admin panel.
    |
    | For detailed instructions you can look the admin panel classes here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Layout-and-Styling-Configuration
    |
    */

    'classes_body' => '',
    'classes_brand' => '',
    'classes_brand_text' => '',
    'classes_content_wrapper' => '',
    'classes_content_header' => '',
    'classes_content' => '',
    'classes_sidebar' => 'sidebar-light-primary elevation-4',
    'classes_sidebar_nav' => '',
    'classes_topnav' => 'navbar-white navbar-light',
    'classes_topnav_nav' => 'navbar-expand',
    'classes_topnav_container' => 'container',

    /*
    |--------------------------------------------------------------------------
    | Sidebar
    |--------------------------------------------------------------------------
    |
    | Here we can modify the sidebar of the admin panel.
    |
    | For detailed instructions you can look the sidebar section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Layout-and-Styling-Configuration
    |
    */

    'sidebar_mini' => 'lg',
    'sidebar_collapse' => false,
    'sidebar_collapse_auto_size' => false,
    'sidebar_collapse_remember' => false,
    'sidebar_collapse_remember_no_transition' => true,
    'sidebar_scrollbar_theme' => 'os-theme-light',
    'sidebar_scrollbar_auto_hide' => 'l',
    'sidebar_nav_accordion' => true,
    'sidebar_nav_animation_speed' => 300,

    /*
    |--------------------------------------------------------------------------
    | Control Sidebar (Right Sidebar)
    |--------------------------------------------------------------------------
    |
    | Here we can modify the right sidebar aka control sidebar of the admin panel.
    |
    | For detailed instructions you can look the right sidebar section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Layout-and-Styling-Configuration
    |
    */

    'right_sidebar' => false,
    'right_sidebar_icon' => 'fas fa-cogs',
    'right_sidebar_theme' => 'dark',
    'right_sidebar_slide' => true,
    'right_sidebar_push' => true,
    'right_sidebar_scrollbar_theme' => 'os-theme-light',
    'right_sidebar_scrollbar_auto_hide' => 'l',

    /*
    |--------------------------------------------------------------------------
    | URLs
    |--------------------------------------------------------------------------
    |
    | Here we can modify the url settings of the admin panel.
    |
    | For detailed instructions you can look the urls section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Basic-Configuration
    |
    */

    'use_route_url' => false,
    'dashboard_url' => '/',
    'logout_url' => 'logout',
    'login_url' => 'login',
    'register_url' => 'register',
    'password_reset_url' => 'password/reset',
    'password_email_url' => 'password/email',
    'profile_url' => false,
    'disable_darkmode_routes' => false,

    /*
    |--------------------------------------------------------------------------
    | Laravel Asset Bundling
    |--------------------------------------------------------------------------
    |
    | Here we can enable the Laravel Asset Bundling option for the admin panel.
    | Currently, the next modes are supported: 'mix', 'vite' and 'vite_js_only'.
    | When using 'vite_js_only', it's expected that your CSS is imported using
    | JavaScript. Typically, in your application's 'resources/js/app.js' file.
    | If you are not using any of these, leave it as 'false'.
    |
    | For detailed instructions you can look the asset bundling section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Other-Configuration
    |
    */

    'laravel_asset_bundling' => false,
    'laravel_css_path' => 'css/app.css',
    'laravel_js_path' => 'js/app.js',

    /*
    |--------------------------------------------------------------------------
    | Menu Items
    |--------------------------------------------------------------------------
    |
    | Here we can modify the sidebar/top navigation of the admin panel.
    |
    | For detailed instructions you can look here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Menu-Configuration
    |
    */

    'menu' => [
        // Navbar items:
        // [
        //     'type' => 'navbar-search',
        //     'text' => 'search',
        //     'topnav_right' => true,
        // ],
        //        [
        //            'type' => 'fullscreen-widget',
        //            'topnav_right' => true,
        //        ],

        // ['header' => 'PANEL DE CONTROL'],

        [
            'type' => 'sidebar-menu-search',
            'text' => 'Buscar',
        ],
        [
            'text' => 'Identidades y Accesos',
            'icon' => 'fas fa-fw fa-shield-alt',
            'submenu' => [
                [
                    'text' => 'Usuarios',
                    'route' => 'users.index',
                    'icon' => 'fas fa-fw fa-user',
                    'can' => 'view users'
                ],
                [
                    'text' => 'Roles',
                    'route' => 'roles.index',
                    'icon' => 'fas fa-fw fa-id-card',
                    'can' => 'view roles'
                ],
            ]
        ],
        [
            'text' => 'Panel de Administración',
            'icon' => 'fas fa-fw fa-cog',
            'submenu' => [
                [
                    'text' => 'Clientes',
                    'route' => 'clients.index',
                    'icon' => 'fas fa-fw fa-handshake',
                    'can' => 'view tag types'
                ],
                [
                    'text' => 'Proyectos',
                    'route' => 'projects.index',
                    'icon' => 'fas fa-fw fa-project-diagram',
                    'can' => 'view tag types'
                ],
                [
                    'text' => 'Áreas',
                    'route' => 'areas.index',
                    'icon' => 'fas fa-fw fa-industry',
                    'can' => 'view areas'
                ],
                [
                    'text' => 'Lineas',
                    'route' => 'lines.index',
                    'icon' => 'fas fa-fw fa-pallet',
                    'can' => 'view lines'
                ],
                [
                    'text' => 'Estaciones',
                    'route' => 'work-centers.index',
                    'icon' => 'fas fa-fw fa-warehouse',
                    'can' => 'view work centers'
                ],
                [
                    'text' => 'Mapa de Estaciones',
                    'route' => 'work-center.map',
                    'icon' => 'fas fa-fw fa-map-pin',
                    'can' => 'view work centers map'
                ],
                [
                    'text' => 'Número de Parte',
                    'route' => 'part-numbers.index',
                    'icon' => 'fas fa-fw fa-shapes',
                    'can' => 'view part numbers',
                ],
                [
                    'text' => 'Tipo de Tags',
                    'route' => 'tag-types.index',
                    'icon' => 'fas fa-fw fa-tasks',
                    'can' => 'view tag types'
                ],
                [
                    'text' => 'Estados',
                    'route' => 'statuses.index',
                    'icon' => 'fas fa-fw fa-compass',
                    'can' => 'view statuses'
                ],
                [
                    'text' => 'Turnos',
                    'route' => 'shifts.index',
                    'icon' => 'fas fa-fw fa-clock',
                    'can' => 'view shifts'
                ],
                [
                    'text' => 'Tipos de Scrap',
                    'route' => 'type-scraps.index',
                    'icon' => 'fas fa-fw fa-trash-alt',
                    'can' => 'view type scraps'
                ],
                [
                    'text' => 'Scrap',
                    'route' => 'scraps.index',
                    'icon' => 'fas fa-fw fa-recycle',
                    'can' => 'view scraps'
                ],
                [
                    'text' => 'Tipos de Paro de Línea',
                    'route' => 'type-line-stoppages.index',
                    'icon' => 'fas fa-fw fa-tags',
                    'can' => 'view type line stoppages'
                ],
                [
                    'text' => 'Paros de Línea',
                    'route' => 'line-stoppages.index',
                    'icon' => 'fas fa-fw fa-stop-circle',
                    'can' => 'view line stoppages'
                ],
            ]
        ],
        [
            'text' => 'Gestión de Producción',
            'icon' => 'fas fa-fw fa-chart-line',
            'submenu' => [
                [
                    'text' => 'Registro de Producción',
                    'route'  => 'production-records.index',
                    'icon' => 'fas fa-fw fa-clipboard-check',
                    'can' => 'view production records'
                ],
                [
                    'text' => 'Resumen de Producción',
                    'route' => 'production-records.summary',
                    'icon' => 'fas fa-fw fa-chart-bar',
                    'can' => 'view production records'
                ],
                [
                    'text' => 'Secuencias de Producción',
                    'route' => 'production-sequences.index',
                    'icon' => 'fas fa-fw fa-list-ol',
                    'can' => 'view production records'
                ],
                // [
                //     'text' => 'Registro de Scrap',
                //     'route' => 'scrap-records.index',
                //     'icon' => 'fas fa-fw fa-trash',
                //     'can' => 'view scrap records'
                // ],
                // [
                //     'text' => 'Registro de Paros',
                //     'route' => 'line-stoppage-records.index',
                //     'icon' => 'fas fa-fw fa-stopwatch',
                //     'can' => 'view line stoppage records'
                // ],
            ],
        ],
        [
            'text' => 'Escaneo 3 Puntos',
            'icon' => 'fas fa-fw fa-qrcode',
            'submenu' => [
                [
                    'text' => 'Registros de Escaneo',
                    'route'  => 'material-validations.index',
                    'can' => 'view material validations'
                ],
                [
                    'text' => 'Resumen de Escaneo',
                    'route'  => 'material-validations.statistics',
                    'can' => 'view material validations'
                ]
            ]
        ]


        //        [
        //            'text' => 'blog',
        //            'url' => 'admin/blog',
        //            'can' => 'manage-blog',
        //        ],
        //        [
        //            'text' => 'pages',
        //            'url' => 'admin/pages',
        //            'icon' => 'far fa-fw fa-file',
        //            'label' => 4,
        //            'label_color' => 'success',
        //        ],
        //        ['header' => 'account_settings'],
        //        [
        //            'text' => 'profile',
        //            'url' => 'admin/settings',
        //            'icon' => 'fas fa-fw fa-user',
        //        ],
        //        [
        //            'text' => 'change_password',
        //            'url' => 'admin/settings',
        //            'icon' => 'fas fa-fw fa-lock',
        //        ],
        //
        //
        //        [
        //            'text' => 'multilevel',
        //            'icon' => 'fas fa-fw fa-share',
        //            'submenu' => [
        //                [
        //                    'text' => 'level_one',
        //                    'url' => '#',
        //                ],
        //                [
        //                    'text' => 'level_one',
        //                    'url' => '#',
        //                    'submenu' => [
        //                        [
        //                            'text' => 'level_two',
        //                            'url' => '#',
        //                        ],
        //                        [
        //                            'text' => 'level_two',
        //                            'url' => '#',
        //                            'submenu' => [
        //                                [
        //                                    'text' => 'level_three',
        //                                    'url' => '#',
        //                                ],
        //                                [
        //                                    'text' => 'level_three',
        //                                    'url' => '#',
        //                                ],
        //                            ],
        //                        ],
        //                    ],
        //                ],
        //                [
        //                    'text' => 'level_one',
        //                    'url' => '#',
        //                ],
        //            ],
        //        ],
        //        ['header' => 'labels'],
        //        [
        //            'text' => 'important',
        //            'icon_color' => 'red',
        //            'url' => '#',
        //        ],
        //        [
        //            'text' => 'warning',
        //            'icon_color' => 'yellow',
        //            'url' => '#',
        //        ],
        //        [
        //            'text' => 'information',
        //            'icon_color' => 'cyan',
        //            'url' => '#',
        //        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Menu Filters
    |--------------------------------------------------------------------------
    |
    | Here we can modify the menu filters of the admin panel.
    |
    | For detailed instructions you can look the menu filters section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Menu-Configuration
    |
    */

    'filters' => [
        JeroenNoten\LaravelAdminLte\Menu\Filters\GateFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\HrefFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\SearchFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\ActiveFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\ClassesFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\LangFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\DataFilter::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Plugins Initialization
    |--------------------------------------------------------------------------
    |
    | Here we can modify the plugins used inside the admin panel.
    |
    | For detailed instructions you can look the plugins section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Plugins-Configuration
    |
    */

    'plugins' => [
        'CustomStyles' => [
            'active' => true,
            'files' => [
                [
                    'type' => 'css',
                    'asset' => true,
                    'location' => 'css/custom.css',
                ],
            ],
        ],
        'Datatables' => [
            'active' => false,
            'files' => [
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => '//cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js',
                ],
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => '//cdn.datatables.net/1.10.19/js/dataTables.bootstrap4.min.js',
                ],
                [
                    'type' => 'css',
                    'asset' => false,
                    'location' => '//cdn.datatables.net/1.10.19/css/dataTables.bootstrap4.min.css',
                ],
            ],
        ],
        'Select2' => [
            'active' => true,
            'files' => [
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => '//cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js',
                ],
                [
                    'type' => 'css',
                    'asset' => false,
                    'location' => '//cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css',
                ],
            ],
        ],
        'Chartjs' => [
            'active' => true,
            'files' => [
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => '//cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.min.js',
                ],
            ],
        ],
        'Sweetalert2' => [
            'active' => true,
            'files' => [
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => '//cdn.jsdelivr.net/npm/sweetalert2@11',
                ],
            ],
        ],
        'Pace' => [
            'active' => false,
            'files' => [
                [
                    'type' => 'css',
                    'asset' => false,
                    'location' => '//cdnjs.cloudflare.com/ajax/libs/pace/1.0.2/themes/blue/pace-theme-center-radar.min.css',
                ],
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => '//cdnjs.cloudflare.com/ajax/libs/pace/1.0.2/pace.min.js',
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | IFrame
    |--------------------------------------------------------------------------
    |
    | Here we change the IFrame mode configuration. Note these changes will
    | only apply to the view that extends and enable the IFrame mode.
    |
    | For detailed instructions you can look the iframe mode section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/IFrame-Mode-Configuration
    |
    */

    'iframe' => [
        'default_tab' => [
            'url' => null,
            'title' => null,
        ],
        'buttons' => [
            'close' => true,
            'close_all' => true,
            'close_all_other' => true,
            'scroll_left' => true,
            'scroll_right' => true,
            'fullscreen' => true,
        ],
        'options' => [
            'loading_screen' => 1000,
            'auto_show_new_tab' => true,
            'use_navbar_items' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Livewire
    |--------------------------------------------------------------------------
    |
    | Here we can enable the Livewire support.
    |
    | For detailed instructions you can look the livewire here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Other-Configuration
    |
    */

    'livewire' => false,
];
