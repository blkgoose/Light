<?php include 'JWT.php';
class Revolver
{
    static $URI = [];
    static $METHOD;

    public static function load($main)
    {
        static $inst = null;
        if ($inst === null) {
            $inst = new Revolver($main);
        }
        return $inst;
    }
    private function __construct($main)
    {
        header('Content-Type: application/json');
        header('Accept: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: *');
        header('Access-Control-Allow-Headers: *');
        header('Access-Control-Allow-Credentials: true');

        self::$METHOD = $_SERVER[REQUEST_METHOD];

        self::$URI = $this->__splitter(parse_url(urldecode($_SERVER[REQUEST_URI]), PHP_URL_PATH));

        $path = $this->__splitter($_SERVER[ORIG_PATH_INFO]);

        foreach ($path as $u) {
            if ($u == self::$URI[0]) {
                array_shift(self::$URI);
                array_shift($path);
            }
        }

        if (self::$URI[0]) {
            array_unshift(self::$URI, '');
        }

        //HELLO WORLD
        $this->any('/hello', function ($res) {
            $this->send('HELLO WORLD! <- ' . self::$METHOD);
        });

        $main($this);

        //NOT FOUND
        $this->options('', function () {
            http_response_code(200);
        });
        $this->any('', function () {
            http_response_code(404);
        });
    }
    private function __splitter($string)
    {
        return explode('/', $string);
    }
    private function __parametize($uri)
    {
        $pars = [];

        foreach ($uri as $k => $u) {
            if (preg_match('/^\:/', $u)) {
                $pars[substr($u, 1)] = self::$URI[$k] == '' ? null : self::$URI[$k];
            } elseif ($u != self::$URI[$k]) {
                return preg_match('/$u/', self::$URI[$k]);
            }
        }

        return count($pars) > 0 ? $pars : true;
    }
    private function __action($target, $bullet)
    {
        $P = $this->__splitter($target);

        $raw_post = file_get_contents("php://input");

        $json_body = json_decode($raw_post, true);

        if (!$target) {
            exit($bullet(null, $json_body));
        }

        if (count($P) == count(self::$URI) && ($pars = $this->__parametize($P))) {
            exit($bullet($pars, $json_body, $raw_post));
        }

    }

    public function send($data, $final = false)
    {
        echo json_encode($data, true);
        if (json_last_error() != 0) {
            http_response_code(500);
            throw new Exception(json_last_error_msg());
            exit();
        }
        if ($final) {
            exit();
        }

    }

    public function any($target, $bullet)
    {
        $this->__action($target, $bullet);
    }
    public function options($target, $bullet)
    {
        if (self::$METHOD == 'OPTIONS') {
            $this->__action($target, $bullet);
        }
    }
    public function post($target, $bullet)
    {
        if (self::$METHOD == 'POST') {
            $this->__action($target, $bullet);
        }
    }
    public function get($target, $bullet)
    {
        if (self::$METHOD == 'GET') {
            $this->__action($target, $bullet);
        }
    }
    public function put($target, $bullet)
    {
        if (self::$METHOD == 'PUT') {
            $this->action($target, $bullet);
        }
    }
    public function patch($target, $bullet)
    {
        if (self::$METHOD == 'PATCH') {
            $this->action($target, $bullet);
        }
    }
    public function delete($target, $bullet)
    {
        if (self::$METHOD == 'DELETE') {
            $this->action($target, $bullet);
        }
    }
}
