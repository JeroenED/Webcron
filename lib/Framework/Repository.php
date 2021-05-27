<?php


namespace JeroenED\Framework;


use Doctrine\DBAL\Connection;

class Repository
{
    protected Connection $dbcon;

    public function __construct(Connection $dbcon)
    {
        $this->dbcon = $dbcon;
    }
}