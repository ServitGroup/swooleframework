<?php
namespace Servit\Restsrv\RestServer;

use Servit\Restsrv\Libs\Request;
use Servit\Restsrv\RestServer\Restjwt;

class RestController
{

    public $input;
    public $request;
    public function __construct()
    {
        // consolelog('-----construct----first function----------------');
        // $hostName = explode(':',$_SERVER['HTTP_HOST'])[0];
        // $file = @file_get_contents('http://127.0.0.1:8000/license.txt');
        // $file = @file_get_contents(__DIR__.'/license.txt');
        // if(strpos($file,$hostName) == false){
        //     exit("Domain [$hostName] not registered. Please contact (limweb@hotmail.com) for a license");
        // }
        $this->jwt = (new Restjwt());
        $this->rbac = (new RestRbac($this->jwt));
        $this->input = Request::getInstance();
        if (!SWOOLEMODE) {

        }
    }

    public function init()
    {
        // consolelog('-----init------second function --------------');
        // dump($this->jwt->tokenverify());
        // dump($this->jwt->chkauth());
    }

    public function authorize()
    {
        // consolelog('-----Authorize------third function --------------');
        $chk = 0;
        if (AUTHTYPE == 'session') {
            if ($this->input->user) {
                $chk = 1;
            }
        } else {
            if ($this->jwt) {
                $this->jwt->server = $this->server;
                $chk = $this->jwt->chkauth();
                // dump($chk);
            }
        }
        // dump($chk);
        // consolelog('---chk--', $chk);
        return $chk;
    }

    /**
     * @noAuth
     * @url GET /routes
     */
//     public function getRoutes()
    //     {
    //         if ($this->server->mode == 'debug') {
    //             echo '<style> .divline { width:100%; text-align:center; border-bottom: 1px dashed #000; line-height:0.1em; margin:10px 0 20px; }
    //             </style>
    //             <center><table><thead><tr><td><b>Route</b></td><td><b>Controller</b></td><td><b>Method</b></td><td><b>$args</b></td><td>null</td><td><b>@noAuth</b></td></tr></thead><tbody>';
    //             foreach ($this->server->routes() as $routekey => $routes) {
    //                 echo '<tr><td colspan="6"><div style="display:flex;padding-right:10px;height:15px;">
    //                 <div class="divline" style="width:200px;">&nbsp;</div>
    //                 <span style="white-space: pre;">&nbsp;>&nbsp;@url ' . $routekey . '&nbsp;</span>
    //                 <div class="divline">&nbsp;</div>
    //                 </div>
    //                 </td></tr>';
    //                 switch ($routekey) {
    //                     case 'GET':
    //                         foreach ($routes as $key => $value) {
    //                             if (strtolower($value[0]) == strtolower(get_class($this))) {
    //                                 echo "<tr><td>" . ($routekey == 'GET' ? '<a href="http://' . $_SERVER['HTTP_HOST'] . '/' . $key . '">' . (empty($key) ? '/' : $key) . '</a>' : $key) . "</td><td>$value[0]</td><td>$value[1]</td><td><pre>" . json_encode($value[2]) . "</pre></td><td>" . json_encode($value[3]) . "</td><td>" . json_encode($value[4]) . "</td></tr>";
    //                             }
    //                         }
    //                         break;
    //                     case 'POST':
    //                     case 'OPTIONS':
    //                     default:
    //                         foreach ($routes as $key => $value) {
    //                             if (strtolower($value[0]) == strtolower(get_class($this))) {
    //                                 echo "<tr><td style='cursor:pointer;' onclick='alert(\"" . $key . "\")'>$key</td><td>$value[0]</td><td>$value[1]</td><td><pre>" . json_encode($value[2]) . "</pre></td><td>" . json_encode($value[3]) . "</td><td>" . json_encode($value[4]) . "</td></tr>";
    //                             }
    //                         }
    //                         break;
    //                 }
    //             }
    //             echo '<tr><td colspan="6"><div style="display:flex;padding-right:10px;height:15px;">
    //             <div class="divline">&nbsp;</div>
    //             <span style="white-space: pre;">&nbsp;>&nbsp;END.&nbsp;</span>
    //             </div></td></tr>';
    //             echo '</tbody></table></center>';
    //         }
    //         exit(0);
    //     }
}
