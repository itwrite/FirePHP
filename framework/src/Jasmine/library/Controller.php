<?php
/**
 * Created by PhpStorm.
 * User: itwri
 * Date: 2019/3/19
 * Time: 14:59
 */

namespace Jasmine\library;


use Jasmine\App;

abstract class Controller
{
    /**
     * @var App|null
     */
    protected $app = null;

    /**
     * @var http\Request|null
     */
    protected $Request = null;

    /**
     * @var http\Response|null
     */
    protected $Response = null;

    function __construct()
    {
        $this->app = App::init();
        $this->Request = $this->app->getRequest();
        $this->Response = $this->app->getResponse();
    }

    /**
     *
     * User: Peter
     * Date: 2019/3/21
     * Time: 17:55
     *
     * @param $data
     * @param string $msg
     * @param int $code
     * @param null $type
     * @return false|string
     */
    function success($data, $msg = '', $code = 200, $type = null)
    {
        if ($type == 'json' || $this->Request->isJson() || is_array($data)) {
            $this->Response->setContentType('application/json');
            $arr = [
                'code' => $code,
                'msg' => $msg,
                'data' => $data
            ];
            return json_encode($arr);
        }
        return $data;
    }

    /**
     *
     * User: Peter
     * Date: 2019/3/21
     * Time: 17:54
     *
     * @param $data
     * @param string $msg
     * @param int $code
     * @param null $type
     * @return false|string
     */
    function error($data, $msg = '', $code = -1, $type = null)
    {
        if ($type == 'json' || $this->Request->isJson() || is_array($data)) {
            $arr = [
                'code' => $code,
                'msg' => $msg,
                'data' => $data
            ];
            return json_encode($arr);
        }
        return $data;
    }

    /**
     *
     * User: Peter
     * Date: 2019/3/21
     * Time: 17:58
     *
     * @return App|null
     */
    function getApp()
    {
        return $this->app;
    }

    /**
     *
     * User: Peter
     * Date: 2019/3/21
     * Time: 11:30
     *
     * @param string $key
     * @param null $default
     * @param null $filter
     * @return http\Request|mixed|null
     */
    function request($key = '', $default = null, $filter = null)
    {
        if (func_num_args() == 0 || $key == null) {
            return $this->Request;
        }

        return call_user_func_array([$this->Request, 'input'], func_get_args());
    }
}