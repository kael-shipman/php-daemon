<?php
return [
    'exec-profile' => 'dev',
    'display-errors' => 1,
    'error-reporting' => E_ALL & ~E_WARNING,
    'socket-domain' => AF_UNIX,
    'socket-type' => SOCK_STREAM,
    'socket-protocol' => 0,
    'socket-address' => '/var/run/ks/exchange.sock',
    'socket-port' => null,
    'verbosity' => 1,
];

