<?php
class JWT
{
    static $SECRET = "";
    static $RSAKEY = "";

    static $HEADER  = [];
    static $PAYLOAD = [];

    static $algoritms = [
        "HS128" => "sha128",
        "HS256" => "sha256",
        "HS512" => "sha512",
    ];

    public function constructor($data = [])
    {
        self::$SECRET = $data[secret];
        self::$RSAKEY = $data[rsakey];

        if (!self::$SECRET) {
            throw new Exception("secret is needed");
        }
    }

    public function seconds($offset = 0)
    {
        return intval(microtime(true)) + $offset;
    }
    public function minutes($offset = 0)
    {
        return $this->seconds($offset * 60);
    }
    public function hours($offset = 0)
    {
        return $this->minutes($offset * 60);
    }
    public function days($offset = 0)
    {
        return $this->hours($offset * 24);
    }
    public function years($offset = 0)
    {
        return $this->days($offset * 365.4);
    }

    private function __base64urlEncode($s)
    {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($s));
    }
    private function __base64urlDecode($s)
    {
        return str_replace(['-', '_'], ['+', '/'], base64_decode($s));
    }
    private function __dataIsGood($data)
    {
        if (!is_array($data) && $data) {
            throw new Exception("Data must be array");
        }
    }

    public function check($token, $badTokenCallback)
    {
        try {
            $token     = $this->__decode($token);
            $testToken = $this->__decode($this
                    ->header($token[header])
                    ->payload($token[payload])
                    ->cook()
            );

            if (
                $testToken[signature] == $token[signature] &&
                (
                    $token[payload][exp] && $this->seconds < $token[payload][exp]
                )
            ) {
                return $token;
            }
        } catch (Exception $_) {}
        if ($token) {
            $badTokenCallback($token);
        }
        return null;
    }
    private function __sslDecrypt($data, $algo = "AES256")
    {
        return self::$RSAKEY ? openssl_decrypt($data, $algo, $self::$RSAKEY) : $data;
    }
    private function __sslEncrypt($data, $algo = "AES256")
    {
        return self::$RSAKEY ? openssl_encrypt($data, $algo, $self::$RSAKEY) : $data;
    }

    private function __decode($token)
    {
        $token = explode(".", $token);

        return [
            "header"    => json_decode($this->__base64urlDecode($token[0]), true),
            "payload"   => json_decode($this->__sslDecrypt($this->__base64urlDecode($token[1])), true),
            "signature" => $token[2],
        ];
    }

    public function header($data = [])
    {
        $this->__dataIsGood($data);
        $defaults = [
            "alg" => "HS256",
            "typ" => "JWT",
        ];

        self::$HEADER = array_merge($defaults, $data);
        return $this;
    }
    public function payload($data = [])
    {
        $this->__dataIsGood($data);
        $defaults = [
            "exp" => $this->years(1),
        ];

        self::$PAYLOAD = array_merge($defaults, $data);
        return $this;
    }
    public function cook()
    {
        $alg = self::$algoritms[self::$HEADER[alg]];

        if (!$alg) {
            return null;
        }

        $header    = $this->__base64urlEncode(json_encode(self::$HEADER));
        $payload   = $this->__base64urlEncode($this->__sslEncrypt(json_encode(self::$PAYLOAD)));
        $signature = $this->__base64urlEncode(hash_hmac($alg, "$header.$payload", self::$SECRET, true));

        return "$header.$payload.$signature";
    }
}
