<?php

namespace Doctrine\Tests\DBAL\Functional;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use Doctrine\Tests\DbalFunctionalTestCase;
use Throwable;

class TemporaryTableTest extends DbalFunctionalTestCase
{
    public function testDropTemporaryTableNotAutoCommitTransaction(): void
    {
        if (
            $this->connection->getDatabasePlatform()->getName() === 'sqlanywhere' ||
            $this->connection->getDatabasePlatform()->getName() === 'oracle'
        ) {
            $this->markTestSkipped('Test does not work on Oracle and SQL Anywhere.');
        }

        $platform          = $this->connection->getDatabasePlatform();
        $columnDefinitions = ['id' => ['type' => Type::getType('integer'), 'notnull' => true]];
        $tempTable         = $platform->getTemporaryTableName('my_temporary');

        $createTempTableSQL = $platform->getCreateTemporaryTableSnippetSQL() . ' ' . $tempTable . ' ('
                . $platform->getColumnDeclarationListSQL($columnDefinitions) . ')';
        $this->connection->executeStatement($createTempTableSQL);

        $table = new Table('nontemporary');
        $table->addColumn('id', 'integer');
        $table->setPrimaryKey(['id']);

        $this->connection->getSchemaManager()->dropAndCreateTable($table);

        $this->connection->beginTransaction();
        $this->connection->insert('nontemporary', ['id' => 1]);
        $this->dropTemporaryTable('my_temporary');
        $this->connection->insert('nontemporary', ['id' => 2]);

        $this->connection->rollBack();

        // In an event of an error this result has one row, because of an implicit commit
        self::assertEquals([], $this->connection->fetchAll('SELECT * FROM nontemporary'));
    }

    public function testCreateTemporaryTableNotAutoCommitTransaction(): void
    {
        if (
            $this->connection->getDatabasePlatform()->getName() === 'sqlanywhere' ||
            $this->connection->getDatabasePlatform()->getName() === 'oracle'
        ) {
            $this->markTestSkipped('Test does not work on Oracle and SQL Anywhere.');
        }

        $platform          = $this->connection->getDatabasePlatform();
        $columnDefinitions = ['id' => ['type' => Type::getType('integer'), 'notnull' => true]];
        $tempTable         = $platform->getTemporaryTableName('my_temporary');

        $createTempTableSQL = $platform->getCreateTemporaryTableSnippetSQL() . ' ' . $tempTable . ' ('
                . $platform->getColumnDeclarationListSQL($columnDefinitions) . ')';

        $table = new Table('nontemporary');
        $table->addColumn('id', 'integer');
        $table->setPrimaryKey(['id']);

        $this->connection->getSchemaManager()->dropAndCreateTable($table);

        $this->connection->beginTransaction();
        $this->connection->insert('nontemporary', ['id' => 1]);

        $this->dropTemporaryTable('my_temporary');
        $this->connection->exec($createTempTableSQL);
        $this->connection->insert('nontemporary', ['id' => 2]);

        $this->connection->rollBack();

        try {
            $this->connection->exec($platform->getDropTemporaryTableSQL($tempTable));
        } catch (Throwable $e) {
        }

        // In an event of an error this result has one row, because of an implicit commit
        self::assertEquals([], $this->connection->fetchAll('SELECT * FROM nontemporary'));
    }

    private function dropTemporaryTable(string $name): void
    {
        $platform = $this->connection->getDatabasePlatform();
        $sql      = $platform->getDropTemporaryTableSQL(
            $platform->getTemporaryTableName($name)
        );

        try {
            $this->connection->executeStatement($sql);
        } catch (Exception $e) {
        }
    }
}
