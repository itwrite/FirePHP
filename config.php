<?php
/**
 * Created by PhpStorm.
 * User: zzpzero
 * Date: 2018/1/3
 * Time: 15:13
 */

return array(
    /**
     * debug mode
     * you turn it off/on with set it false/true
     */
    'debug'=> false,

    /**
     * app configuration
     */
    'app'=>array(
        //默认模块
        'default_module'=>'Api',
        //默认控制器
        'default_controller'=>'Index',
        //默认操作
        'default_action'=>'index',
        //定义控制器目录名
        'controller_folder'=>'Controller'
    ),

    /**
     * set the cookie configuration here
     * cookie configuration
     */
    'cookie'=>array(
        'prefix'=>'',
        'path'=>'',
        'domain'=>'',
    ),

    /**
     * set the database configuration here.
     * we have several db
     */
    'db'=>array(
        'dsn'=>'mysql:host=localhost;dbname=thinkshop;',
        'username'=>'root',
        'password'=>'123456',
        'driver'=>'mysql'
    ),

    /**
     * request configuration
     */
    'request'=>array(
        'var_is_ajax'=>'is_ajax',
        'var_action'=>'a',
        'var_module'=>'m',
        'var_controller'=>'c',
        'var_jsonp_handler'=>'callback',
    )
);