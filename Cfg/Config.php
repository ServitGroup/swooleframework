<?php
namespace  Servit\Restsrv\Cfg;

class Config
{

    protected $values;
    
    public function __construct($obj = null)
    {
        if ($obj) {
            $this->values = $obj;
        } else {
            $this->values = new \stdClass();
        }
    }

    public function __get($prop)
    {
        return (isset($this->values->$prop) ? $this->values->$prop : null  );
    }

    public function __set($prop, $value)
    {
         $this->values->$prop = $value;
    }

    public function __toString()
    {
        return $this->values;
    }

    public function toArray()
    {
        return   json_decode(json_encode($this->values), true);
    }
}
