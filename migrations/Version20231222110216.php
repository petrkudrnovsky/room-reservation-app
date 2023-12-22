<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231222110216 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add table reservation and connect to other entities';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE reservation_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE reservation (id INT NOT NULL, room_id INT DEFAULT NULL, created_by_id INT DEFAULT NULL, approved_by_id INT DEFAULT NULL, title VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, start_datetime TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, end_datetime TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, status VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_42C8495554177093 ON reservation (room_id)');
        $this->addSql('CREATE INDEX IDX_42C84955B03A8386 ON reservation (created_by_id)');
        $this->addSql('CREATE INDEX IDX_42C849552D234F6A ON reservation (approved_by_id)');
        $this->addSql('CREATE TABLE reservation_app_user (reservation_id INT NOT NULL, app_user_id INT NOT NULL, PRIMARY KEY(reservation_id, app_user_id))');
        $this->addSql('CREATE INDEX IDX_150811FDB83297E7 ON reservation_app_user (reservation_id)');
        $this->addSql('CREATE INDEX IDX_150811FD4A3353D8 ON reservation_app_user (app_user_id)');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT FK_42C8495554177093 FOREIGN KEY (room_id) REFERENCES room (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT FK_42C84955B03A8386 FOREIGN KEY (created_by_id) REFERENCES app_user (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT FK_42C849552D234F6A FOREIGN KEY (approved_by_id) REFERENCES app_user (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE reservation_app_user ADD CONSTRAINT FK_150811FDB83297E7 FOREIGN KEY (reservation_id) REFERENCES reservation (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE reservation_app_user ADD CONSTRAINT FK_150811FD4A3353D8 FOREIGN KEY (app_user_id) REFERENCES app_user (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE reservation_id_seq CASCADE');
        $this->addSql('ALTER TABLE reservation DROP CONSTRAINT FK_42C8495554177093');
        $this->addSql('ALTER TABLE reservation DROP CONSTRAINT FK_42C84955B03A8386');
        $this->addSql('ALTER TABLE reservation DROP CONSTRAINT FK_42C849552D234F6A');
        $this->addSql('ALTER TABLE reservation_app_user DROP CONSTRAINT FK_150811FDB83297E7');
        $this->addSql('ALTER TABLE reservation_app_user DROP CONSTRAINT FK_150811FD4A3353D8');
        $this->addSql('DROP TABLE reservation');
        $this->addSql('DROP TABLE reservation_app_user');
    }
}
