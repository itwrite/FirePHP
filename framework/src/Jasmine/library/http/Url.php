<?php
/**
 * Created by PhpStorm.
 * User: itwri
 * Date: 2019/3/24
 * Time: 11:25
 */

namespace Jasmine\library\http;


use Jasmine\library\http\request\schema\Eloquent;
use Jasmine\library\util\Arr;

class Url extends Eloquent
{
    /**
     * scheme - e.g. http
     * host
     * port
     * user
     * pass
     * path
     * query - after the question mark ?
     * fragment - after the hashmark #
     *
     * Url constructor.
     * @param $url
     */
    function __construct($url)
    {
        $this->parse($url);
    }

    /**
     *
     * User: Peter
     * Date: 2019/3/24
     * Time: 12:21
     *
     * @param $url
     * @return $this
     */
    public function parse($url)
    {
        $this->data = parse_url($url);
        return $this;
    }

    /**
     *
     * User: Peter
     * Date: 2019/3/24
     * Time: 11:43
     *
     * @return string
     */
    public function getScheme()
    {
        return $this->get('scheme', '');
    }

    /**
     *
     * User: Peter
     * Date: 2019/3/24
     * Time: 11:43
     *
     * @return string
     */
    public function getHost()
    {
        return $this->get('host', '');
    }

    /**
     *
     * User: Peter
     * Date: 2019/3/24
     * Time: 11:44
     *
     * @return string
     */
    public function getPort()
    {
        return $this->get('port', '');
    }

    /**
     *
     * User: Peter
     * Date: 2019/3/24
     * Time: 11:45
     *
     * @return string
     */
    public function getUser()
    {
        return $this->get('user', '');
    }

    /**
     *
     * User: Peter
     * Date: 2019/3/24
     * Time: 11:46
     *
     * @return string
     */
    public function getPass()
    {
        return $this->get('pass', '');
    }

    /**
     *
     * User: Peter
     * Date: 2019/3/24
     * Time: 11:29
     *
     * @return array|mixed
     */
    public function getPath()
    {
        return $this->get('path', '');
    }

    /**
     *
     * User: Peter
     * Date: 2019/3/24
     * Time: 11:29
     *
     * @return array|mixed
     */
    public function getQuery()
    {
        return $this->get('query', '');
    }

    /**
     *
     * User: Peter
     * Date: 2019/3/24
     * Time: 11:53
     *
     * @return string
     */
    public function getFragment()
    {
        return $this->get('fragment', '');
    }

    /**
     *
     * User: Peter
     * Date: 2019/3/24
     * Time: 12:51
     *
     * @param $key
     * @param null $default
     * @param null $filter
     * @return mixed
     */
    public function getParam($key='', $default = null, $filter = null)
    {
        parse_str($this->getQuery(), $params);
        if (func_num_args() == 0) {
            return $params;
        }
        return Arr::get($params, $key, $default, $filter);
    }

    /**
     *
     * @param string $path key1/value1/key2/value2/key3/value3
     * @param string $sep
     * @return array [key1=>value1,key2=>value2,...]
     */
    function parsePath($path, $sep = "/")
    {

        $result = array();

        if (!empty($path) && $path[0] == $sep) {
            $path = substr($path, 1);
        }

        $info = explode($sep, $path);
        if (count($info) < 2) {
            return $result;
        }

        for ($i = 0; $i < count($info); $i++) {

            if (isset($info[$i + 1])) {

                $result[$info[$i]] = $info[$i + 1];
                $i++;
            }

        }
        return $result;
    }

    /**
     *
     * User: Peter
     * Date: 2019/3/24
     * Time: 11:47
     *
     * @return string
     */
    public function __toString()
    {
        return implode('', [
            ($this->getScheme() ? $this->getScheme() . "://" : ""),
            ($this->getUser() ? $this->getUser() . ($this->getPass() ? ":" . $this->getPass() : '') . "@" : ''),
            ($this->getHost()),
            ($this->getPort() ?: ''),
            ($this->getPath()),
            ($this->getQuery() ? "?" . $this->getQuery() : ""),
            ($this->getFragment() ? "#" . $this->getFragment() : '')
        ]);
    }
}