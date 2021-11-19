<?php


namespace JeroenED\Webcron\Repository;


use Doctrine\DBAL\Connection;
use JeroenED\Framework\Repository;

class User extends Repository
{

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
        $userRslt = $userStmt->executeQuery([':user' => $user]);
        if($user = $userRslt->fetchAssociative()) {
            if($autologin) $password = $this->getPassFromAutologinToken($password);

            $password = hash($_ENV['HASHING_METHOD'], $password);

            if(password_verify($password, $user['password'])) {
                return $user['id'];
            }
        }
        return false;
    }

    public function createAutologinToken($password): string
    {
        $time = time();
        $password = $password . substr($time, -7) ;
        $encrypted = Secret::encrypt($password);
        return base64_encode(json_encode(['time' => $time, 'password' => base64_encode($encrypted)]));
    }

    public function getPassFromAutologinToken($token) {
        $extracted = json_decode(base64_decode($token), true);
        $encrypted = base64_decode($extracted['password']);

        $decrypted = Secret::decrypt($encrypted);

        return (
            (($extracted['time'] + $_ENV['COOKIE_LIFETIME']) > time()) &&
            substr($extracted['time'], -7) == substr($decrypted, -7)
        )
            ? substr($decrypted, 0, -7) : null;
    }

    public function getMailAddresses() {
        $emailSql = "SELECT email FROM user WHERE sendmail = 1";
        $emailStmt = $this->dbcon->prepare($emailSql);
        $emailRslt = $emailStmt->executeQuery();

        $return = [];
        foreach($emailRslt->fetchAllAssociative() as $email) {
            $return[] = $email['email'];
        }
        return $return;
    }
}