<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231228164212 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add visitors to reservations';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE reservation_app_user (reservation_id INT NOT NULL, app_user_id INT NOT NULL, PRIMARY KEY(reservation_id, app_user_id))');
        $this->addSql('CREATE INDEX IDX_150811FDB83297E7 ON reservation_app_user (reservation_id)');
        $this->addSql('CREATE INDEX IDX_150811FD4A3353D8 ON reservation_app_user (app_user_id)');
        $this->addSql('ALTER TABLE reservation_app_user ADD CONSTRAINT FK_150811FDB83297E7 FOREIGN KEY (reservation_id) REFERENCES reservation (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE reservation_app_user ADD CONSTRAINT FK_150811FD4A3353D8 FOREIGN KEY (app_user_id) REFERENCES app_user (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE reservation_app_user DROP CONSTRAINT FK_150811FDB83297E7');
        $this->addSql('ALTER TABLE reservation_app_user DROP CONSTRAINT FK_150811FD4A3353D8');
        $this->addSql('DROP TABLE reservation_app_user');
    }
}
