<?php
/**
 * Created by PhpStorm.
 * User: zzpzero
 * Date: 2018/4/24
 * Time: 10:10
 */

namespace Jasmine\library\db;

require_once("Builder.php");

/**
 * @method \PDOStatement|bool prepare ($statement, array $driver_options = array())
 * @method bool beginTransaction ()
 * @method bool commit ()
 * @method bool rollBack ()
 * @method bool inTransaction ()
 * @method bool setAttribute ($attribute, $value)
 * @method string lastInsertId ($name = null)
 * @method mixed errorCode()
 * @method array errorInfo ()
 * @method mixed getAttribute ($attribute)
 * @method string quote ($string, $parameter_type = \PDO::PARAM_STR)
 * @method bool sqliteCreateFunction($function_name, $callback, $num_args = -1, $flags = 0)
 * above methods are belong to PDO
 * Class Database
 * @package Jasmine\library\db
 */
class Database extends Builder
{
    /**
     * @var bool
     */
    private $_debug = true;

    /**
     * @var null|\PDO
     */
    private $_pdo = null;

    /**
     * Database constructor.
     * @param string $dsn
     * @param string $username
     * @param string $password
     * @param array $options
     */
    public function __construct($dsn, $username, $password, array $options = array())
    {
        parent::__construct();
        $this->_pdo = new \PDO($dsn, $username, $password, $options);
    }

    /**
     * @return null|\PDO
     */
    public function pdo()
    {
        return $this->_pdo;
    }

    /**
     * @param bool $debug
     * @return $this
     */
    public function debug($debug = false)
    {
        if (func_num_args() > 0) {
            $this->_debug = $debug;
        }
        return $this;
    }

    /**
     * @param array $data
     * @param bool $is_replace
     * @return int
     */
    public function insert(Array $data, $is_replace = false)
    {
        if (empty($data)) return 0;
        //set data
        $this->set($data);
        //get the insert sql
        $SQL = $this->getInsertSql($is_replace);
        //begin transaction
        $this->beginTransaction();
        //execute the sql
        $this->exec($SQL);
        //get the inserted Id
        $lastInsertId = $this->lastInsertId();
        //commit
        $res = $this->commit();
        if ($res === false) {
            //if failure, roll back the sql;
            $this->rollBack();
            $errorInfo = $this->errorInfo();
            die(isset($errorInfo[2]) ? $errorInfo[2] : "");
        }
        return intval($lastInsertId);
    }

    /**
     * @return int
     */
    public function delete()
    {
        //get the delete sql
        $SQL = $this->getDeleteSql();

        return $this->exec($SQL);
    }

    /**
     * @param array $data
     * @return int
     */
    public function update(Array $data)
    {
        //set data
        $this->set($data);
        //get the update sql;
        $SQL = $this->getUpdateSql();

        return $this->exec($SQL);
    }

    /**
     * @param string $fields
     * @return array|Builder
     */
    public function select($fields = '*')
    {
        parent::select($fields);

        return $this->getAll();
    }

    /**
     * @return int|mixed
     */
    public function count()
    {
        $this->limit(1);
        //get the select sql
        $SQL = $this->getCountSql();
        //query
        $st = $this->query($SQL);
        if ($st !== false) {
            return $st->fetch(\PDO::FETCH_COLUMN);
        }
        return 0;
    }

    /**
     * @param int $fetch_type
     * @return bool|mixed
     */
    public function getOne($fetch_type = \PDO::FETCH_ASSOC)
    {
        $this->limit(1);
        //get the select sql
        $SQL = $this->getSelectSql();
        //query
        $st = $this->query($SQL);
        if ($st !== false) {
            //return the result
            return $st->fetch($fetch_type);
        }
        return false;
    }

    /**
     * @param int $fetch_type
     * @return array
     */
    public function getAll($fetch_type = \PDO::FETCH_ASSOC)
    {
        //get the select sql
        $SQL = $this->getSelectSql();
        //query
        $st = $this->query($SQL);
        if ($st) {
            return $st->fetchAll($fetch_type);
        }
        return array();
    }

    /**
     * (PHP 5 &gt;= 5.1.0, PECL pdo &gt;= 0.2.0)<br/>
     * Executes an SQL statement, returning a result set as a PDOStatement object
     * @link http://php.net/manual/en/pdo.query.php
     * @param string $statement <p>
     * The SQL statement to prepare and execute.
     * </p>
     * <p>
     * Data inside the query should be properly escaped.
     * </p>
     * @return \PDOStatement|false <b>PDO::query</b> returns a PDOStatement object, or <b>FALSE</b>
     * on failure.
     */
    public function query($statement)
    {
        $res = false;
        $this->trace(function () use ($statement, &$res) {
            $res = $this->pdo()->query($statement);
            $this->logSql($statement);
            if ($this->_debug) {
                print_r(sprintf("[SQL Query]: %s %s\r\n", $statement, ($res == true ? '[true]' : '[false]')));
            }
        });
        return $res;
    }

    /**
     * @param $statement
     * @return bool
     */
    public function exec($statement)
    {
        $res = false;
        $this->trace(function () use ($statement, &$res) {
            $res = $this->pdo()->exec($statement);
            $this->logSql($statement);
            if ($this->_debug) {
                print_r(sprintf("[SQL Execute]: %s %s\r\n", $statement, ($res == true ? '[true]' : '[false]')));
            }
        });
        return $res;
    }

    /**
     * @param $callback
     * @return $this
     */
    public function trace($callback)
    {
        if ($callback instanceof \Closure) {
            $time_arr = explode(' ', microtime(false));
            $start_time = $time_arr[0] + $time_arr[1];
            $start_memory = memory_get_usage();
            if ($this->_debug) {
                print_r(sprintf("[Start Time:%s, Memory:%skb]\r\n", date("H:i:s", intval($time_arr[1])) . substr($time_arr[0], 1), strval($start_memory)));
            }
            //callback
            call_user_func_array($callback, array());
            //end time
            $time_arr = explode(' ', microtime(false));
            $end_time = $time_arr[0] + $time_arr[1];
            $send_memory = memory_get_usage();
            if ($this->_debug) {
                $runtime = number_format($end_time - $start_time, 10);
                $used_memory = number_format((memory_get_usage() - $start_memory) / 1024, 2);
                print_r(sprintf("[End Time:%s, Memory:%skb, Runtime:%s]\r\n", date("H:i:s", intval($time_arr[1])) . substr($time_arr[0], 1), strval($send_memory), $runtime, $used_memory));
            }
        }
        return $this;
    }

    /**
     * @var array
     */
    protected $logSQLs = array();

    /**
     * pop out the last one SQL;
     * @return string
     */
    public function getLastSql()
    {
        $SQL = $this->logSQLs[count($this->logSQLs) - 1];
        return $SQL ? $SQL : "";
    }

    /**
     * @param string $sql
     * @return $this
     */
    protected function logSql($sql)
    {
        $this->logSQLs[] = $sql;
        return $this;
    }

    /**
     * @param $name
     * @param $arguments
     */
    function __call($name, $arguments)
    {
        if (!method_exists($this, $name)) {
            if (method_exists($this->pdo(), $name)) {
                call_user_func_array(array($this->pdo(), $name), $arguments);
            } else {
                die("there is not exists method:" . __CLASS__ . "->{$name}");
            }
        }
    }
}