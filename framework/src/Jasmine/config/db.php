<?php
/**
 * Created by PhpStorm.
 * User: Peter
 * Date: 2018/12/18
 * Time: 17:30
 */

return [
    'master' => [
        //可有多个地址，但初始化时只随机使用一个
        'host' => ['127.0.0.1:3306'],
        'driver'    => 'mysql',
        'dbname'  => 'bullfight',
        'username'  => 'root',
        'password'  => 'root',
        'charset'   => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',

    ],
    'slave' => [
        'host' => ['127.0.0.1:3306'],
        'driver'    => 'mysql',
        'dbname'  => 'bullfight',
        'username'  => 'root',
        'password'  => 'root',
        'charset'   => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',

    ]
];