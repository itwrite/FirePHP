<?php
/**
 * Created by PhpStorm.
 * User: zzpzero
 * Date: 2018/4/24
 * Time: 10:10
 */

namespace Jasmine\library\db;

use Jasmine\library\db\connection\Connection;
use Jasmine\library\db\connection\Link;

require_once("Builder.php");
require_once("connection/Connection.php");
/**
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
     * @var Connection|null
     */
    protected $Connection = null;

    /**
     * @var bool
     */
    private $_sticky = false;


    public function __construct(Connection $connection)
    {
        parent::__construct();
        /**
         *
         */
        if ($connection instanceof Connection) {
            $this->Connection = $connection;
        }
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
     * 粘合处理
     * User: Peter
     * Date: 2019/3/28
     * Time: 11:11
     *
     * @param null $callback
     * @return $this
     */
    public function masterProcess($callback = null)
    {
        $sticky = $this->_sticky;
        $this->_sticky = true;
        if ($callback instanceof \Closure || is_callable($callback)) {
            call_user_func_array($callback, [$this]);
        }
        $this->_sticky = $sticky;
        return $this;
    }

    /**
     *
     * User: Peter
     * Date: 2019/3/31
     * Time: 14:22
     *
     * @return Connection|null
     */
    public function getConnection(){
        return $this->Connection;
    }
    /**
     *
     * User: Peter
     * Date: 2019/3/31
     * Time: 13:41
     *
     * @param string $type
     * @return array|Link
     * @throws \ErrorException
     */
    public function getLink($type=''){
        if($this->_sticky){
            return $this->getConnection()->getMasterLink();
        }

        if($type!='m'){
            $link = $this->getConnection()->getSlaveLink();
            if($link instanceof Link){
                return $link;
            }
        }
        $link = $this->getConnection()->getMasterLink();
        if($link instanceof Link){
            return $link;
        }
        throw new \ErrorException('Database connection error.');
    }

    /**
     *
     * User: Peter
     * Date: 2019/3/31
     * Time: 13:52
     *
     * @param string $type
     * @return null|\PDO
     * @throws \ErrorException
     */
    function getPdo($type=''){
        return $this->getLink($type)->getPdo();
    }

    /**
     *
     * User: Peter
     * Date: 2019/3/30
     * Time: 23:17
     *
     * @param array $data
     * @param bool $is_replace
     * @return int
     * @throws \ErrorException
     */
    public function insert(Array $data, $is_replace = false)
    {
        $Link = $this->getLink('m');
        if (empty($data)) return 0;
        //set data
        $this->set($data);
        //get the insert sql
        $SQL = $is_replace ? $Link->getGrammar()->toReplaceSql($this) : $Link->getGrammar()->toInsertSql($this);
        //begin transaction
        $Link->getPdo()->beginTransaction();
        //execute the sql
        $this->exec($SQL);
        //get the inserted Id
        $lastInsertId =  $Link->getPdo()->lastInsertId();
        //commit
        $res =  $Link->getPdo()->commit();
        if ($res === false) {
            //if failure, roll back the sql;
            $Link->getPdo()->rollBack();
            $errorInfo =  $Link->getPdo()->errorInfo();
            die(isset($errorInfo[2]) ? $errorInfo[2] : "");
        }
        return intval($lastInsertId);
    }

    /**
     *
     * User: Peter
     * Date: 2019/3/30
     * Time: 23:24
     *
     * @return bool
     * @throws \ErrorException
     */
    public function delete()
    {
        //get the delete sql
        $SQL = $this->getLink('m')->getGrammar()->toDeleteSql($this);

        return $this->exec($SQL);
    }

    /**
     *
     * User: Peter
     * Date: 2019/3/30
     * Time: 23:24
     *
     * @param array $data
     * @return bool
     * @throws \ErrorException
     */
    public function update(Array $data)
    {
        //set data
        $this->set($data);
        //get the update sql;
        $SQL = $this->getLink('m')->getGrammar()->toUpdateSql($this);

        return $this->exec($SQL);
    }

    /**
     *
     * User: Peter
     * Date: 2019/3/30
     * Time: 23:29
     *
     * @return int|mixed
     * @throws \ErrorException
     */
    public function count()
    {
        $this->limit(1);
        //get the select sql
        $SQL = $this->getLink()->getGrammar()->toCountSql($this);
        //query
        $st = $this->query($SQL);
        if ($st !== false) {
            return $st->fetch(\PDO::FETCH_COLUMN);
        }
        return 0;
    }

    /**
     *
     * User: Peter
     * Date: 2019/3/30
     * Time: 23:30
     *
     * @param int $fetch_type
     * @return bool|mixed
     * @throws \ErrorException
     */
    public function find($fetch_type = \PDO::FETCH_ASSOC)
    {
        $this->limit(1);
        //get the select sql
        $SQL = $this->getLink()->getGrammar()->toSelectSql($this);
        //query
        $st = $this->query($SQL);
        if ($st !== false) {
            //return the result
            return $st->fetch($fetch_type);
        }
        return false;
    }

    /**
     *
     * User: Peter
     * Date: 2019/3/30
     * Time: 23:30
     *
     * @param string $fields
     * @param int $fetch_type
     * @return array
     * @throws \ErrorException
     */
    public function select($fields = '*', $fetch_type = \PDO::FETCH_ASSOC)
    {
        parent::fields($fields);
        //get the select sql
        $SQL = $this->getLink()->getGrammar()->toSelectSql($this);
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
            if($this->isWriteAction($statement)){
                $res = $this->getLink('m')->getPdo()->query($statement);
            }else{
                $res = $this->getLink()->getPdo()->query($statement);
            }
            $this->logSql($statement);
            if ($this->debug) {
                print_r(sprintf("[SQL Query]: %s %s\r\n", $statement, ($res != false ? '[true]' : '[false]')));
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
            if($this->isWriteAction($statement)){
                $res = $this->getLink('m')->getPdo()->exec($statement);
                $res==false && print_r($this->getLink('m')->getPdo()->errorInfo());
            }else{
                $res = $this->getLink()->getPdo()->exec($statement);
                $res==false && print_r($this->getLink()->getPdo()->errorInfo());
            }
            $this->logSql($statement);
            if ($this->debug) {
                print_r(sprintf("[SQL Execute]: %s %s\r\n", $statement, ($res != false ? '[true]' : '[false]')));

            }
        });
        return $res;
    }

    /**
     *
     * User: Peter
     * Date: 2019/3/26
     * Time: 0:50
     *
     * @param $statement
     * @return bool
     */
    protected function isWriteAction($statement)
    {
        $statement = strtolower(trim($statement));
        if (strpos($statement, 'insert ') == 0
            || strpos($statement, 'delete ') == 0
            || strpos($statement, 'update ') == 0
            || strpos($statement, 'replace ') == 0
            || strpos($statement, 'truncate ') == 0
            || strpos($statement, 'create ') == 0
        ) {
            return true;
        }
        return false;
    }

    /**
     *
     * User: Peter
     * Date: 2019/3/26
     * Time: 1:14
     *
     * @param $statement
     * @return bool|int
     */


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

}