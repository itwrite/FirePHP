<?php
/**
 * Created by PhpStorm.
 * User: itwri
 * Date: 2019/3/8
 * Time: 2:30
 */

namespace Jasmine\library\http;

use Jasmine\library\http\request\Header;
use Jasmine\library\http\request\method\Get;
use Jasmine\library\http\request\method\Input;
use Jasmine\library\http\request\method\Post;
use Jasmine\library\util\Arr;

class Request
{
    protected $Get = null;
    protected $Post = null;
    protected $Input = null;
    protected $Header = null;

    protected $Url = null;

    /**
     * 配置参数
     * @var array
     */
    protected $config = [
        'var_action'=>'a',
        'var_module'=>'m',
        'var_controller'=>'c',
        // PATHINFO变量名 用于兼容模式
        'var_pathinfo' => 's',
        // 表单请求类型伪装变量
        'var_method' => '_method',
        // 表单ajax伪装变量
        'var_ajax' => '_ajax',
        // 表单pjax伪装变量
        'var_pjax' => '_pjax',

        //默认模块
        'default_module'=>'index',
        //默认控制器
        'default_controller'=>'index',
        //默认操作
        'default_action'=>'index',

    ];

    /**
     * 请求类型
     * @var string
     */
    private $_method;

    /**
     * @var mixed|string
     */
    protected $module = '';

    /**
     * @var mixed|string
     */
    protected $controller = '';

    /**
     * @var mixed|string
     */
    protected $action = '';

    protected $extraData = [];


    function __construct($config = [])
    {
        $this->config = array_merge($this->config, $config);

        $this->Url = new Url($this->getUrl());
        $this->Get = new Get($this->getUrl());
        $this->Post = new Post();
        $this->Input = new Input();
        $this->Header = new Header();

        $this->_method = $this->getMethod();

        if($pathinfo = $this->getRawParam($this->config('var_pathinfo'))){

            if ($pathinfo && preg_match('/^\/+(.*)/', $pathinfo, $mts)) {
                $pathinfo = $mts[1];
            }
            $arr = explode('/', $pathinfo);
            $this->module = array_shift($arr);
            $this->controller = count($arr)>0?array_shift($arr):'';
            $this->action = count($arr)>0?array_shift($arr):'';
            if(count($arr)>0){
                $this->extraData = $this->Url->parsePath(implode('/',$arr));
            }
        }
    }


    /**
     *
     * User: Peter
     * Date: 2019/3/21
     * Time: 2:06
     *
     * @param string $key
     * @param null $default
     * @param null $filter
     * @return mixed
     */
    function get($key = '', $default = null, $filter = null)
    {
        return call_user_func_array([$this->Get, 'get'], func_get_args());
    }

    /**
     *
     * User: Peter
     * Date: 2019/3/21
     * Time: 2:06
     *
     * @param string $key
     * @param null $default
     * @param null $filter
     * @return mixed
     */
    function post($key = '', $default = null, $filter = null)
    {
        return call_user_func_array([$this->Post, 'get'], func_get_args());
    }

    /**
     *
     * User: Peter
     * Date: 2019/3/21
     * Time: 12:28
     *
     * @param string $key
     * @param null $default
     * @param null $filter
     * @return mixed
     */
    function cookie($key = '', $default = null, $filter = null)
    {
        return Cookie::get($key, $default, $filter);
    }

    /**
     *
     * User: Peter
     * Date: 2019/3/21
     * Time: 12:29
     *
     * @param string $key
     * @param null $default
     * @param null $filter
     * @return mixed
     */
    function session($key = '', $default = null, $filter = null)
    {
        return Session::get($key, $default, $filter);
    }

    /**
     *
     * User: Peter
     * Date: 2019/3/21
     * Time: 12:35
     *
     * @param string $key
     * @param null $default
     * @param null $filter
     * @return mixed
     */
    function file($key = '', $default = null, $filter = null)
    {
        return Arr::get($_FILES, $key, $default, $filter);
    }

    /**
     *
     * User: Peter
     * Date: 2019/3/21
     * Time: 12:33
     *
     * @param string $key
     * @param null $default
     * @param null $filter
     * @return mixed
     */
    function server($key = '', $default = null, $filter = null)
    {
        if (func_num_args() == 0 || is_null($key)) return Server::all();
        return Server::get($key, $default, $filter);
    }


