<?php
namespace Servit\Restsrv\Service;
use \Servit\Restsrv\RestServer\RestException;
use \Servit\Restsrv\Traits\DbTrait;
use \Servit\Restsrv\Service\BaseService;

class BasedbService extends BaseService {
    use DbTrait;  
    public function __construct(){
        parent::__construct();
    }

    protected function model()
    {
        return null;
    }
}