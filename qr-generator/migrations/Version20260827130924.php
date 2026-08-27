<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

/**
 * Creates the table backing the public gallery of created links.
 *
 * Written against the Schema API so the same migration runs on SQLite (the
 * default) and on PostgreSQL.
 */
final class Version20260827130924 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create the public_links table (gallery of links turned into QR codes).';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->createTable('public_links');
        $table->addColumn('id', Types::INTEGER, ['autoincrement' => true, 'notnull' => true]);
        $table->addColumn('url', Types::STRING, ['length' => 1024, 'notnull' => true]);
        $table->addColumn('url_hash', Types::STRING, ['length' => 64, 'notnull' => true]);
        $table->addColumn('host', Types::STRING, ['length' => 255, 'notnull' => true]);
        $table->addColumn('locale', Types::STRING, ['length' => 5, 'notnull' => true]);
        $table->addColumn('hits', Types::INTEGER, ['notnull' => true]);
        $table->addColumn('blocked', Types::BOOLEAN, ['notnull' => true]);
        $table->addColumn('created_at', Types::DATETIME_IMMUTABLE, ['notnull' => true]);
        $table->addColumn('last_seen_at', Types::DATETIME_IMMUTABLE, ['notnull' => true]);

        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['url_hash'], 'uniq_public_links_hash');
        $table->addIndex(['created_at'], 'idx_public_links_created_at');
        $table->addIndex(['host'], 'idx_public_links_host');
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('public_links');
    }
}
