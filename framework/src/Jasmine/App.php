<?php
/**
 * Created by PhpStorm.
 * User: Peter
 * Date: 2018/12/18
 * Time: 17:50
 */

namespace Jasmine;

use Jasmine\helper\Config;
use Jasmine\library\console\Console;
use Jasmine\library\db\connection\Connection;
use Jasmine\library\db\Database;
use Jasmine\library\http\Request;
use Jasmine\library\http\Response;
use Jasmine\library\http\Server;
use Jasmine\library\util\File;

defined('DS') or define('DS', DIRECTORY_SEPARATOR);

require_once 'library/util/File.php';

class App
{
    /**
     * @var bool
     */
    protected $debug = false;

    /**
     * @var string
     */
    static protected $app_namespace = 'app';

    /**
     * @var $this |null
     */
    static protected $instance = null;

    /**
     * @var Console|null
     */
    protected $Console = null;

    /**
     * @var Request|null
     */
    protected $Request = null;

    /**
     * @var null
     */
    protected $Response = null;

    /**
     * @var null
     */
    static protected $db = null;

    /**
     * @var string
     */
    protected $rootPath = '';

    /**
     * @var string
     */
    protected $appPath = '';

    /**
     * @var int|mixed
     */
    protected $beginTime = 0;

    /**
     * @var int
     */
    protected $beginMem = 0;

    /**
     * App constructor.
     */
    function __construct()
    {
        /**
         * 记录开始时间
         */
        $this->beginTime = microtime(true);

        /**
         * 记录开始时的内存使用情况
         */
        $this->beginMem = memory_get_usage();

        /*
        |--------------------------------------------------------------------------
        | 注册AUTOLOAD方法
        |--------------------------------------------------------------------------
        */
        spl_autoload_register('self::autoload');

        /**
         *
         */
        File::load(__DIR__ . DS . "common");


        //初始化一些常量和配置
        /**
         * 取入口文件的目录为根目录
         * 保存到全局变量中
         */
        $this->rootPath = dirname(realpath(Server::get('SCRIPT_FILENAME')));
        Config::set('PATH_ROOT', $this->rootPath);

        /**
         * 此部分可通过提前声明全局常量来控制
         */
        //调试模式,默认true
        !is_null(Config::get('debug')) or Config::set('debug', defined('DEBUG') ? DEBUG : true);

        /**
         * 应用目录，可通过提前声明的常量进行设置，也可以通过config去设置
         * 默认为入口文件目录下的Application目录
         */
        !is_null(Config::get('PATH_APPS')) or Config::set('PATH_APPS', defined('PATH_APPS') ? PATH_APPS : dirname($this->rootPath) . DS . 'Application');
        $this->appPath = Config::get('PATH_APPS');

        /**
         * 加载默认配置信息
         */
        Config::load(__DIR__ . DS . 'config');

    }


    /**
     * Desc:
     * User: Peter
     * Date: 2019/1/17
     * Time: 20:40
     *
     * @return App|null
     */
    static public function init()
    {
        if (!self::$instance instanceof self) {
            self::$instance = new static();
        }
        return self::$instance;
    }

    /**
     * 解析应用类的类名
     * @access public
     * @param  string $module 模块名
     * @param  string $layer 层名 controller model ...
     * @param  string $name 类名
     * @param  bool $appendSuffix
     * @return string
     */
    private function parseAppClass($module, $layer, $name, $appendSuffix = false)
    {
        /**
         * 替换
         */
        $name = str_replace(['/', '.'], '\\', $name);
        $array = explode('\\', $name);

        $class = $this->parseName(array_pop($array), 1) . ($appendSuffix ? ucfirst($layer) : '');

        $path = $array ? implode('\\', $array) . '\\' : '';

        return self::$app_namespace . '\\' . ($module ? $module . '\\' : '') . $layer . '\\' . $path . $class;
    }

