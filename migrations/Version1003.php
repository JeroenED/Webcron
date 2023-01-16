<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Entity\Job;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version1003 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $allJobs = $this->connection->executeQuery('SELECT * FROM job')->fetchAllAssociative();
        foreach($allJobs as $job) {
            $data = json_decode($job['data'], true);
            $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $randomString = '';
            $length = 32;

            for ($i = 0; $i < $length; $i++) {
                $index = rand(0, strlen($characters) - 1);
                $randomString .= $characters[$index];
            }

            $data['hooktoken'] =  $randomString;
            $this->addSql('UPDATE job SET data = "' . addSlashes(json_encode($data)) . '" WHERE id = ' . $job['id']);
        }
    }

    public function down(Schema $schema): void
    {
        $allJobs = $this->connection->executeQuery('SELECT * FROM job')->fetchAllAssociative();
        foreach($allJobs as $job) {
            $data = json_decode($job['data'], true);
            unset($data['hooktoken']);
            $this->addSql('UPDATE job SET data = "' . addSlashes(json_encode($data)) . '" WHERE id = ' . $job['id']);
        }
    }
}
