<?php


namespace App\Repository;


use App\Service\Secret;
use Doctrine\ORM\EntityRepository;

class UserRepository extends EntityRepository
{
    public function getMailAddresses() {
        $users = $this->findBy(['sendmail' => 1]);

        $return = [];
        foreach($users as $user) {
            $return[] = $user->getEmail();
        }
        return $return;
    }
}