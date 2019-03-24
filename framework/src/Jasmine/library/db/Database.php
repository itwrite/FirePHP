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
 * above methods are belong to PDO
 * Class Database
 * @package Jasmine\library\db
 */
class Database extends Builder
{
    /**
     * @var bool
     */
    protected $debug = false;

    /**
     * @var null|\PDO
     */
    protected $pdo = null;

    /**
     * @var null|\PDO
     */
    protected $write_pdo = null;

    /**
     * @var bool
     */
    protected $distributed = false;

    /**
     * Database constructor.
     * @param \PDO $PDO
     */
    public function __construct(\PDO $PDO = null)
    {
        parent::__construct();
        $this->pdo = $PDO;
    }

    /**
     * @return null|\PDO
     */
    public function getPdo()
    {
        return $this->pdo;
    }

    /**
     *
     * User: Peter
     * Date: 2019/3/24
     * Time: 21:52
     *
     * @param \PDO $PDO
     */
    public function setPdo(\PDO $PDO){
        $this->pdo = $PDO;
    }

    /**
     *
     * User: Peter
     * Date: 2019/3/20
     * Time: 3:27
     *
     * @return \PDO|null
     */
    public function getWritePdo()
    {
        return $this->write_pdo;
    }

    /**
     *
     * User: Peter
     * Date: 2019/3/20
     * Time: 3:28
     *
     * @param \PDO $PDO
     * @return $this
     */
    public function setWritePdo(\PDO $PDO)
    {
        $this->write_pdo = $PDO;
        return $this;
    }

    /**
     *
     * User: Peter
     * Date: 2019/3/20
     * Time: 3:58
     *
     * @return bool
     */
    public function getDistributed()
    {
        return $this->distributed;
    }

    /**
     *
     * User: Peter
     * Date: 2019/3/24
     * Time: 19:51
     *
     * @param bool $distributed
     * @return $this
     */
    public function setDistributed($distributed = false)
    {
        $this->distributed = $distributed;
        return $this;
    }

    /**
     * @param bool $debug
     * @return $this
     */
    public function debug($debug = true)
    {
        if (func_num_args() > 0) {
            $this->debug = $debug;
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
    public function find($fetch_type = \PDO::FETCH_ASSOC)
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
     * Desc:
     * User: Peter
     * Date: 2019/3/5
     * Time: 23:55
     *
     * @param string $fields
     * @param int $fetch_type
     * @return array|Builder
     */
    public function select($fields = '*', $fetch_type = \PDO::FETCH_ASSOC)
    {
        parent::fields($fields);
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
            $str = strtolower($statement);
            if ($this->distributed && $this->write_pdo != null && (strpos($str, 'insert ') > -1
                    || strpos($str, 'delete ') > -1 || strpos($str, 'update ') > -1
                    || strpos($str, 'replace ') > -1 || strpos($str, 'truncate ') > -1
                    || strpos($str, 'create ') > -1 || strpos($str, 'set ') > -1)
            ) {
                $res = $this->getWritePdo()->query($statement);
            } else {
                $res = $this->getPdo()->query($statement);
            }
            $this->logSql($statement);
            if ($this->debug) {
                print_r(sprintf("[SQL Query]: %s %s\r\n", $statement, ($res == true ? '[true]' : '[false]')));
            }
        });
        return $res;
    }

    /**
     *
     * User: Peter
     * Date: 2019/3/20
     * Time: 3:26
     *
     * @param $statement
     * @return bool
     */
    public function exec($statement)
    {
        $res = false;
        $this->trace(function () use ($statement, &$res) {
            $str = strtolower($statement);
            if ($this->distributed && $this->write_pdo != null && (strpos($str, 'insert ') > -1
                    || strpos($str, 'delete ') > -1 || strpos($str, 'update ') > -1
                    || strpos($str, 'replace ') > -1 || strpos($str, 'truncate ') > -1
                    || strpos($str, 'create ') > -1 || strpos($str, 'set ') > -1)
            ) {
                $res = $this->getWritePdo()->exec($statement);
            } else {
                $res = $this->getPdo()->exec($statement);
            }

            $this->logSql($statement);
            if ($this->debug) {
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
            if ($this->debug) {
                print_r(sprintf("[Start Time:%s, Memory:%skb]\r\n", date("H:i:s", intval($time_arr[1])) . substr($time_arr[0], 1), strval($start_memory)));
            }
            //callback
            call_user_func_array($callback, array());
            //end time
            $time_arr = explode(' ', microtime(false));
            $end_time = $time_arr[0] + $time_arr[1];
            $send_memory = memory_get_usage();
            if ($this->debug) {
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
            if (method_exists($this->getPdo(), $name)) {
                call_user_func_array(array($this->getPdo(), $name), $arguments);
            } else {
                die("there is not exists method:" . __CLASS__ . "->{$name}");
            }
        }
    }
}