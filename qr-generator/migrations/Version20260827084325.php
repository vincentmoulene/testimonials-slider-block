<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

/**
 * Creates the table holding the email addresses collected on download.
 *
 * Written against the Schema API rather than raw SQL so the same migration runs
 * on SQLite (the default) and on PostgreSQL (the recommended production setup).
 */
final class Version20260827084325 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create the leads table (email capture on code generation).';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->createTable('leads');
        $table->addColumn('id', Types::INTEGER, ['autoincrement' => true, 'notnull' => true]);
        $table->addColumn('email', Types::STRING, ['length' => 180, 'notnull' => true]);
        $table->addColumn('locale', Types::STRING, ['length' => 5, 'notnull' => true]);
        $table->addColumn('tool', Types::STRING, ['length' => 32, 'notnull' => true]);
        $table->addColumn('source', Types::STRING, ['length' => 512, 'notnull' => false]);
        $table->addColumn('ip_hash', Types::STRING, ['length' => 64, 'notnull' => false]);
        $table->addColumn('consent_marketing', Types::BOOLEAN, ['notnull' => true]);
        $table->addColumn('created_at', Types::DATETIME_IMMUTABLE, ['notnull' => true]);
        $table->addColumn('last_seen_at', Types::DATETIME_IMMUTABLE, ['notnull' => true]);
        $table->addColumn('generation_count', Types::INTEGER, ['notnull' => true]);

        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['email'], 'uniq_leads_email');
        $table->addIndex(['created_at'], 'idx_leads_created_at');
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('leads');
    }
}
