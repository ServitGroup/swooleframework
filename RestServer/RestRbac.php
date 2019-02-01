<?php

namespace Servit\Restsrv\RestServer;
use Servit\Restsrv\RestServer\Restjwt;

class RestRbac
{
    private $rbac = ['r','c','u','d','p','e','a','t'];
    protected $modules = ['a','b','c']; // module
    private $role = null;
    private $level = null;

    public function __construct($jwt = null)
    {
        $this->jwt = ($jwt ? $jwt  : (new RestJwt()) );
        if ($this->jwt) {
            $jwtdata =  $this->jwt->getJwtobjdata();
            if ($jwtdata) {
                $this->role = $jwtdata->role;
                $this->level = $jwtdata->level;
            }
        }
    }

    public function hasRole($r)
    {
 // admin   user  ['admin','user']
        if ($this->jwt && $this->jwt->getStatus()) {
            $roles = (is_string($r) ? [ $r ] : $r );
            foreach ($roles as $role) {
                if ($this->jwt->chkauth() && $this->role == $role) {
                    return true;
                }
            }
            return false;
        } else {
            return false;
        }
    }


    //------------------ RBAC ---------------------------
    //---- check ----- role base system ------------
    public function chk($module = null, $action = null)
    {
        $chk = 1;
        if (USEDROLE && $this->role) {
                $str = $this->level;
                dump($module, $str);
                $ls = explode(':', chunk_split($str, 2, ':'));
                dump($ls);
                $m = array_search($module, $this->modules);
                dump('m=', $m);
                (is_numeric($m) ? null : $chk = 0 );
                $levelhx = $ls[$m];
                ($levelhx ? null : $chk=0 );
            if ($chk) {
                $level = base_convert($levelhx, 16, 2);
                $a = array_search($action, $this->actions);
                (is_numeric($a) ? null : $chk = 0 );
            }
                return ($chk ? $level[$a] : 0 );
        } else {
            return false;
        }
    }



    public function __call($name, $m = null)
    {
        $match = preg_split('@(?=[A-Z])@', $name);
        if (count($match) >= 2 && isset($match[0]) && $match[0] == 'can') {
            if ($m) {
                return $m[0];// ยังไม่ได้ทำ
            } else {
                $ac = $this->actions[strtolower($match[1])];

                dump($this->chk('a'));
                if ($ac) {
                    return (bool) in_array($ac, $this->rbac);
                }
            }
        } else {
            return false;
        }
    }
    //------------------ RBAC ---------------------------
    private $actions = [
        'read' => 'r',
        'create' =>'c',
        'insert'=>'c',
        'update'=>'u',
        'edit'=>'u',
        'earse' => 'd',
        'delete' => 'd',
        'destroy' => 'd',
        'del'=>'d',
        'print'=>'p',
        'export'=>'e',
        'auth'=>'a',
        'etc'=>'t',
        'custom'=>'t'
        ];
}

// admin:FF
// user:Fafffafofef100113945
// 
