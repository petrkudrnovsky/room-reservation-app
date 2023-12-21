<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231219125228 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add Room table and it\'s relation to other entities';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE room_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE room (id INT NOT NULL, name VARCHAR(255) NOT NULL, code VARCHAR(255) DEFAULT NULL, is_private BOOLEAN NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE room_group (room_id INT NOT NULL, group_id INT NOT NULL, PRIMARY KEY(room_id, group_id))');
        $this->addSql('CREATE INDEX IDX_277CE56E54177093 ON room_group (room_id)');
        $this->addSql('CREATE INDEX IDX_277CE56EFE54D947 ON room_group (group_id)');
        $this->addSql('CREATE TABLE appRoom_member_appUser (room_id INT NOT NULL, app_user_id INT NOT NULL, PRIMARY KEY(room_id, app_user_id))');
        $this->addSql('CREATE INDEX IDX_5E1F698154177093 ON appRoom_member_appUser (room_id)');
        $this->addSql('CREATE INDEX IDX_5E1F69814A3353D8 ON appRoom_member_appUser (app_user_id)');
        $this->addSql('CREATE TABLE appRoom_admin_appUser (room_id INT NOT NULL, app_user_id INT NOT NULL, PRIMARY KEY(room_id, app_user_id))');
        $this->addSql('CREATE INDEX IDX_D392426A54177093 ON appRoom_admin_appUser (room_id)');
        $this->addSql('CREATE INDEX IDX_D392426A4A3353D8 ON appRoom_admin_appUser (app_user_id)');
        $this->addSql('ALTER TABLE room_group ADD CONSTRAINT FK_277CE56E54177093 FOREIGN KEY (room_id) REFERENCES room (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE room_group ADD CONSTRAINT FK_277CE56EFE54D947 FOREIGN KEY (group_id) REFERENCES appGroup (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE appRoom_member_appUser ADD CONSTRAINT FK_5E1F698154177093 FOREIGN KEY (room_id) REFERENCES room (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE appRoom_member_appUser ADD CONSTRAINT FK_5E1F69814A3353D8 FOREIGN KEY (app_user_id) REFERENCES app_user (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE appRoom_admin_appUser ADD CONSTRAINT FK_D392426A54177093 FOREIGN KEY (room_id) REFERENCES room (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE appRoom_admin_appUser ADD CONSTRAINT FK_D392426A4A3353D8 FOREIGN KEY (app_user_id) REFERENCES app_user (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE room_id_seq CASCADE');
        $this->addSql('ALTER TABLE room_group DROP CONSTRAINT FK_277CE56E54177093');
        $this->addSql('ALTER TABLE room_group DROP CONSTRAINT FK_277CE56EFE54D947');
        $this->addSql('ALTER TABLE appRoom_member_appUser DROP CONSTRAINT FK_5E1F698154177093');
        $this->addSql('ALTER TABLE appRoom_member_appUser DROP CONSTRAINT FK_5E1F69814A3353D8');
        $this->addSql('ALTER TABLE appRoom_admin_appUser DROP CONSTRAINT FK_D392426A54177093');
        $this->addSql('ALTER TABLE appRoom_admin_appUser DROP CONSTRAINT FK_D392426A4A3353D8');
        $this->addSql('DROP TABLE room');
        $this->addSql('DROP TABLE room_group');
        $this->addSql('DROP TABLE appRoom_member_appUser');
        $this->addSql('DROP TABLE appRoom_admin_appUser');
    }
}
