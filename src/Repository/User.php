<?php


namespace JeroenED\Webcron\Repository;


use Doctrine\DBAL\Connection;

class User
{
    private Connection $dbcon;

    public function __construct(Connection $dbcon)
    {
        $this->dbcon = $dbcon;
    }

    /**
     * @param string $user
     * @param string $password
     * @param bool $autologin
     * @return int|bool
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws \Doctrine\DBAL\Exception
     */
    public function checkAuthentication(string $user, string $password, bool $autologin = false): int|bool
    {
        $userSql = "SELECT * from user WHERE email = :user";
        $userStmt = $this->dbcon->prepare($userSql);
        $userRslt = $userStmt->execute([':user' => $user]);
        if($user = $userRslt->fetchAssociative()) {
            if($autologin) $password = $this->getPassFromAutologinToken($password);

            $password = hash($_ENV['HASHING_METHOD'], $password);

            if(password_verify($password, $user['password'])) {
                return $user['id'];
            }
        }
        return false;
    }

    public function createAutologinToken($password): string {
        $method = $_ENV['ENCRYPTION_METHOD'];
        $key = hash($_ENV['HASHING_METHOD'], $_ENV['SECRET'], true);
        $iv = openssl_random_pseudo_bytes(16);
        $time = time();

        $ciphertext = openssl_encrypt($password . substr($time, -7), $method, $key, OPENSSL_RAW_DATA, $iv);
        $hash = hash_hmac($_ENV['HASHING_METHOD'], $ciphertext . $iv, $key, true);
        return base64_encode(json_encode(['time' => $time, 'password' => base64_encode($iv . $hash . $ciphertext)]));
    }

    public function getPassFromAutologinToken($token) {
        $extracted = json_decode(base64_decode($token), true);
        $method = $_ENV['ENCRYPTION_METHOD'];
        $encrypted = base64_decode($extracted['password']);
        $iv = substr($encrypted, 0, 16);
        $hash = substr($encrypted, 16, 32);
        $ciphertext = substr($encrypted, 48);
        $key = hash($_ENV['HASHING_METHOD'], $_ENV['SECRET'], true);

        if (!hash_equals(hash_hmac($_ENV['HASHING_METHOD'], $ciphertext . $iv, $key, true), $hash)) return null;

        $decryption = openssl_decrypt($ciphertext, $method, $key, OPENSSL_RAW_DATA, $iv);

        return (
            (($extracted['time'] + $_ENV['COOKIE_LIFETIME']) > time()) &&
            substr($extracted['time'], -7) == substr($decryption, -7)
        )
            ? substr($decryption, 0, -7) : null;
    }
}