    /**
     * 字符串命名风格转换
     * type 0 将Java风格转换为C的风格 1 将C风格转换为Java的风格
     * @access public
     * @param  string $name 字符串
     * @param  integer $type 转换类型
     * @param  bool $ucfirst 首字母是否大写（驼峰规则）
     * @return string
     */
    private function parseName($name, $type = 0, $ucfirst = true)
    {
        if ($type) {
            $name = preg_replace_callback('/_([a-zA-Z])/', function ($match) {
                return strtoupper($match[1]);
            }, $name);
            return $ucfirst ? ucfirst($name) : lcfirst($name);
        }

        return strtolower(trim(preg_replace("/[A-Z]/", "_\\0", $name), "_"));
    }

    /**
     * Desc:
     * User: Peter
     * Date: 2019/3/5
     * Time: 19:39
     *
     * @param \Closure|null $callback
     */
    public function console($callback = null)
    {
        /**
         * 控制台
         */
        $this->Console = new Console();

        //如果返回的是false，强制退出
        if (is_callable($callback) && call_user_func_array($callback, array($this)) === false) exit("exit anyway!!");

        defined('PATH_CONFIG') && Config::set('PATH_CONFIG', PATH_CONFIG);

        !is_null(Config::get('PATH_CONFIG')) && $this->loadConfig(Config::get('PATH_CONFIG'));

        try {
            /**
             * 访问的模块
             */
            $module = $this->Console->getInput()->getModule();

            if (empty($module)) {
                throw new \ErrorException("Module can not be empty");
            }

            $controller = ucfirst($this->Console->getInput()->getController());

            if (empty($controller)) {
                throw new \ErrorException("Controller can not be empty");
            }

            $action = $this->Console->getInput()->getAction();

            if (empty($action)) {
                throw new \ErrorException("Action can not be empty");
            }
            /**
             * 路由规则
             */
            $controller_class = $this->parseAppClass($module, 'console', $controller);

            /**
             * 实例化
             */
            $controller_instance = new $controller_class();
            /**
             * 检查操作的合法性，并调起对应的操作方法
             */
            if (!empty($action) && is_callable(array($controller_instance, $action))) {

                //
                File::load(implode(DS, [Config::get('PATH_APPS', ''), $module, 'common']));
                //加载模块下的配置
                Config::load(implode(DS, [Config::get('PATH_APPS', ''), $module, 'config']));
                //调用对应的操作方法方
                echo call_user_func_array(array($controller_instance, $action), array($this));
                die();
            } elseif (!empty($action)) {
                throw new \ErrorException("非法操作");
            }

        } catch (\ErrorException $e) {
            print_r("Error: " . $e->getMessage() . PHP_EOL);
            print_r($e->getTraceAsString());
        }
    }

    /**
     *
     * User: Peter
     * Date: 2019/3/19
     * Time: 14:44
     *
     * @param \Closure|null $callback
     */
    public function web($callback = null)
    {
        /**
         *
         */
        $this->Request = new Request(Config::get('app'));
        $this->Response = new Response();

        //如果返回的是false，强制退出
        if (is_callable($callback) && call_user_func_array($callback, array($this)) === false) exit("exit anyway!!");

        defined('PATH_CONFIG') && Config::set('PATH_CONFIG', PATH_CONFIG);

        !is_null(Config::get('PATH_CONFIG')) && $this->loadConfig(Config::get('PATH_CONFIG'));

        try {
            /**
             * 访问的模块
             */
            $module = $this->Request->getModule();

            if (empty($module)) {
                throw new \ErrorException("Module can not be empty");
            }

            $controller = $this->Request->getController();

            if (empty($controller)) {
                throw new \ErrorException("Controller can not be empty");
            }

            $action = $this->Request->getAction();

            if (empty($action)) {
                throw new \ErrorException("Action can not be empty");
            }
            /**
             * 路由规则
             */
            $controller_class = $this->parseAppClass($module, 'controller', $controller);

            /**
             * 实例化
             */
            $controller_instance = new $controller_class();
            /**
             * 检查操作的合法性，并调起对应的操作方法
             */
            if (!empty($action) && is_callable(array($controller_instance, $action))) {
                File::load(implode(DS, [Config::get('PATH_APPS', ''), $module, 'common']));
                //加载模块下的配置
                Config::load(implode(DS, [Config::get('PATH_APPS', ''), $module, 'config']));

                //调用对应的操作方法方
                $result = call_user_func_array(array($controller_instance, $action), array($this));

                die($result = $result === false ? '0' : $result);
            } elseif (!empty($action)) {
                throw new \ErrorException("非法操作");
            }

        } catch (\ErrorException $e) {
            print_r("Error: " . $e->getMessage() . PHP_EOL);
            print_r($e->getTraceAsString());
        }
    }

