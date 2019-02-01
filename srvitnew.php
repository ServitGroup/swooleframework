<?php

namespace Servit\Restsrv\RestServer;

// require_once __DIR__ . '/../Libs/utils.php';

// use Servit\Restsrv\RestServer\RestRbac;
use Servit\Restsrv\RestServer\RestFormat;
// use Servit\Restsrv\RestServer\RestException;
// use Servit\Restsrv\RestServer\RestJwt;
// use Servit\Restsrv\RestServer\RestRbac;
// use Servit\Restsrv\RestServer\RestController;
// use Servit\Restsrv\RestServer\AuthServer;
// use Servit\Restsrv\RestServer\Auth\HTTPAuthServer;

// use Servit\Restsrv\Libs\Request;
// use Exception;
// use ReflectionClass;
// use ReflectionObject;
// use ReflectionMethod;
// use DOMDocument;
// use Illuminate\Database\Capsule\Manager as Capsule;
// use Illuminate\Events\Dispatcher;
// use Illuminate\Container\Container;

//------------- INIT----------------------------------------

/**
 * Description of RestServer
 *
 * @author jacob
 */
class RestServer
{
    //@todo add type hint
    public $url;
    public $method;
    public $params;
    public $format;
    public $realm;
    public $mode;
    public $root;
    public $rootPath;
    public $serverpath;
    public $jsonAssoc = false;
    public $useCors = false;
    public $allowedOrigin = '*';
    protected $map = array();
    protected $errorClasses = array();
    protected $capsule;
    protected $authHandler = null;
    private $_token = null; // string payload  header.payload.sinager

    //----------swoole object------------------
    protected $http;
    protected $request;
    protected $response;
    //----------swoole object------------------

    /**
     * The constructor.
     *
     * @param string $mode The mode, either debug or production
     */
    public function __construct(\Servit\Restsrv\Cfg\Config $config = null, $mode = 'debug', $realm = 'Rest Server')
    {
        $this->mode = $mode;
        $this->format = RestFormat::HTML;
        $this->realm = $realm;
        $this->code = 200;
        $dir = str_replace('\\', '/', dirname(str_replace($_SERVER['DOCUMENT_ROOT'], '', $_SERVER['SCRIPT_FILENAME'])));

        if ($dir == '.') {
            $dir = '/';
        } else {
            if (substr($dir, -1) == '/') {
                $dir = substr($dir, 0, -1);
            }

            if (substr($dir, 0, 1) != '/') {
                $dir = '/' . $dir;
            }

        }

        if ($config) {
            $this->config = $config;
            $this->capsule = $config->capsule;
        } else {
            $this->config = new Config();
        }
        $this->root = $dir;
        $this->serverpath = glob($_SERVER["DOCUMENT_ROOT"])[0];
        $this->setAuthHandler(new \Servit\Restsrv\RestServer\Auth\HTTPAuthServer);
    }

    public function setAuthHandler($authHandler)
    {
        if ($authHandler instanceof AuthServer) {
            $this->authHandler = $authHandler;
        }
    }

    public function routes()
    {
        return $this->map;
    }

    public function getPath()
    {
        $this->query = $_GET;
        $path = preg_replace('/\?.*$/', '', $_SERVER['REQUEST_URI']);
        if ($this->root) {
            $path = preg_replace('/^' . preg_quote($this->root, '/') . '/', '', $path);
        }

        $dot = strrpos($path, '.');
        if ($dot !== false) {
            $path_format = substr($path, $dot + 1);
            foreach (RestFormat::$formats as $format => $mimetype) {
                if ($path_format == $format) {
                    $path = substr($path, 0, $dot);
                    break;
                }
            }
        }
        if ($this->rootPath) {
            $path = str_replace($this->rootPath, '', $path);
        }

        return ltrim($path, '/');
    }

    public function getMethod()
    {
        $method = $this->request->server['request_method'];
        return $method;
    }

