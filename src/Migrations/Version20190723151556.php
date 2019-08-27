<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190723151556 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP INDEX uniq_c13b109498c4c3d8');
        $this->addSql('CREATE INDEX IDX_C13B109498C4C3D8 ON report_position_value (parent_report_position_value_id)');
        $this->addSql('DROP INDEX uniq_464c7d8826fc44f2');
        $this->addSql('CREATE INDEX IDX_464C7D8826FC44F2 ON report_position (parent_report_position_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP INDEX IDX_464C7D8826FC44F2');
        $this->addSql('CREATE UNIQUE INDEX uniq_464c7d8826fc44f2 ON report_position (parent_report_position_id)');
        $this->addSql('DROP INDEX IDX_C13B109498C4C3D8');
        $this->addSql('CREATE UNIQUE INDEX uniq_c13b109498c4c3d8 ON report_position_value (parent_report_position_value_id)');
    }
}
