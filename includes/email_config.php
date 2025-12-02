<?php

return [
    'smtp' => [
        'host'       => 'smtp.gmail.com',
        'port'       => 587,
        'encryption' => 'tls',
        'auth'       => true,
        'username'   => 'mohammedoli376@gmail.com',
        'password'   => 'nqqk xoel sarm wbfc',
    ],

    'from' => [
        'email' => 'mohammedoli376@gmail.com',
        'name'  => 'Online Voting System'
    ],

    'settings' => [
        'subject_prefix' => '[OVS] ',
        'otp_expiry'     => 2,
        'max_retries'    => 3,
    ],

    'debug' => false,

    'templates' => [
        'otp_subject' => 'Your Voting System OTP Code',
        'otp_footer'  => 'This is an automated message. Please do not reply.',
    ]
];