    /**
     *
     * User: Peter
     * Date: 2019/3/21
     * Time: 11:02
     *
     * @param string $key
     * @param null $default
     * @param null $filter
     * @return array|bool|mixed|string
     */
    function input($key = '', $default = null, $filter = null)
    {
        /**
         * 如果不传参数，返回Input的data
         */
        if (func_num_args() == 0 || empty($key)) {
            $input_data = $this->Input->getData(true);
            $get_data = $this->get();
            return array_merge($this->extraData,$get_data, $input_data);
        }

        if ($pos = strpos($key, '.')) {
            // 指定参数来源
            $method = substr($key, 0, $pos);
            if (in_array($method, ['get', 'post', 'session', 'cookie', 'server', 'file'])) {
                $key = substr($key, $pos + 1);
                return call_user_func_array([$this, $method], [$key, $default, $filter]);
            }
        }

        /**
         * 优先从Input 的data里取值
         */
        $data = $this->Input->getData(true);

        $input_data = Arr::get($data, $key, $default);
        if ($input_data != $default) {
            return Arr::filterValue($input_data, $filter);
        }

        $post_data = $this->post($key, $default);
        if ($post_data != $default) {
            return Arr::filterValue($post_data, $filter);
        }

        $get_data = $this->get($key, $default);
        if ($get_data != $default) {
            return Arr::filterValue($get_data, $filter);
        }

        return Arr::get($this->extraData,$key,$default,$filter);
    }

    /**
     *
     * User: Peter
     * Date: 2019/3/19
     * Time: 14:41
     *
     * @return mixed|string
     */
    function getAction()
    {
        if(empty($this->action)){
            $this->action = $this->get($this->config('var_action'),$this->config('default_action','index'));
        }
        return $this->action;
    }

    /**
     *
     * User: Peter
     * Date: 2019/3/19
     * Time: 14:41
     *
     * @return mixed|string
     */
    function getController()
    {
        if(empty($this->controller)){
            $this->controller = $this->get($this->config('var_controller'),$this->config('default_controller','index'));
        }

        return ucfirst($this->controller);
    }

    /**
     *
     * User: Peter
     * Date: 2019/3/19
     * Time: 14:41
     *
     * @return mixed|string
     */
    function getModule()
    {
        if(empty($this->module)){
            $this->module = $this->get($this->config('var_module'),$this->config('default_module','index'));
        }
        return $this->module;
    }

    /**
     *
     * User: Peter
     * Date: 2019/3/20
     * Time: 14:47
     *
     * @return array
     */
    function getHeaders()
    {
        return $this->Header->all();
    }

    /**
     *
     * User: Peter
     * Date: 2019/3/24
     * Time: 18:47
     *
     * @param string $key
     * @param null $default
     * @return array|mixed|string
     */
    function config($key = '',$default = null)
    {
        if (func_num_args() == 0 || empty($key)) {
            return $this->config;
        }
        return Arr::get($this->config,$key,$default);
    }

    /**
     * 当前的请求类型
     * @access public
     * @param  bool $origin 是否获取原始请求类型
     * @return string
     */
    public function getMethod($origin = false)
    {
        if ($origin) {
            // 获取原始请求类型
            return $this->server('REQUEST_METHOD', 'GET');
        } elseif (!$this->_method) {
            if ($_method = $this->input($this->config['var_method'])) {
                $this->_method = strtoupper($_method);
            } elseif ($_method = $this->server('HTTP_X_HTTP_METHOD_OVERRIDE')) {
                $this->_method = strtoupper($_method);
            } else {
                $this->_method = $this->server('REQUEST_METHOD', 'GET');
            }
        }

        return $this->_method;
    }

    /**
     *
     * User: Peter
     * Date: 2019/3/24
     * Time: 13:09
     *
     * @return mixed
     */
    public function getHost()
    {
        return $this->server('HTTP_HOST');
    }

    /**
     * 当前URL地址中的scheme参数
     * @access public
     * @return string
     */
    public function getScheme()
    {
        return $this->isSsl() ? 'https' : 'http';
    }

    /**
     *
     * User: Peter
     * Date: 2019/3/24
     * Time: 10:56
     *
     * @return mixed
     */
    public function getDomain()
    {
        return explode(':', $this->server('HTTP_HOST', ''))[0];
    }

    /**
     *
     * User: Peter
     * Date: 2019/3/24
     * Time: 11:04
     *
     * @return int
     */
    public function getPort()
    {
        $arr = explode(':', $this->getHost());
        return isset($arr[1]) && !in_array($arr[1], ['80', '443']) ? $arr[1] : '';
    }

    /**
     *
     * User: Peter
     * Date: 2019/3/24
     * Time: 13:25
     *
     * @return mixed
     */
    public function getScriptName()
    {
        return $this->server('SCRIPT_NAME', '');
    }

