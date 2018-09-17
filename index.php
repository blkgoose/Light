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
        $res = json_decode($_POST[data], true);

        if (isset($res[name], $res[password], $res[email])) {
            $t = $pdo->prepare("INSERT INTO `Users` (`name`, `password`, `email`, `role`) VALUES (:name, :password, :email, -1)");

            $result = $t->execute([
                ':name'     => $res[name],
                ':password' => password_hash($res[password], 1),
                ':email'    => $res[email],
            ]);

            $R->send($result);
        }
    });

    $R->post('/login', function ($res) use ($R, $jwt, $pdo) {
        $res = json_decode($_POST[data], true);

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

    /* JEEPH TEST */

    $R->post('/gif/getChat', function ($res) use ($R, $jwt, $pdo) {
        $token = $jwt->check($_POST[token]);
        $other = $_POST[talkingTo];

        $t = $pdo->prepare("SELECT messages as message,
                            CASE WHEN from_user = :to THEN 'received' ELSE 'sent' END as type
                            FROM (SELECT * FROM gif_chat ORDER BY timestamp) as ordered
                            WHERE from_user = :from AND to_user = :to
                            OR to_user = :from AND from_user = :to");

        $result = $t->execute([
            ':from' => $token[payload][name],
            ':to'   => $other,
        ]);

        $R->send($t->fetchAll());
    });

    $R->post('/gif/send', function ($res) use ($R, $jwt, $pdo) {
        $token   = $jwt->check($_POST[token]);
        $other   = $_POST[talkingTo];
        $message = $_POST[message];

        $t = $pdo->prepare("INSERT INTO gif_chat (`from_user`, `to_user`, `messages`) VALUES (:from, :to, :message)");

        $R->send(
            $t->execute([
                ':from'    => $token[payload][name],
                ':to'      => $other,
                ':message' => $message,
            ]
            )
        );

    });

    $R->post('/gif/login', function ($res) use ($R, $jwt, $pdo) {
        $res = json_decode($_POST[data], true);

        if (isset($res[name], $res[password])) {
            $t = $pdo->prepare("SELECT * FROM gif_users WHERE username=? LIMIT 1");

            $t->execute([$res[name]]);

            $user = $t->fetch();
            if (password_verify($res[password], $user[password])) {
                $R->send(
                    $jwt->header()
                        ->payload([
                            "name" => $res[name],
                            "exp"  => $jwt->years(1),
                        ])
                        ->cook(),
                    true
                );
            }
        }
        http_response_code(401);
    });

});
