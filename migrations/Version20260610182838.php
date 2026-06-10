<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260610182838 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add approved_at to reservation; replace Room.isLocked boolean with lockState string; create access_log table';
    }

    public function up(Schema $schema): void
    {
        // reservation: add approvedAt timestamp
        $this->addSql('ALTER TABLE reservation ADD approved_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');

        // room: replace is_locked boolean with lock_state string, preserving existing values
        $this->addSql('ALTER TABLE room ADD lock_state VARCHAR(20) DEFAULT \'locked\' NOT NULL');
        $this->addSql('UPDATE room SET lock_state = CASE WHEN is_locked = true THEN \'locked\' ELSE \'unlocked\' END');
        $this->addSql('ALTER TABLE room DROP is_locked');

        // access_log: persistent record of each door-access decision
        $this->addSql('CREATE SEQUENCE access_log_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE access_log (id INT NOT NULL, room_id INT NOT NULL, user_id INT DEFAULT NULL, decision VARCHAR(10) NOT NULL, accessed_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_EF7F351054177093 ON access_log (room_id)');
        $this->addSql('CREATE INDEX IDX_EF7F3510A76ED395 ON access_log (user_id)');
        $this->addSql('ALTER TABLE access_log ADD CONSTRAINT FK_EF7F351054177093 FOREIGN KEY (room_id) REFERENCES room (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE access_log ADD CONSTRAINT FK_EF7F3510A76ED395 FOREIGN KEY (user_id) REFERENCES app_user (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE reservation DROP approved_at');
        $this->addSql('ALTER TABLE room ADD is_locked BOOLEAN DEFAULT TRUE NOT NULL');
        $this->addSql('UPDATE room SET is_locked = (lock_state = \'locked\')');
        $this->addSql('ALTER TABLE room DROP lock_state');
        $this->addSql('DROP SEQUENCE access_log_id_seq CASCADE');
        $this->addSql('ALTER TABLE access_log DROP CONSTRAINT FK_EF7F351054177093');
        $this->addSql('ALTER TABLE access_log DROP CONSTRAINT FK_EF7F3510A76ED395');
        $this->addSql('DROP TABLE access_log');
    }
}
