<?php
namespace Servit\Restsrv\RestServer\Auth;

class HTTPAuthServer implements \Servit\Restsrv\RestServer\AuthServer
{
    protected $realm;

    public function __construct($realm = 'Rest Server')
    {
        $this->realm = $realm;
    }

    public function isAuthorized($classObj)
    {
        if (method_exists($classObj, 'authorize')) {
            return $classObj->authorize();
        }

        return true;
    }

    public function unauthorized($classObj)
    {
        // dump($classObj);
        // header('Location: /login');
        // header("WWW-Authenticate: Basic realm=\"$this->realm\"");
        throw new \Servit\Restsrv\RestServer\RestException(401, "You are not authorized to access this resource.");
    }
}