    public function getFormat()
    {
        $format = RestFormat::HTML;
        $accept_mod = null;

        if (isset($_SERVER["HTTP_ACCEPT"])) {
            $accept_mod = preg_replace('/\s+/i', '', $_SERVER['HTTP_ACCEPT']); // ensures that exploding the HTTP_ACCEPT string does not get confused by whitespaces
        }

        $accept = explode(',', $accept_mod);
        $override = '';

        if (isset($_REQUEST['format']) || isset($_SERVER['HTTP_FORMAT'])) {
            $override = isset($_SERVER['HTTP_FORMAT']) ? $_SERVER['HTTP_FORMAT'] : '';
            $override = isset($_REQUEST['format']) ? $_REQUEST['format'] : $override;
            $override = trim($override);
        }

        if (preg_match('/\.(\w+)$/i', strtok($_SERVER["REQUEST_URI"], '?'), $matches)) {
            $override = $matches[1];
        }

        $override = isset($_GET['format']) ? $_GET['format'] : $override;
        if (isset(RestFormat::$formats[$override])) {
            $format = RestFormat::$formats[$override];
        } else if (in_array(RestFormat::JSON, $accept)) {
            $format = RestFormat::JSON;
        }

        return $format;
    }

    public function getData()
    {
        if ($this->data) {

            return $this->data;
        }
    }

    public function sendData($data)
    {
        if (is_array($data)) {
            $this->format = RestFormat::JSON;
            $this->result = json_encode($data, JSON_UNESCAPED_UNICODE);
        } else {
            $this->result = $data;
        }
    }

    public function setStatus($code)
    {
        $this->code = $code;
    }

    public function getStatusCode()
    {
        return $this->code;
    }

    protected function findUrl()
    {
        $urls = isset($this->map[$this->method]) ? $this->map[$this->method] : null;
        if (!$urls) {
            return null;
        }

        foreach ($urls as $url => $call) {
            $args = $call[2];

            if (!strstr($url, '$')) {
                if ($url == $this->url) {
                    $params = array();
                    if (isset($args['data'])) {
                        $params += array_fill(0, $args['data'] + 1, null);
                        $params[$args['data']] = $this->data;
                    }
                    if (isset($args['query'])) {
                        $params += array_fill(0, $args['query'] + 1, null);
                        $params[$args['query']] = $this->query;
                    }
                    $call[2] = $params;
                    return $call;
                }
            } else {
                $regex = preg_replace('/\\\\\$([\w\d]+)\.\.\./', '(?P<$1>.+)', str_replace('\.\.\.', '...', preg_quote($url)));
                $regex = preg_replace('/\\\\\$([\w\d]+)/', '(?P<$1>[^\/]+)', $regex);

                if (preg_match(":^$regex$:", urldecode($this->url), $matches)) {
                    $params = array();
                    $paramMap = array();

                    if (isset($args['data'])) {
                        $params[$args['data']] = $this->data;
                    }
                    if (isset($args['query'])) {
                        $params[$args['query']] = $this->query;
                    }

                    foreach ($matches as $arg => $match) {
                        if (is_numeric($arg)) {
                            continue;
                        }

                        $paramMap[$arg] = $match;

                        if (isset($args[$arg])) {
                            $params[$args[$arg]] = $match;
                        }
                    }

                    ksort($params);

                    // make sure we have all the params we need
                    end($params);
                    $max = key($params);
                    for ($i = 0; $i < $max; $i++) {
                        if (!array_key_exists($i, $params)) {
                            $params[$i] = null;
                        }
                    }

                    ksort($params);

                    $call[2] = $params;
                    $call[3] = $paramMap;

                    return $call;
                }
            }
        }
    }

