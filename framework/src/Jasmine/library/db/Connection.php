<?php
/**
 * Created by PhpStorm.
 * User: zzpzero
 * Date: 2018/4/24
 * Time: 10:09
 */

namespace Jasmine\library\db;


class Connection
{
    private $masterLink = 'read';
    protected $links = [];

    function __construct($config)
    {
        if(func_num_args()>0){
            $this->ping($config,$this->masterLink);
        }
    }

    /**
     *
     * User: Peter
     * Date: 2019/3/20
     * Time: 1:28
     *
     * @param array $config
     * @param int|string $link
     * @return \PDO|null
     */
    function ping($config = [], $link = 0)
    {

        /**
         * 返回可用连接
         */
        if (isset($this->links[$link])) {
            return $this->links[$link];
        }

        $config['username'] = isset($config['username']) ? $config['username'] : '';
        $config['password'] = isset($config['password']) ? $config['password'] : '';
        $config['options'] = isset($config['options']) ? $config['options'] : [];

        try {
            $this->links[$link] = new \PDO($this->parseDsn($config), $config['username'], $config['password'], $config['options']);

            return $this->links[$link];
        } catch (\PDOException $PDOException) {
            //TODO
        }
        return null;
    }

    /**
     *
     * User: Peter
     * Date: 2019/3/20
     * Time: 1:23
     *
     * @param $config
     * @return bool|string
     */
    protected function parseDsn($config)
    {
        $dsn = isset($config['dsn']) ? $config['dsn'] : (isset($config['driver']) ? $config['driver'] . ":" : 'mysql:');

        $dsn = strlen($dsn) > 0 && $dsn[strlen($dsn) - 1] == ';' ? substr($dsn, 0, strlen($dsn) - 1) : $dsn;

        $arr = [];
        if (isset($config['params']) && is_array($config['params'])) {
            foreach ($config['params'] as $key => $value) {
                array_push($arr, "{$key}={$value}");
            }
            $dsn .= implode(';', $arr);
            unset($arr);
        }
        return $dsn;
    }

    /**
     *
     * User: Peter
     * Date: 2019/3/24
     * Time: 22:41
     *
     * @return \PDO|null
     */
    function getMaster(){
        if(isset($this->links[$this->masterLink])){
            return $this->links[$this->masterLink];
        }
        return null;
    }
}