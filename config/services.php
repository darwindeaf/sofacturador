<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Stripe, Mailgun, SparkPost and others. This file provides a sane
    | default location for this type of information, allowing packages
    | to have a conventional place to find your various credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
    ],

    'ses' => [
        'key' => env('SES_KEY'),
        'secret' => env('SES_SECRET'),
        'region' => env('SES_REGION', 'us-east-1'),
    ],

    'sparkpost' => [
        'secret' => env('SPARKPOST_SECRET'),
    ],

    'stripe' => [
        'model' => App\User::class,
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
    ],

    'sunat_gre' => [
        'security_url' => env('SUNAT_GRE_SECURITY_URL', 'https://api-seguridad.sunat.gob.pe/v1/clientessol'),
        'cpe_url' => env('SUNAT_GRE_CPE_URL', 'https://api-cpe.sunat.gob.pe/v1/contribuyente/gem'),
        'scope' => env('SUNAT_GRE_SCOPE', 'https://api-cpe.sunat.gob.pe'),
        'timeout' => (int) env('SUNAT_GRE_TIMEOUT', 30),
        'connect_timeout' => (int) env('SUNAT_GRE_CONNECT_TIMEOUT', 10),
    ],

];