    protected function generateMap($class, $basePath)
    {
        if (is_object($class)) {
            $reflection = new ReflectionObject($class);
        } elseif (class_exists($class)) {
            $reflection = new ReflectionClass($class);
        }

        $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC); //@todo $reflection might not be instantiated
        foreach ($methods as $method) {
            if (!in_array($method->name, ['init', '__construct', 'authorize', '__call', '__destruct']) && substr($method->name, 0, 6) !== "handle") {
                $doc = $method->getDocComment();
                $noAuth = strpos($doc, '@noAuth') !== false;
                $test = strpos($doc, '@Test') !== false;
                $add = true;
                if (APPMODE == 'production' && $test) {
                    $add = false;
                }
                if ($add) {
                    $params = $method->getParameters();
                    if (preg_match_all('/@url[ \t]+(GET|POST|PUT|PATCH|DELETE|HEAD|OPTIONS)[ \t]+\/?(\S*)/s', $doc, $matches, PREG_SET_ORDER)) {
                        foreach ($matches as $match) {
                            $httpMethod = $match[1];
                            $url = $basePath . $match[2];
                            if ($url && $url[strlen($url) - 1] == '/') {
                                $url = substr($url, 0, -1);
                            }
                            $call = array($class, $method->getName());
                            $args = array();
                            foreach ($params as $param) {
                                $args[$param->getName()] = $param->getPosition();
                            }
                            $call[] = $args;
                            $call[] = null;
                            $call[] = $noAuth;
                            $this->map[$httpMethod][$url] = $call;
                        }
                    } else {
                        $chk = 1;
                        $httpmethods = ['get', 'post', 'put', 'patch', 'delete', 'head', 'options'];
                        foreach ($httpmethods as $httpMethod) {
                            $match = preg_split('@(?=[A-Z])@', $method->getName());
                            if ($match[0] == $httpMethod) {
                                $chk = 0;
                                $url = strtolower($basePath . $match[1]);
                                if ($url && $url[strlen($url) - 1] == '/') {
                                    $url = substr($url, 0, -1);
                                }

                                $args = array();
                                foreach ($params as $param) {
                                    $args[$param->getName()] = $param->getPosition();
                                }

                                $call = array($class, $method->getName());
                                $call[] = $args;
                                $call[] = null;
                                $call[] = $noAuth;
                                $this->map[strtoupper($httpMethod)][$url] = $call;
                                foreach ($args as $key => $value) {
                                    $call = array($class, $method->getName());
                                    $url .= '/$' . $key;
                                    $call[] = $args;
                                    $call[] = null;
                                    $call[] = $noAuth;
                                    $this->map[strtoupper($httpMethod)][$url] = $call;
                                }
                            }
                        }

                        if ($chk) {
                            $url = strtolower($basePath . $method->getName());
                            if ($url && $url[strlen($url) - 1] == '/') {
                                $url = substr($url, 0, -1);
                            }

                            $args = array();
                            foreach ($params as $param) {
                                $args[$param->getName()] = $param->getPosition();
                            }
                            $call = array($class, $method->getName());
                            $call[] = $args;
                            $call[] = null;
                            $call[] = $noAuth;
                            $this->map['GET'][$url] = $call;
                            foreach ($args as $key => $value) {
                                $call = array($class, $method->getName());
                                $url .= '/$' . $key;
                                $call[] = $args;
                                $call[] = null;
                                $call[] = $noAuth;
                                $this->map['GET'][$url] = $call;
                            }
                        }
                    }
                }
            }
        }
    }

    public function getResult()
    {
        return $this->result;
    }
    public function setFormat($format)
    {
        $this->format = $format;
    }

    public function setToken($token = null)
    {
        if ($token) {
            $this->_token = $token;
        }
    }
    public function getToken()
    {
        return $this->_token;
    }

    public function options()
    {
        if (CROS) {
            return ['status' => 'success'];
        }throw new RestException(200, "authorized");
    }

    protected function isAuthorized($obj)
    {
        if ($this->authHandler !== null) {
            return $this->authHandler->isAuthorized($obj);
        }

        return true;
    }
    protected function unauthorized($obj)
    {
        if ($this->authHandler !== null) {
            return $this->authHandler->unauthorized($obj);
        }

        throw new RestException(401, "You are not authorized to access this resource.");
    }

    protected function initClass($obj)
    {
        if (method_exists($obj, 'init')) {
            $obj->init();
        }
    }

    protected function instantiateClass($obj)
    {
        if (class_exists($obj)) {
            return new $obj();
        }

        return false;
    }

    public function setRootPath($path)
    {
        $this->rootPath = '/' . trim($path, '/');
    }

    public function setJsonAssoc($value)
    {
        $this->jsonAssoc = ($value === true);
    }
    public function includeDir($path)
    {
        $dir = new \RecursiveDirectoryIterator($path);
        $iterator = new \RecursiveIteratorIterator($dir);
        foreach ($iterator as $file) {
            $fname = $file->getFilename();
            if (preg_match('%\.php$%', $fname)) {
                if ($fname != 'index.php') {
                    require_once $file->getPathname();
                }

            }
        }
    }

    private function corsHeaders()
    {
        $allowedOrigin = (array) $this->allowedOrigin;
        $currentOrigin = !empty($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '*';
        if (in_array($currentOrigin, $allowedOrigin)) {
            $allowedOrigin = array($currentOrigin); // array ; if there is a match then only one is enough
        }
        foreach ($allowedOrigin as $allowed_origin) { // to support multiple origins
            header("Access-Control-Allow-Origin: $allowed_origin");
        }
        $this->response->header('Access-Control-Allow-Origin', '*');
        $this->response->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $this->response->header('Access-Control-Allow-Credentials', 'true');
        $this->response->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
        if ($this->_token) {
            Header('Authorization: ' . $this->_token);
            Header('Authorization: Bearer ' . $this->token);
        }
        if ($this->request->server['request_method'] === 'OPTIONS') {
            $this->response->status(200);
            $this->response->end();
        }
    }

    public function addClass($class, $basePath = '', $sys = '')
    {
        $path = glob($_SERVER["DOCUMENT_ROOT"])[0];
        if ($sys) {
            $sys .= '/';
        }

        $filepath = $path . $this->root . 'controllers/' . $sys . $class . '.php';
        if (file_exists($filepath)) {
            require_once $filepath;
            if (is_string($class) && !class_exists($class)) {
                throw new RestException('Invalid method or class');
            } elseif (!is_string($class) && !is_object($class)) {
                throw new RestException('Invalid method or class; must be a classname or object');
            }

            if (substr($basePath, 0, 1) == '/') {
                $basePath = substr($basePath, 1);
            }
            if ($basePath && substr($basePath, -1) != '/') {
                $basePath .= '/';
            }
            $this->generateMap($class, $basePath);

        }
    }
    public function addThemeClass($class, $sys = '')
    {
        if ($sys) {
            $sys .= '/';
        }

        $path = glob($_SERVER["DOCUMENT_ROOT"])[0];
        $filepath = $path . $this->root . 'controllers/' . $sys . $class . '.php';
        if (file_exists($filepath)) {
            require_once $filepath;
            $class = new $class();
            $class->server = $this;
        }
        $this->errorClasses[] = $class;
    }

    public function addErrorClass($class)
    {
        $this->errorClasses[] = $class;
    }

    public function handleError($statusCode, $errorMessage = null)
    {
        $roottheme = null;
        list($theme) = explode('/', $this->url);
        $method = "handle$statusCode";
        foreach ($this->errorClasses as $class) {
            if (is_object($class)) {
                $reflection = new ReflectionObject($class);
            } elseif (class_exists($class)) {
                $reflection = new ReflectionClass($class);
            }
            if ($class->gettheme() == '') {
                $roottheme = $reflection;
            }
            if ($class->gettheme() == $theme) {
                if (isset($reflection)) {
                    if ($reflection->hasMethod($method)) {
                        $obj = is_string($class) ? new $class() : $class;
                        $obj->$method();
                        return;
                    }
                }
            }
        } // end foreach
        if ($roottheme && $roottheme->hasMethod($method)) {
            $obj = is_string($class) ? new $class() : $class;
            $obj->$method();
            return;
        } else {
            if (!$errorMessage) {
                $errorMessage = $this->codes[$statusCode];
            }
            $this->setStatus($statusCode);
            $this->sendData(array('error' => array('code' => $statusCode, 'message' => $errorMessage)));
        }
    }

    /**
     * @param prefix
     * @param dbname
     * @param host
     * @param username
     * @param password
     */

    public function setConnection($prefix = '', $dbname = null, $host = null, $username = null, $password = null, $charset = 'utf8', $collation = 'utf8_unicode_ci', $connection = 'default')
    {
        // for new and reset config
        $config = $this->config->dbconfig;
        $config['database'] = ($dbname ?: DB_NAME);
        $config['prefix'] = ($prefix ?: '');
        $config['host'] = ($host ?: DB_HOST);
        $config['username'] = ($username ?: DB_USER);
        $config['password'] = ($password ?: DB_PASSWORD);
        $config['charset'] = $charset;
        $config['collation'] = $collation;
        $this->capsule = new Capsule;
        $this->capsule->addConnection($config, $connection);
        $this->capsule->setEventDispatcher(new Dispatcher(new Container));
        $this->capsule->bootEloquent();
        $this->config->dbconfig = $config;
        // Capsule::setTablePrefix($prefix);
        // echo Capsule::getTablePrefix();
        // Capsule::setTablePrefix('sys_');
        // echo Capsule::getTablePrefix();
        // $this->server->setconnection() use in controller
    }

    /**
     * $config =  array of config
     * $connection  string of nameconnect ex  dba  dbb dbc
     */
    public function addConnection($config, $connection = 'default')
    {
        if ($this->capsule && $config) {
            if ($connection == 'default') {
                $this->capsule = new Capsule();
            }
            $this->capsule->addConnection($config, $connection);
            $this->capsule->setEventDispatcher(new Dispatcher(new Container));
            $this->capsule->bootEloquent();
            $this->config->{$connection} = $config;
        }
    }

    public function handle($request, $response)
    {
        $this->request = $request;
        $this->response = $response;
        $this->code = 200;
        $this->format = RestFormat::HTML;
        $this->result = '';
        $request_method = $request->server['request_method'];
        $request_uri = $request->server['request_uri'];
        $_GET = $request->get ?? [];
        $_COOKIE = $request->cookie ?? [];
        $_FILES = $request->files ?? [];
        $_SERVER['REQUEST_URI'] = $request->server['request_uri'];
        $_SERVER['REQUEST_METHOD'] = $request->server['request_method'];
        $_SERVER['REMOTE_ADDR'] = $request->server['remote_addr'];
        $_SERVER["PATH_INFO"] = $request->server["path_info"];
        $_SERVER["REQUEST_TIME"] = $request->server["request_time"];
        $_SERVER["REQUEST_TIME_FLOAT"] = $request->server["request_time_float"];
        $_SERVER["SERVER_PORT"] = $request->server["server_port"];
        $_SERVER["REMOTE_PORT"] = $request->server["remote_port"];
        $_SERVER["MASTER_TIME"] = $request->server["master_time"];
        $_SERVER["SERVER_PROTOCOL"] = $request->server["server_protocol"];
        $_SERVER["SERVER_SOFTWARE"] = $request->server["server_software"];
        $_HEADER = $request->header;

        $this->url = $this->getPath();
        $this->method = $this->getMethod();
        $this->format = $this->getFormat();
        if (($this->useCors) && ($this->method == 'OPTIONS')) {
            $this->corsHeaders();
            exit;
        }

        if ($this->method == 'PUT' || $this->method == 'POST' || $this->method == 'PATCH') {
            if ($request_method === 'POST' && $request->header['content-type'] === 'application/json') {
                $body = $request->rawContent();
                $this->foramt = RestFormat::JSON;
                $this->data = empty($body) ? [] : json_decode($body, true);
                $_POST = $this->data;
            } else {
                $this->data = $request->post ?? [];
                $_POST = $this->data;
            }
        }
        if ($this->method == 'OPTIONS' && getallheaders()->Access - Control - Request - Headers) {
            $this->sendData($this->options());
        }

        list($obj, $method, $params, $this->params, $noAuth) = $this->findUrl();

        if ($obj) {
            if (is_string($obj) && !($newObj = $this->instantiateClass($obj))) {
                throw new Exception("Class $obj does not exist");
            }

            $obj = $newObj;
            $obj->server = $this;
            $obj->request = $this->request;
            $obj->response = $this->response;

            try {
                $this->initClass($obj);

                if (!$noAuth && !$this->isAuthorized($obj)) {
                    $data = $this->unauthorized($obj);
                    $this->sendData($data);
                } else {
                    $result = call_user_func_array(array($obj, $method), $params);

                    if ($result !== null) {
                        $this->sendData($result);
                    }
                }
            } catch (RestException $e) {
                $this->handleError($e->getCode(), $e->getMessage());
            }
        } else {
            $this->handleError(404);
        }
    }

    private $codes = [
        '100' => 'Continue',
        '200' => 'OK',
        '201' => 'Created',
        '202' => 'Accepted',
        '203' => 'Non-Authoritative Information',
        '204' => 'No Content',
        '205' => 'Reset Content',
        '206' => 'Partial Content',
        '300' => 'Multiple Choices',
        '301' => 'Moved Permanently',
        '302' => 'Found',
        '303' => 'See Other',
        '304' => 'Not Modified',
        '305' => 'Use Proxy',
        '307' => 'Temporary Redirect',
        '400' => 'Bad Request',
        '401' => 'Unauthorized',
        '402' => 'Payment Required',
        '403' => 'Forbidden',
        '404' => 'Not Found',
        '405' => 'Method Not Allowed',
        '406' => 'Not Acceptable',
        '409' => 'Conflict',
        '410' => 'Gone',
        '411' => 'Length Required',
        '412' => 'Precondition Failed',
        '413' => 'Request Entity Too Large',
        '414' => 'Request-URI Too Long',
        '415' => 'Unsupported Media Type',
        '416' => 'Requested Range Not Satisfiable',
        '417' => 'Expectation Failed',
        '500' => 'Internal Server Error',
        '501' => 'Not Implemented',
        '503' => 'Service Unavailable',
    ];
}
