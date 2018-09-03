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
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET,PUT,POST,PATCH,DELETE");

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
            array_unshift(self::$URI, "");
        }

        //HELLO WORLD
        $this->any('/hello', function ($res) {
            $this->send("HELLO WORLD! <- " . self::$METHOD);
        });

        $main($this);

        //NOT FOUND
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
                return preg_match("/$u/", self::$URI[$k]);
            }
        }

        return count($pars) > 0 ? $pars : true;
    }
    private function __action($target, $bullet)
    {
        $P = $this->__splitter($target);

        if (!$target) {
            exit($bullet(null));
        }

        if (count($P) == count(self::$URI) && ($pars = $this->__parametize($P))) {
            exit($bullet($pars));
        }

    }

    public function send($data, $final = false)
    {
        echo json_encode($data);
        if ($final) {
            exit();
        }

    }

    public function any($target, $bullet)
    {
        $this->__action($target, $bullet);
    }
    public function post($target, $bullet)
    {
        if (self::$METHOD == 'POST') {
            $this->action($target, $bullet);
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
