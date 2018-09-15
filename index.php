<?php include 'Revolver.php';
$host = 'locahost';
$db   = 'my_phptestareas';
$user = 'phptestareas';
$pass = '';

$jwt = new JWT([
    "secret" => "HSfDQXr2qnn2mnVBtRdjejJMu9UDKgU4ajtaM9ZexSBZUj6ryRdvtVHSfCrpZSFWQ4CKryyKCTzH73RqVAfsmX48WmSwTdaSWLmdWSHVnurJftn7C9mA83suXEPM7gBBmvuREybZz2L7hbfqpdJ2UdsjgMmktBM2wsJvKb25G7sfb4Y6PUrnMfRAYdArKNAqhn9RcrthckQfvrKV32gKEBJJxdHAVS9vT2yLps2PaV7S7YbCWPSXNAjbQNsVuGwu",
]);
$pdo = new PDO("mysql:host=$host; dbname=$db", "$user", "$pass");

Revolver::load(function ($R) use ($jwt, $pdo) {
    $token = $jwt->check($_SERVER[Authorization]);

    $R->post('/register', function ($res) use ($R, $jwt, $pdo) {
        $res = $_POST;
        $R->send($_SERVER[body]);

        // if (isset($res[name], $res[password], $res[email])) {
        //     $t = $pdo->prepare("INSERT INTO `Users` (`name`, `password`, `email`, `role`) VALUES (?, ?, ?, -1)");

        //     $R->send("prova");

        //     // $t->execute([$res[name], password_hash($res[password]), $res[email]]);

        //     // $result = $t->fetch();
        //     // $R->send($result);
        // }
    });

    $R->post('/login', function ($res) use ($R, $jwt, $pdo) {
        $res = $_POST;
        if (isset($res[name], $res[password])) {
            $t = $pdo->prepare("SELECT * FROM Users WHERE name=? LIMIT 1");

            $t->execute([$res[name]]);

            $user = $t->fetch();
            if (password_verify($res[password], $user[password])) {
                $R->send(
                    $jwt->header()
                        ->payload([
                            "role" => $user[role],
                            "exp"  => $jwt->years(1),
                        ])
                        ->cook(),
                    true
                );
            }
        }
        http_response_code(401);
    });

    //ADMIN ZONE
    if ($token[payload][role] == 1) {
        $R->get('/query', function ($res) use ($R, $pdo) {
            $t = $pdo->query($_SERVER["QueryText"]);

            $R->send($t->fetchAll());
        });
    }
});

/*
$R->get('/register/:name/:password', function ($res) use ($R, $pdo) {
$t = $pdo->prepare("INSERT INTO utenti (name, password) VALUES (:name, :password)");
$t->execute([
':name'     => $res[name],
':password' => password_hash($res[password], PASSWORD_BCRYPT),
]);
});
 */