    /**
     * Desc:
     * User: Peter
     * Date: 2019/1/17
     * Time: 21:28
     *
     * @return Database|null
     */
    public function getDb()
    {
        if (self::$db == null) {
            self::$db = new Database(new Connection(Config::get('db')));
        }
        return self::$db;
    }

    /**
     *
     * User: Peter
     * Date: 2019/3/19
     * Time: 15:34
     *
     * @return Request|null
     */
    public function getRequest()
    {
        return $this->Request;
    }

    /**
     *
     * User: Peter
     * Date: 2019/3/21
     * Time: 16:00
     *
     * @return Response|null
     */
    public function getResponse()
    {
        return $this->Response;
    }

    /**
     *
     * User: Peter
     * Date: 2019/3/24
     * Time: 23:22
     *
     * @param $key
     * @param null $default
     * @return mixed
     */
    public function config($key='', $default = null)
    {
        if (func_num_args() == 0||empty($key)) {
            return Config::all();
        }
        return Config::get($key,$default);
    }

    /**
     * Desc:
     * User: Peter
     * Date: 2019/3/8
     * Time: 2:06
     *
     * @param $file
     */
    public function loadConfig($file)
    {
        Config::load($file);
    }

    /**
     * ===============================================================================================================
     * ===============================================================================================================
     * ===============================================================================================================
     * ===============================================================================================================
     *
     */

    /**
     * cache class file's path
     * @var array
     */
    static private $_auto_loaded_class_files = array();

    /**
     * Desc:
     * User: Peter
     * Date: 2019/3/8
     * Time: 1:01
     *
     * @param $class
     * @return bool
     * @throws \Exception
     */
    static public function autoload($class)
    {
        //如果第一个字符为\，则先去掉它
        $class = $class{0} == '\\' ? substr($class, 1) : $class;

        //if it is already there, load it directly.
        if (isset(self::$_auto_loaded_class_files[$class])) {
            File::load(self::$_auto_loaded_class_files[$class]);
            return true;
        } else {
            //
            $first_of_namespace = strstr($class, '\\', true);

            /**
             * 目前自动加载的类文件只支持.php文件
             * 注：往后可扩展
             */
            $filename = str_replace('\\', DS, $class) . '.php';

            /**
             * 提供三种自加载途径
             * 1、框架类
             * 2、模块类
             */
            $framework_namespace = explode('\\', __NAMESPACE__)[0];

            if (in_array($first_of_namespace, [self::$app_namespace, $framework_namespace])) {
                $basePath = dirname(__DIR__);

                if ($first_of_namespace == self::$app_namespace) {
                    $basePath = Config::get('PATH_APPS');
                    $filename = substr($filename, strlen(self::$app_namespace)+1);
                }

                //架框类目录
                $file = $basePath . DS . $filename;
                if (is_file($file)) {
                    // Win环境严格区分大小写
                    if (strpos(PHP_OS, 'WIN') !== false && pathinfo($file, PATHINFO_FILENAME) != pathinfo(realpath($file), PATHINFO_FILENAME)) {
                        return false;
                    }

                    File::load($file);

                    //缓存加载过的文件路径
                    self::$_auto_loaded_class_files[$class] = $file;
                    return true;
                } else {
                    throw new \Exception("class not exists:" . $class);
                }
            } else {
                throw new \Exception("class not exists:" . $class);
            }
        }
    }
}
