<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20231206CreateCurrencyRatesTable extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $table = $schema->createTable('currency_rates');

        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('time', 'integer');
        $table->addColumn('high', 'decimal', ['precision' => 16, 'scale' => 8]);
        $table->addColumn('low', 'decimal', ['precision' => 16, 'scale' => 8]);
        $table->addColumn('open', 'decimal', ['precision' => 16, 'scale' => 8]);
        $table->addColumn('close', 'decimal', ['precision' => 16, 'scale' => 8]);
        $table->addColumn('volume_to', 'decimal', ['precision' => 16, 'scale' => 8]);

        $table->setPrimaryKey(['id']);

        $table->addIndex(['time'], 'time_idx');
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('currency_rates');
    }
}
