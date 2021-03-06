<?php
namespace Atlas\Database;

class Factory
{
    protected $_config;

    public function __construct($config)
    {
        $this->_config = $config;
    }

    public function adapter($mode)
    {
        if (empty($mode)) {
            throw new Exception("Db adapter mode not specified");
        }

        if (!in_array($mode, array('write','read'))) {
            throw new Exception("Invalid db adapter mode '{$mode}' specified");
        }

        if (!array_key_exists($mode, $this->_config)) {
            throw new Exception("Malformed db adapter {$mode} config specified");
        }

        return new \PDO(
            $this->_config[$mode]['dsn'],
            $this->_config[$mode]['username'],
            $this->_config[$mode]['password'],
            array(\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION)
        );
    }

    public function fetch($resolver, $key)
    {
        $mapper = $resolver->mapper();

        $select = $this->_getSelect($mapper);
        $select->where()->isEqual('id', $key);

        return new Sql\Fetch(
            $this->adapter('read'), $mapper, $select
        );
    }

    public function relation($resolver, $entity)
    {
        if (is_numeric($entity)) {
            $entity = $this->fetch($key);
        }

        return $resolver->relation($this, $entity);
    }

    public function named($resolver)
    {
        return $resolver->named($this);
    }
    
    public function query($resolver, $ignoreEmptyValues = false)
    {
        $mapper = $resolver->mapper();

        return $resolver->query(
            $this->adapter('read'), $mapper, $this->_getSelect($mapper)
        );
    }

    public function write($resolver)
    {
        return new Write(
            $this->adapter('write'), $resolver->mapper() 
        );
    }

    protected function _getSelect($mapper)
    {
        return Sql\Select::factory($mapper->getAlias());
    }
}
