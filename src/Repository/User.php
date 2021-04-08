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

    public function checkAuthentication(string $user, string $password): bool
    {
        $userSql = "SELECT * from user WHERE email = :user";
        $userStmt = $this->dbcon->prepare($userSql);
        $userRslt = $userStmt->execute([':user' => $user]);
        if($user = $userRslt->fetchAssociative()) {
            $shaPass = hash('sha256', $password);
            if(password_verify($shaPass, $user['password'])) {
                return true;
            }
        }
        return false;
    }
}