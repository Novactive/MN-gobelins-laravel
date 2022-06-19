<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220619141951 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `order` ADD reference CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', ADD amount INT NOT NULL, ADD net_to_pay INT NOT NULL, ADD pay INT NOT NULL, ADD remainder_to_pay INT NOT NULL, CHANGE discount discount INT NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_F5299398AEA34913 ON `order` (reference)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_F5299398AEA34913 ON `order`');
        $this->addSql('ALTER TABLE `order` DROP reference, DROP amount, DROP net_to_pay, DROP pay, DROP remainder_to_pay, CHANGE discount discount DOUBLE PRECISION NOT NULL');
    }
}
