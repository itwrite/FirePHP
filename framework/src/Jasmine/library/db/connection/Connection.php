<?php
/**
 * Created by PhpStorm.
 * User: zzpzero
 * Date: 2018/4/24
 * Time: 10:09
 */

namespace Jasmine\library\db\connection;

require_once 'Link.php';
class Connection
{


    /**
     * 主库【读、写】
     * deployed
     * @var Link
     */
    protected $masterLink = null;

    /**
     * 从库【读】
     * @var array
     */
    protected $slaveLink = [];


    function __construct(array $config)
    {
        /**
         *
         */
        $master_config = isset($config['master']) && is_array($config['master']) ? $config['master'] : [];
        $this->masterLink = new Link($master_config);

        /**
         *
         */
        $slave_config = isset($config['slave']) && is_array($config['slave']) ? $config['slave'] : [];
        $this->slaveLink = new Link($slave_config);

    }

    /**
     *
     * User: Peter
     * Date: 2019/3/30
     * Time: 23:16
     *
     * @return Link
     */
    public function getMasterLink()
    {
        return $this->masterLink;
    }


    /**
     *
     * User: Peter
     * Date: 2019/3/30
     * Time: 18:17
     *
     * @return array
     */
    public function getSlaveLink()
    {
       return $this->slaveLink;
    }
}