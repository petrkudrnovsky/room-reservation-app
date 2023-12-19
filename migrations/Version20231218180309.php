<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231218180309 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add group table, connect with appUser with two join tables';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE appGroup_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE appGroup (id INT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE appGroup_member_appUser (group_id INT NOT NULL, app_user_id INT NOT NULL, PRIMARY KEY(group_id, app_user_id))');
        $this->addSql('CREATE INDEX IDX_51FF3C92FE54D947 ON appGroup_member_appUser (group_id)');
        $this->addSql('CREATE INDEX IDX_51FF3C924A3353D8 ON appGroup_member_appUser (app_user_id)');
        $this->addSql('CREATE TABLE appGroup_admin_appUser (group_id INT NOT NULL, app_user_id INT NOT NULL, PRIMARY KEY(group_id, app_user_id))');
        $this->addSql('CREATE INDEX IDX_333E6523FE54D947 ON appGroup_admin_appUser (group_id)');
        $this->addSql('CREATE INDEX IDX_333E65234A3353D8 ON appGroup_admin_appUser (app_user_id)');
        $this->addSql('ALTER TABLE appGroup_member_appUser ADD CONSTRAINT FK_51FF3C92FE54D947 FOREIGN KEY (group_id) REFERENCES appGroup (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE appGroup_member_appUser ADD CONSTRAINT FK_51FF3C924A3353D8 FOREIGN KEY (app_user_id) REFERENCES app_user (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE appGroup_admin_appUser ADD CONSTRAINT FK_333E6523FE54D947 FOREIGN KEY (group_id) REFERENCES appGroup (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE appGroup_admin_appUser ADD CONSTRAINT FK_333E65234A3353D8 FOREIGN KEY (app_user_id) REFERENCES app_user (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE appGroup_id_seq CASCADE');
        $this->addSql('ALTER TABLE appGroup_member_appUser DROP CONSTRAINT FK_51FF3C92FE54D947');
        $this->addSql('ALTER TABLE appGroup_member_appUser DROP CONSTRAINT FK_51FF3C924A3353D8');
        $this->addSql('ALTER TABLE appGroup_admin_appUser DROP CONSTRAINT FK_333E6523FE54D947');
        $this->addSql('ALTER TABLE appGroup_admin_appUser DROP CONSTRAINT FK_333E65234A3353D8');
        $this->addSql('DROP TABLE appGroup');
        $this->addSql('DROP TABLE appGroup_member_appUser');
        $this->addSql('DROP TABLE appGroup_admin_appUser');
    }
}
