<?php
/**
 * Created by PhpStorm.
 * User: itwri
 * Date: 2019/3/24
 * Time: 19:54
 */

/**
 *
 * User: Peter
 * Date: 2019/3/24
 * Time: 20:25
 *
 * @param $route
 * @param $params
 * @param $type
 * @return string
 */
function url($route='',$params=[],$type=1){
    echo \Jasmine\App::init()->getRequest()->getUrl();

    $url = "";
    return $url;
}