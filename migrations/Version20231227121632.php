<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231227121632 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Alter reservation - app_user relation. Remove M:N relation (has access to) and created_by column. Add reserved_for column.';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE reservation_app_user DROP CONSTRAINT fk_150811fdb83297e7');
        $this->addSql('ALTER TABLE reservation_app_user DROP CONSTRAINT fk_150811fd4a3353d8');
        $this->addSql('DROP TABLE reservation_app_user');
        $this->addSql('ALTER TABLE app_user ALTER password DROP NOT NULL');
        $this->addSql('ALTER TABLE reservation DROP CONSTRAINT fk_42c84955b03a8386');
        $this->addSql('DROP INDEX idx_42c84955b03a8386');
        $this->addSql('ALTER TABLE reservation ADD reserved_for_id INT NOT NULL');
        $this->addSql('ALTER TABLE reservation DROP created_by_id');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT FK_42C849559190173B FOREIGN KEY (reserved_for_id) REFERENCES app_user (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_42C849559190173B ON reservation (reserved_for_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('CREATE TABLE reservation_app_user (reservation_id INT NOT NULL, app_user_id INT NOT NULL, PRIMARY KEY(reservation_id, app_user_id))');
        $this->addSql('CREATE INDEX idx_150811fd4a3353d8 ON reservation_app_user (app_user_id)');
        $this->addSql('CREATE INDEX idx_150811fdb83297e7 ON reservation_app_user (reservation_id)');
        $this->addSql('ALTER TABLE reservation_app_user ADD CONSTRAINT fk_150811fdb83297e7 FOREIGN KEY (reservation_id) REFERENCES reservation (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE reservation_app_user ADD CONSTRAINT fk_150811fd4a3353d8 FOREIGN KEY (app_user_id) REFERENCES app_user (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE app_user ALTER password SET NOT NULL');
        $this->addSql('ALTER TABLE reservation DROP CONSTRAINT FK_42C849559190173B');
        $this->addSql('DROP INDEX IDX_42C849559190173B');
        $this->addSql('ALTER TABLE reservation ADD created_by_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE reservation DROP reserved_for_id');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT fk_42c84955b03a8386 FOREIGN KEY (created_by_id) REFERENCES app_user (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_42c84955b03a8386 ON reservation (created_by_id)');
    }
}
