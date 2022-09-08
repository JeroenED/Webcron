<?php


namespace App\Repository;


use App\Entity\User;
use App\Service\Secret;
use Doctrine\ORM\EntityRepository;

class UserRepository extends EntityRepository
{
    public function setLocale(User $user, $locale)
    {
        $em = $this->getEntityManager();
        $user->setLocale($locale);
        $em->persist($user);
        $em->flush();
    }

    public function setPassword(User $user, $hashedPassword)
    {
        $em = $this->getEntityManager();
        $user->setPassword($hashedPassword);
        $em->persist($user);
        $em->flush();
    }
}