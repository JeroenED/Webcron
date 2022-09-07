<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create initial database';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE job (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, `data` LONGTEXT NOT NULL, `interval` INT NOT NULL, nextrun INT NOT NULL, lastrun INT DEFAULT NULL, running INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE run (id INT AUTO_INCREMENT NOT NULL, job_id INT DEFAULT NULL, exitcode VARCHAR(15) NOT NULL, output LONGTEXT NOT NULL, runtime DOUBLE PRECISION NOT NULL, timestamp INT NOT NULL, flags VARCHAR(5) NOT NULL, INDEX IDX_5076A4C0BE04EA9 (job_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(100) NOT NULL, password VARCHAR(60) NOT NULL, sendmail TINYINT(1) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE run ADD CONSTRAINT FK_5076A4C0BE04EA9 FOREIGN KEY (job_id) REFERENCES job (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE run DROP FOREIGN KEY FK_5076A4C0BE04EA9');
        $this->addSql('DROP TABLE job');
        $this->addSql('DROP TABLE run');
        $this->addSql('DROP TABLE user');
    }
}
