<?php
namespace Servit\Restsrv\RestServer;

use Servit\Restsrv\Libs\DbTrait;
use Servit\Restsrv\Libs\Nonce;
use Servit\Restsrv\Libs\Csrf;
use Servit\Restsrv\RestServer\RestController;
use Servit\Restsrv\RestServer\RestException;
use \Servit\Restsrv\Libs\Request;

class ThemeController extends RestController
{

    protected $theme = null; //   /page/themes/admin/   have   / first and  last
    protected $themepath = null;


    public function gettheme(){
        if($this->theme) return $this->theme;
        return;
    }

    protected function get_themeurl()
    {
        if ($this->theme) {
            return '/page/themes/'.$this->theme.'/';
        }
        return false;
    }

    protected function get_header()
    {
        $headerpath = $this->themepath.'/../page/themes/'.$this->theme.'/header.php';
        if (file_exists($headerpath)) {
            require_once $headerpath;
        }
        return;
    }

    protected function get_footer()
    {
        $footerpath = $this->themepath.'/../page/themes/'.$this->theme.'/footer.php';
        if (file_exists($footerpath)) {
            require_once $footerpath;
        }
        return ;
    }

    protected function breadcrumb()
    {
        $breadcrumb =  explode('/', $this->server->url);
        $count = count($breadcrumb)?:1;
        $url = '/';
        $first = 1;
        $b = '<ol class="breadcrumb">';
        for ($i=0; $i< $count; $i++) {
            $url .= $breadcrumb[$i].'/';
            if ($first) {
                $b .='<li><a href="'.$url.'"><i class="fa fa-dashboard"></i>'.$breadcrumb[$i].'</a></li>';
                $first = 0;
            } else {
                if ($count-$i == 1) {
                    $b .='<li class="active">'.$breadcrumb[$count-1].'</li>';
                } else {
                    $b .='<li><a href="'.$url.'">'.$breadcrumb[$i].'</a></li>';
                }
            }
        }
        $b .='</ol>';
        return $b;
    }
}
