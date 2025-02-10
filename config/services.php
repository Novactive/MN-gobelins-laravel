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
        'endpoint' => 'api.eu.mailgun.net',
    ],

    'ses' => [
        'key' => env('SES_KEY'),
        'secret' => env('SES_SECRET'),
        'region' => 'us-east-1',
    ],

    'sparkpost' => [
        'secret' => env('SPARKPOST_SECRET'),
    ],

    'stripe' => [
        'model' => App\User::class,
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
    ],

    'zetcom' => [
        'base_url' => env('ZETCOM_BASE_URL', 'https://mpparismobiliernationaltest.zetcom.app/ria-ws/application'),
        'username' => env('ZETCOM_USERNAME', ''),
        'password' => env('ZETCOM_PASSWORD', ''),
        'module_namespace' => env('ZETCOM_MODULE_NAMESPACE', 'http://www.zetcom.com/ria/ws/module')
    ],


];
