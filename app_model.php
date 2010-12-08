<?php
/**
* App model structure to make us eof Master-Slave database setup
*
*/
class AppModel extends Model 
{
    /**
    * Holds the previous Database config name after switching
    *
    * @var string
    */
    public $prevDbConfig = null;

    /**
    * Make Model::save() write on master
    * @see http://bakery.cakephp.org/articles/gman.php/2009/01/20/master-slave-support-also-with-multiple-slave-support
    */
    public function save($data = null, $validate = true, $fieldList = array()) {
        $this->_switchDbConfig('master');
        $return = parent::save($data, $validate, $fieldList);
        $this->_switchDbConfig();

        return $return;
    }

    /**
    * Make Model::updateAll() write on master
    * @see http://bakery.cakephp.org/articles/gman.php/2009/01/20/master-slave-support-also-with-multiple-slave-support
    */
    public function updateAll($fields, $conditions = true) {
        $this->_switchDbConfig('master');
        $return = parent::updateAll($fields, $conditions);
        $this->_switchDbConfig();

        return $return;
    }

    /**
    * Make Model::delete() write on master
    */
    public function delete($id = null, $cascade = true) {
        $this->_switchDbConfig('master');
        $return = parent::delete($id, $cascade);
        $this->_switchDbConfig();

        return $return;
    }

    /**
    * Make Model::query() write on master
    * @see http://bakery.cakephp.org/articles/eagerterrier/2007/05/26/load-balancing-and-mysql-master-and-slaves-2#comment4caea110-13cc-4dc7-966f-493482f0cb67
    */
    public function query() {
        $params = func_get_args();

        if(!empty($params) && is_string($params[0])) {
            $updates = array('CREATE', 'DELETE', 'DROP', 'INSERT', 'UPDATE');
            if(preg_match('/^(' . implode('|', $updates) . ')/i', trim($params[0]))) {
                $this->_switchDbConfig('master');
            }
        }

        if(!empty($params)) {
            $result =& call_user_func_array(array($this, 'parent::query'), $params);
        }

        $this->_switchDbConfig();

        return $result;
    }

    /**
    * Switches database config to new config
    * When $config is not null it will switch to that $config and set the previous db config var
    * When it is called without $config it will check if $prevDbConfig was set and switch to that
    * @param string $config Databse config name
    */
    private function _switchDbConfig($config = null)
    {
        if (empty($config)) {
            $this->prevDbConfig = $this->useDbConfig;
            $this->setDataSource($config);
        }
        elseif (!empty($this->prevDbConfig)) {
            $this->setDataSource($this->prevDbConfig);
            $this->prevDbConfig = null;
        }        
        return true;
    }
}