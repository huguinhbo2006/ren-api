<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Rentame — Configuración del Sistema
    |--------------------------------------------------------------------------
    |
    | Aquí se definen los límites y configuraciones generales del sistema
    | Rentame, incluyendo los límites por plan y otras opciones globales.
    |
    */

    'plans' => [

        /*
        | Plan Gratuito — Límites de uso
        */
        'free' => [
            'slug'          => 'free',
            'max_assets'    => 3,
            'max_customers' => 10,
            'max_rentals_per_month' => 20,
            'max_extra_services' => 3,
            'abilities'     => ['basic'],
            'features'      => [
                'reports'        => false,
                'export_pdf'     => false,
                'export_excel'   => false,
                'multi_user'     => false,
                'contract_pdf'   => false,
                'audit_log'      => false,
                'advanced_dashboard' => false,
            ],
        ],

        /*
        | Plan Pro — Sin límites
        */
        'pro' => [
            'slug'          => 'pro',
            'max_assets'    => PHP_INT_MAX,
            'max_customers' => PHP_INT_MAX,
            'max_rentals_per_month' => PHP_INT_MAX,
            'max_extra_services' => PHP_INT_MAX,
            'abilities'     => ['basic', 'reports', 'export', 'multi-user', 'audit'],
            'features'      => [
                'reports'        => true,
                'export_pdf'     => true,
                'export_excel'   => true,
                'multi_user'     => true,
                'contract_pdf'   => true,
                'audit_log'      => true,
                'advanced_dashboard' => true,
            ],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de Notificaciones
    |--------------------------------------------------------------------------
    */
    'notifications' => [
        'default_days_before_expiry' => 3,
        'check_schedule'             => 'daily',
    ],

    /*
    |--------------------------------------------------------------------------
    | Folio de Rentas
    |--------------------------------------------------------------------------
    */
    'rental' => [
        'folio_prefix'  => 'RNT',
        'folio_digits'  => 5,
        // Genera: RNT-2026-00001
    ],

    /*
    |--------------------------------------------------------------------------
    | Almacenamiento de Archivos
    |--------------------------------------------------------------------------
    */
    'storage' => [
        'assets_path'   => 'assets',
        'receipts_path' => 'receipts',
        'logos_path'    => 'logos',
        'avatars_path'  => 'avatars',
        'pdfs_path'     => 'pdfs',
        'max_image_size_kb' => 5120,   // 5 MB
        'max_receipt_size_kb' => 10240, // 10 MB
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    */
    'rate_limits' => [
        'login'   => 5,   // intentos por minuto
        'api'     => 60,  // requests por minuto
        'uploads' => 20,  // subidas por minuto
    ],

];