    /**
     *
     * User: Peter
     * Date: 2019/3/24
     * Time: 11:09
     *
     * @return mixed
     */
    public function getUri()
    {
        return $this->server('REQUEST_URI', '');
    }

    /**
     *
     * User: Peter
     * Date: 2019/3/24
     * Time: 11:33
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->getScheme() . "://" . $this->getHost() . $this->getUri();
    }

    /**
     *
     * User: Peter
     * Date: 2019/3/24
     * Time: 13:18
     *
     * @return string
     */
    public function getRawUrl()
    {
        return $this->getScheme() . "://" . $this->getHost() . $this->getScriptName() . ($this->getRawQuery() ? "?" . $this->getRawQuery() : '');
    }

    /**
     *
     * User: Peter
     * Date: 2019/3/24
     * Time: 11:12
     *
     * @return string
     */
    public function getQuery()
    {
        return $this->Url->getQuery();
    }

    /**
     *
     * User: Peter
     * Date: 2019/3/24
     * Time: 13:05
     *
     * @return mixed
     */
    public function getRawQuery()
    {
        return $this->server('QUERY_STRING', '');
    }

    /**
     *
     * User: Peter
     * Date: 2019/3/24
     * Time: 13:27
     *
     * @param string $key
     * @param null $default
     * @param null $filter
     * @return mixed
     */
    public function getRawParam($key = '', $default = null, $filter = null)
    {
        parse_str($this->getRawQuery(), $params);
        if (func_num_args() == 0) {
            return $params;
        }
        return Arr::get($params, $key, $default, $filter);
    }

    /**
     * 当前是否ssl
     * @access public
     * @return bool
     */
    public function isSsl()
    {
        if (in_array($this->server('HTTPS', ''), ['1', 'on'])
            || 'https' == $this->server('REQUEST_SCHEME')
            || '443' == $this->server('SERVER_PORT')
            || 'https' == $this->server('HTTP_X_FORWARDED_PROTO')) {
            return true;
        }

        return false;
    }

    /**
     * 当前是否Ajax请求
     * @access public
     * @param  bool $ajax true 获取原始ajax请求
     * @return bool
     */
    public function isAjax($ajax = false)
    {
        $value = $this->server('HTTP_X_REQUESTED_WITH', '');
        $result = 'xmlhttprequest' == strtolower($value) ? true : false;

        if (true === $ajax) {
            return $result;
        }
        return $this->get($this->config('var_ajax')) ? true : $result;
    }

    /**
     * 当前是否Pjax请求
     * @access public
     * @param  bool $pjax true 获取原始pjax请求
     * @return bool
     */
    public function isPjax($pjax = false)
    {
        $result = !is_null($this->server('HTTP_X_PJAX')) ? true : false;

        if (true === $pjax) {
            return $result;
        }

        $result = $this->input($this->config['var_pjax']) ? true : $result;
        return $result;
    }

    /**
     *
     * User: Peter
     * Date: 2019/3/21
     * Time: 1:37
     *
     * @return bool
     */
    function isPost()
    {
        return $this->getMethod() == 'POST';
    }

    /**
     *
     * User: Peter
     * Date: 2019/3/21
     * Time: 1:38
     *
     * @return bool
     */
    function isGet()
    {
        return $this->getMethod() == 'GET';
    }

    /**
     *
     * User: Peter
     * Date: 2019/3/21
     * Time: 1:38
     *
     * @return bool
     */
    function isPut()
    {
        return $this->getMethod() == 'PUT';
    }

    /**
     *
     * User: Peter
     * Date: 2019/3/21
     * Time: 1:39
     *
     * @return bool
     */
    function isDelete()
    {
        return $this->getMethod() == 'DELETE';
    }

    /**
     *
     * User: Peter
     * Date: 2019/3/21
     * Time: 2:28
     *
     * @return string
     */
    public function contentType()
    {
        $contentType = $this->server('CONTENT_TYPE');

        if ($contentType) {
            if (strpos($contentType, ';')) {
                list($type) = explode(';', $contentType);
            } else {
                $type = $contentType;
            }
            return trim($type);
        }

        return '';
    }

    /**
     *
     * User: Peter
     * Date: 2019/3/21
     * Time: 17:52
     *
     * @return bool
     */
    function isJson()
    {
        return false !== strpos($this->contentType(), 'application/json');
    }

    /**
     *
     * User: Peter
     * Date: 2019/3/21
     * Time: 17:52
     *
     * @return bool
     */
    function isXml()
    {
        return false !== strpos($this->contentType(), 'text/xml');
    }
}