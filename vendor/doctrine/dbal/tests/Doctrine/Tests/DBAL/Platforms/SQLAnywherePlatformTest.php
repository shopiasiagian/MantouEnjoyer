<?php

namespace Doctrine\Tests\DBAL\Platforms;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\LockMode;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\SQLAnywherePlatform;
use Doctrine\DBAL\Platforms\TrimMode;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\ColumnDiff;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Constraint;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\TableDiff;
use Doctrine\DBAL\TransactionIsolationLevel;
use Doctrine\DBAL\Types\Type;
use InvalidArgumentException;

use function mt_rand;
use function strlen;
use function substr;

/**
 * @extends AbstractPlatformTestCase<SQLAnywherePlatform>
 */
class SQLAnywherePlatformTest extends AbstractPlatformTestCase
{
    public function createPlatform(): AbstractPlatform
    {
        return new SQLAnywherePlatform();
    }

    /**
     * {@inheritDoc}
     */
    public function getGenerateAlterTableSql(): array
    {
        return [
            "ALTER TABLE mytable ADD quota INT DEFAULT NULL, DROP foo, ALTER baz VARCHAR(1) DEFAULT 'def' NOT NULL, "
                . "ALTER bloo BIT DEFAULT '0' NOT NULL",
            'ALTER TABLE mytable RENAME userlist',
        ];
    }

    protected function getGenerateForeignKeySql(): string
    {
        return 'ALTER TABLE test ADD FOREIGN KEY (fk_name_id) REFERENCES other_table (id)';
    }

    public function getGenerateIndexSql(): string
    {
        return 'CREATE INDEX my_idx ON mytable (user_name, last_login)';
    }

    public function getGenerateTableSql(): string
    {
        return 'CREATE TABLE test (id INT IDENTITY NOT NULL, test VARCHAR(255) DEFAULT NULL, PRIMARY KEY (id))';
    }

    /**
     * {@inheritDoc}
     */
    public function getGenerateTableWithMultiColumnUniqueIndexSql(): array
    {
        return [
            'CREATE TABLE test (foo VARCHAR(255) DEFAULT NULL, bar VARCHAR(255) DEFAULT NULL)',
            'CREATE UNIQUE INDEX UNIQ_D87F7E0C8C73652176FF8CAA ON test (foo, bar)',
        ];
    }

    public function getGenerateUniqueIndexSql(): string
    {
        return 'CREATE UNIQUE INDEX index_name ON test (test, test2)';
    }

    /**
     * {@inheritDoc}
     */
    protected function getQuotedColumnInForeignKeySQL(): array
    {
        return ['CREATE TABLE "quoted" ("create" VARCHAR(255) NOT NULL, foo VARCHAR(255) NOT NULL, '
                . '"bar" VARCHAR(255) NOT NULL, '
                . 'CONSTRAINT FK_WITH_RESERVED_KEYWORD FOREIGN KEY ("create", foo, "bar") '
                . 'REFERENCES "foreign" ("create", bar, "foo-bar"), '
                . 'CONSTRAINT FK_WITH_NON_RESERVED_KEYWORD FOREIGN KEY ("create", foo, "bar") '
                . 'REFERENCES foo ("create", bar, "foo-bar"), '
                . 'CONSTRAINT FK_WITH_INTENDED_QUOTATION FOREIGN KEY ("create", foo, "bar") '
                . 'REFERENCES "foo-bar" ("create", bar, "foo-bar"))',
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function getQuotedColumnInIndexSQL(): array
    {
        return [
            'CREATE TABLE "quoted" ("create" VARCHAR(255) NOT NULL)',
            'CREATE INDEX IDX_22660D028FD6E0FB ON "quoted" ("create")',
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function getQuotedNameInIndexSQL(): array
    {
        return [
            'CREATE TABLE test (column1 VARCHAR(255) NOT NULL)',
            'CREATE INDEX "key" ON test (column1)',
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function getQuotedColumnInPrimaryKeySQL(): array
    {
        return ['CREATE TABLE "quoted" ("create" VARCHAR(255) NOT NULL, PRIMARY KEY ("create"))'];
    }

    /**
     * {@inheritDoc}
     */
    public function getCreateTableColumnCommentsSQL(): array
    {
        return [
            'CREATE TABLE test (id INT NOT NULL, PRIMARY KEY (id))',
            "COMMENT ON COLUMN test.id IS 'This is a comment'",
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getAlterTableColumnCommentsSQL(): array
    {
        return [
            'ALTER TABLE mytable ADD quota INT NOT NULL',
            "COMMENT ON COLUMN mytable.quota IS 'A comment'",
            'COMMENT ON COLUMN mytable.foo IS NULL',
            "COMMENT ON COLUMN mytable.baz IS 'B comment'",
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getCreateTableColumnTypeCommentsSQL(): array
    {
        return [
            'CREATE TABLE test (id INT NOT NULL, data TEXT NOT NULL, PRIMARY KEY (id))',
            "COMMENT ON COLUMN test.data IS '(DC2Type:array)'",
        ];
    }

    public function testHasCorrectPlatformName(): void
    {
        self::assertEquals('sqlanywhere', $this->platform->getName());
    }

    public function testGeneratesCreateTableSQLWithCommonIndexes(): void
    {
        $table = new Table('test');
        $table->addColumn('id', 'integer');
        $table->addColumn('name', 'string', ['length' => 50]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['name']);
        $table->addIndex(['id', 'name'], 'composite_idx');

        self::assertEquals(
            [
                'CREATE TABLE test (id INT NOT NULL, name VARCHAR(50) NOT NULL, PRIMARY KEY (id))',
                'CREATE INDEX IDX_D87F7E0C5E237E06 ON test (name)',
                'CREATE INDEX composite_idx ON test (id, name)',
            ],
            $this->platform->getCreateTableSQL($table)
        );
    }

    public function testGeneratesCreateTableSQLWithForeignKeyConstraints(): void
    {
        $table = new Table('test');
        $table->addColumn('id', 'integer');
        $table->addColumn('fk_1', 'integer');
        $table->addColumn('fk_2', 'integer');
        $table->setPrimaryKey(['id']);
        $table->addForeignKeyConstraint('foreign_table', ['fk_1', 'fk_2'], ['pk_1', 'pk_2']);
        $table->addForeignKeyConstraint(
            'foreign_table2',
            ['fk_1', 'fk_2'],
            ['pk_1', 'pk_2'],
            [],
            'named_fk'
        );

        self::assertEquals(
            [
                'CREATE TABLE test (id INT NOT NULL, fk_1 INT NOT NULL, fk_2 INT NOT NULL, '
                . 'CONSTRAINT FK_D87F7E0C177612A38E7F4319 FOREIGN KEY (fk_1, fk_2) '
                . 'REFERENCES foreign_table (pk_1, pk_2), '
                . 'CONSTRAINT named_fk FOREIGN KEY (fk_1, fk_2) REFERENCES foreign_table2 (pk_1, pk_2))',
            ],
            $this->platform->getCreateTableSQL($table, AbstractPlatform::CREATE_FOREIGNKEYS)
        );
    }

    public function testGeneratesCreateTableSQLWithCheckConstraints(): void
    {
        $table = new Table('test');
        $table->addColumn('id', 'integer');
        $table->addColumn('check_max', 'integer', ['platformOptions' => ['max' => 10]]);
        $table->addColumn('check_min', 'integer', ['platformOptions' => ['min' => 10]]);
        $table->setPrimaryKey(['id']);

        self::assertEquals(
            [
                'CREATE TABLE test (id INT NOT NULL, check_max INT NOT NULL, check_min INT NOT NULL, '
                    . 'PRIMARY KEY (id), CHECK (check_max <= 10), CHECK (check_min >= 10))',
            ],
            $this->platform->getCreateTableSQL($table)
        );
    }

    public function testGeneratesTableAlterationWithRemovedColumnCommentSql(): void
    {
        $table = new Table('mytable');
        $table->addColumn('foo', 'string', ['comment' => 'foo comment']);

        $tableDiff                        = new TableDiff('mytable');
        $tableDiff->fromTable             = $table;
        $tableDiff->changedColumns['foo'] = new ColumnDiff(
            'foo',
            new Column('foo', Type::getType('string')),
            ['comment']
        );

        self::assertEquals(
            ['COMMENT ON COLUMN mytable.foo IS NULL'],
            $this->platform->getAlterTableSQL($tableDiff)
        );
    }

    /**
     * @param int|bool|null $lockMode
     *
     * @dataProvider getLockHints
     */
    public function testAppendsLockHint($lockMode, string $lockHint): void
    {
        $fromClause     = 'FROM users';
        $expectedResult = $fromClause . $lockHint;

        self::assertSame($expectedResult, $this->platform->appendLockHint($fromClause, $lockMode));
    }

    /**
     * @return mixed[][]
     */
    public static function getLockHints(): iterable
    {
        return [
            [null, ''],
            [false, ''],
            [true, ''],
            [LockMode::NONE, ''],
            [LockMode::OPTIMISTIC, ''],
            [LockMode::PESSIMISTIC_READ, ' WITH (UPDLOCK)'],
            [LockMode::PESSIMISTIC_WRITE, ' WITH (XLOCK)'],
        ];
    }

    public function testHasCorrectMaxIdentifierLength(): void
    {
        self::assertEquals(128, $this->platform->getMaxIdentifierLength());
    }

    public function testFixesSchemaElementNames(): void
    {
        $maxIdentifierLength = $this->platform->getMaxIdentifierLength();
        $characters          = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $schemaElementName   = '';

        for ($i = 0; $i < $maxIdentifierLength + 100; $i++) {
            $schemaElementName .= $characters[mt_rand(0, strlen($characters) - 1)];
        }

        $fixedSchemaElementName = substr($schemaElementName, 0, $maxIdentifierLength);

        self::assertEquals(
            $fixedSchemaElementName,
            $this->platform->fixSchemaElementName($schemaElementName)
        );
        self::assertEquals(
            $fixedSchemaElementName,
            $this->platform->fixSchemaElementName($fixedSchemaElementName)
        );
    }

    public function testGeneratesColumnTypesDeclarationSQL(): void
    {
        $fullColumnDef = [
            'length' => 10,
            'fixed' => true,
            'unsigned' => true,
            'autoincrement' => true,
        ];

        self::assertEquals('SMALLINT', $this->platform->getSmallIntTypeDeclarationSQL([]));
        self::assertEquals('UNSIGNED SMALLINT', $this->platform->getSmallIntTypeDeclarationSQL(['unsigned' => true]));
        self::assertEquals(
            'UNSIGNED SMALLINT IDENTITY',
            $this->platform->getSmallIntTypeDeclarationSQL($fullColumnDef)
        );

        self::assertEquals('INT', $this->platform->getIntegerTypeDeclarationSQL([]));
        self::assertEquals('UNSIGNED INT', $this->platform->getIntegerTypeDeclarationSQL(['unsigned' => true]));
        self::assertEquals('UNSIGNED INT IDENTITY', $this->platform->getIntegerTypeDeclarationSQL($fullColumnDef));
        self::assertEquals('BIGINT', $this->platform->getBigIntTypeDeclarationSQL([]));
        self::assertEquals('UNSIGNED BIGINT', $this->platform->getBigIntTypeDeclarationSQL(['unsigned' => true]));
        self::assertEquals('UNSIGNED BIGINT IDENTITY', $this->platform->getBigIntTypeDeclarationSQL($fullColumnDef));
        self::assertEquals('LONG BINARY', $this->platform->getBlobTypeDeclarationSQL($fullColumnDef));
        self::assertEquals('BIT', $this->platform->getBooleanTypeDeclarationSQL($fullColumnDef));
        self::assertEquals('TEXT', $this->platform->getClobTypeDeclarationSQL($fullColumnDef));
        self::assertEquals('DATE', $this->platform->getDateTypeDeclarationSQL($fullColumnDef));
        self::assertEquals('DATETIME', $this->platform->getDateTimeTypeDeclarationSQL($fullColumnDef));
        self::assertEquals('TIME', $this->platform->getTimeTypeDeclarationSQL($fullColumnDef));
        self::assertEquals('UNIQUEIDENTIFIER', $this->platform->getGuidTypeDeclarationSQL($fullColumnDef));

        self::assertEquals(1, $this->platform->getVarcharDefaultLength());
        self::assertEquals(32767, $this->platform->getVarcharMaxLength());
    }

    public function testHasNativeGuidType(): void
    {
        self::assertTrue($this->platform->hasNativeGuidType());
    }

    public function testGeneratesDDLSnippets(): void
    {
        self::assertEquals("CREATE DATABASE 'foobar'", $this->platform->getCreateDatabaseSQL('foobar'));
        self::assertEquals("CREATE DATABASE 'foobar'", $this->platform->getCreateDatabaseSQL('"foobar"'));
        self::assertEquals("CREATE DATABASE 'create'", $this->platform->getCreateDatabaseSQL('create'));
        self::assertEquals("DROP DATABASE 'foobar'", $this->platform->getDropDatabaseSQL('foobar'));
        self::assertEquals("DROP DATABASE 'foobar'", $this->platform->getDropDatabaseSQL('"foobar"'));
        self::assertEquals("DROP DATABASE 'create'", $this->platform->getDropDatabaseSQL('create'));
        self::assertEquals('CREATE GLOBAL TEMPORARY TABLE', $this->platform->getCreateTemporaryTableSnippetSQL());
        self::assertEquals("START DATABASE 'foobar' AUTOSTOP OFF", $this->platform->getStartDatabaseSQL('foobar'));
        self::assertEquals("START DATABASE 'foobar' AUTOSTOP OFF", $this->platform->getStartDatabaseSQL('"foobar"'));
        self::assertEquals("START DATABASE 'create' AUTOSTOP OFF", $this->platform->getStartDatabaseSQL('create'));
        self::assertEquals('STOP DATABASE "foobar" UNCONDITIONALLY', $this->platform->getStopDatabaseSQL('foobar'));
        self::assertEquals('STOP DATABASE "foobar" UNCONDITIONALLY', $this->platform->getStopDatabaseSQL('"foobar"'));
        self::assertEquals('STOP DATABASE "create" UNCONDITIONALLY', $this->platform->getStopDatabaseSQL('create'));
        self::assertEquals('TRUNCATE TABLE foobar', $this->platform->getTruncateTableSQL('foobar'));
        self::assertEquals('TRUNCATE TABLE foobar', $this->platform->getTruncateTableSQL('foobar'), true);

        $viewSql = 'SELECT * FROM footable';
        self::assertEquals(
            'CREATE VIEW fooview AS ' . $viewSql,
            $this->platform->getCreateViewSQL('fooview', $viewSql)
        );
        self::assertEquals('DROP VIEW fooview', $this->platform->getDropViewSQL('fooview'));
    }

    public function testGeneratesPrimaryKeyDeclarationSQL(): void
    {
        self::assertEquals(
            'CONSTRAINT pk PRIMARY KEY CLUSTERED (a, b)',
            $this->platform->getPrimaryKeyDeclarationSQL(
                new Index('', ['a', 'b'], true, true, ['clustered']),
                'pk'
            )
        );
        self::assertEquals(
            'PRIMARY KEY (a, b)',
            $this->platform->getPrimaryKeyDeclarationSQL(
                new Index('', ['a', 'b'], true, true)
            )
        );
    }

    public function testCannotGeneratePrimaryKeyDeclarationSQLWithEmptyColumns(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->platform->getPrimaryKeyDeclarationSQL(new Index('pk', [], true, true));
    }

    public function testGeneratesCreateUnnamedPrimaryKeySQL(): void
    {
        self::assertEquals(
            'ALTER TABLE foo ADD PRIMARY KEY CLUSTERED (a, b)',
            $this->platform->getCreatePrimaryKeySQL(
                new Index('pk', ['a', 'b'], true, true, ['clustered']),
                'foo'
            )
        );
        self::assertEquals(
            'ALTER TABLE foo ADD PRIMARY KEY (a, b)',
            $this->platform->getCreatePrimaryKeySQL(
                new Index('any_pk_name', ['a', 'b'], true, true),
                new Table('foo')
            )
        );
    }

    public function testGeneratesUniqueConstraintDeclarationSQL(): void
    {
        self::assertEquals(
            'CONSTRAINT unique_constraint UNIQUE CLUSTERED (a, b)',
            $this->platform->getUniqueConstraintDeclarationSQL(
                'unique_constraint',
                new Index('', ['a', 'b'], true, false, ['clustered'])
            )
        );
        self::assertEquals(
            'UNIQUE (a, b)',
            $this->platform->getUniqueConstraintDeclarationSQL('', new Index('', ['a', 'b'], true, false))
        );
    }

    public function testCannotGenerateUniqueConstraintDeclarationSQLWithEmptyColumns(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->platform->getUniqueConstraintDeclarationSQL('constr', new Index('constr', [], true));
    }

    public function testGeneratesForeignKeyConstraintsWithAdvancedPlatformOptionsSQL(): void
    {
        self::assertEquals(
            'CONSTRAINT fk ' .
                'NOT NULL FOREIGN KEY (a, b) ' .
                'REFERENCES foreign_table (c, d) ' .
                'MATCH UNIQUE SIMPLE ON UPDATE CASCADE ON DELETE SET NULL CHECK ON COMMIT CLUSTERED FOR OLAP WORKLOAD',
            $this->platform->getForeignKeyDeclarationSQL(
                new ForeignKeyConstraint(['a', 'b'], 'foreign_table', ['c', 'd'], 'fk', [
                    'notnull' => true,
                    'match' => SQLAnywherePlatform::FOREIGN_KEY_MATCH_SIMPLE_UNIQUE,
                    'onUpdate' => 'CASCADE',
                    'onDelete' => 'SET NULL',
                    'check_on_commit' => true,
                    'clustered' => true,
                    'for_olap_workload' => true,
                ])
            )
        );
        self::assertEquals(
            'FOREIGN KEY (a, b) REFERENCES foreign_table (c, d)',
            $this->platform->getForeignKeyDeclarationSQL(
                new ForeignKeyConstraint(['a', 'b'], 'foreign_table', ['c', 'd'])
            )
        );
    }

    public function testGeneratesForeignKeyMatchClausesSQL(): void
    {
        self::assertEquals('SIMPLE', $this->platform->getForeignKeyMatchClauseSQL(1));
        self::assertEquals('FULL', $this->platform->getForeignKeyMatchClauseSQL(2));
        self::assertEquals('UNIQUE SIMPLE', $this->platform->getForeignKeyMatchClauseSQL(129));
        self::assertEquals('UNIQUE FULL', $this->platform->getForeignKeyMatchClauseSQL(130));
    }

    public function testCannotGenerateInvalidForeignKeyMatchClauseSQL(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->platform->getForeignKeyMatchCLauseSQL(3);
    }

    public function testCannotGenerateForeignKeyConstraintSQLWithEmptyLocalColumns(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->platform->getForeignKeyDeclarationSQL(new ForeignKeyConstraint([], 'foreign_tbl', ['c', 'd']));
    }

    public function testCannotGenerateForeignKeyConstraintSQLWithEmptyForeignColumns(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->platform->getForeignKeyDeclarationSQL(new ForeignKeyConstraint(['a', 'b'], 'foreign_tbl', []));
    }

    public function testCannotGenerateForeignKeyConstraintSQLWithEmptyForeignTableName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->platform->getForeignKeyDeclarationSQL(new ForeignKeyConstraint(['a', 'b'], '', ['c', 'd']));
    }

    public function testCannotGenerateCommonIndexWithCreateConstraintSQL(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->platform->getCreateConstraintSQL(new Index('fooindex', []), new Table('footable'));
    }

    public function testCannotGenerateCustomConstraintWithCreateConstraintSQL(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->platform->getCreateConstraintSQL($this->createMock(Constraint::class), 'footable');
    }

    public function testGeneratesCreateIndexWithAdvancedPlatformOptionsSQL(): void
    {
        self::assertEquals(
            'CREATE VIRTUAL UNIQUE CLUSTERED INDEX fooindex ON footable (a, b) FOR OLAP WORKLOAD',
            $this->platform->getCreateIndexSQL(
                new Index(
                    'fooindex',
                    ['a', 'b'],
                    true,
                    false,
                    ['virtual', 'clustered', 'for_olap_workload']
                ),
                'footable'
            )
        );
    }

    public function testDoesNotSupportIndexDeclarationInCreateAlterTableStatements(): void
    {
        $this->expectException(Exception::class);

        $this->platform->getIndexDeclarationSQL('index', new Index('index', []));
    }

    public function testGeneratesDropIndexSQL(): void
    {
        $index = new Index('fooindex', []);

        self::assertEquals('DROP INDEX fooindex', $this->platform->getDropIndexSQL($index));
        self::assertEquals('DROP INDEX footable.fooindex', $this->platform->getDropIndexSQL($index, 'footable'));
        self::assertEquals('DROP INDEX footable.fooindex', $this->platform->getDropIndexSQL(
            $index,
            new Table('footable')
        ));
    }

    /**
     * @psalm-suppress InvalidArgument
     */
    public function testCannotGenerateDropIndexSQLWithInvalidIndexParameter(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->platform->getDropIndexSQL(['index'], 'table');
    }

    /**
     * @psalm-suppress InvalidArgument
     */
    public function testCannotGenerateDropIndexSQLWithInvalidTableParameter(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->platform->getDropIndexSQL('index', ['table']);
    }

    public function testGeneratesSQLSnippets(): void
    {
        self::assertEquals('STRING(column1, "string1", column2, "string2")', $this->platform->getConcatExpression(
            'column1',
            '"string1"',
            'column2',
            '"string2"'
        ));
        self::assertEquals('CURRENT DATE', $this->platform->getCurrentDateSQL());
        self::assertEquals('CURRENT TIME', $this->platform->getCurrentTimeSQL());
        self::assertEquals('CURRENT TIMESTAMP', $this->platform->getCurrentTimestampSQL());
        self::assertEquals(
            "DATEADD(DAY, 4, '1987/05/02')",
            $this->platform->getDateAddDaysExpression("'1987/05/02'", 4)
        );

        self::assertEquals(
            "DATEADD(HOUR, 12, '1987/05/02')",
            $this->platform->getDateAddHourExpression("'1987/05/02'", 12)
        );

        self::assertEquals(
            "DATEADD(MINUTE, 2, '1987/05/02')",
            $this->platform->getDateAddMinutesExpression("'1987/05/02'", 2)
        );

        self::assertEquals(
            "DATEADD(MONTH, 102, '1987/05/02')",
            $this->platform->getDateAddMonthExpression("'1987/05/02'", 102)
        );

        self::assertEquals(
            "DATEADD(QUARTER, 5, '1987/05/02')",
            $this->platform->getDateAddQuartersExpression("'1987/05/02'", 5)
        );

        self::assertEquals(
            "DATEADD(SECOND, 1, '1987/05/02')",
            $this->platform->getDateAddSecondsExpression("'1987/05/02'", 1)
        );

        self::assertEquals(
            "DATEADD(WEEK, 3, '1987/05/02')",
            $this->platform->getDateAddWeeksExpression("'1987/05/02'", 3)
        );

        self::assertEquals(
            "DATEADD(YEAR, 10, '1987/05/02')",
            $this->platform->getDateAddYearsExpression("'1987/05/02'", 10)
        );

        self::assertEquals(
            "DATEDIFF(day, '1987/04/01', '1987/05/02')",
            $this->platform->getDateDiffExpression("'1987/05/02'", "'1987/04/01'")
        );

        self::assertEquals(
            "DATEADD(DAY, -1 * 4, '1987/05/02')",
            $this->platform->getDateSubDaysExpression("'1987/05/02'", 4)
        );

        self::assertEquals(
            "DATEADD(HOUR, -1 * 12, '1987/05/02')",
            $this->platform->getDateSubHourExpression("'1987/05/02'", 12)
        );

        self::assertEquals(
            "DATEADD(MINUTE, -1 * 2, '1987/05/02')",
            $this->platform->getDateSubMinutesExpression("'1987/05/02'", 2)
        );

        self::assertEquals(
            "DATEADD(MONTH, -1 * 102, '1987/05/02')",
            $this->platform->getDateSubMonthExpression("'1987/05/02'", 102)
        );

        self::assertEquals(
            "DATEADD(QUARTER, -1 * 5, '1987/05/02')",
            $this->platform->getDateSubQuartersExpression("'1987/05/02'", 5)
        );

        self::assertEquals(
            "DATEADD(SECOND, -1 * 1, '1987/05/02')",
            $this->platform->getDateSubSecondsExpression("'1987/05/02'", 1)
        );

        self::assertEquals(
            "DATEADD(WEEK, -1 * 3, '1987/05/02')",
            $this->platform->getDateSubWeeksExpression("'1987/05/02'", 3)
        );

        self::assertEquals(
            "DATEADD(YEAR, -1 * 10, '1987/05/02')",
            $this->platform->getDateSubYearsExpression("'1987/05/02'", 10)
        );

        self::assertEquals('Y-m-d H:i:s.u', $this->platform->getDateTimeFormatString());
        self::assertEquals('H:i:s.u', $this->platform->getTimeFormatString());
        self::assertEquals('', $this->platform->getForUpdateSQL());
        self::assertEquals('NEWID()', $this->platform->getGuidExpression());

        self::assertEquals(
            'LOCATE(string_column, substring_column)',
            $this->platform->getLocateExpression('string_column', 'substring_column')
        );

        self::assertEquals(
            'LOCATE(string_column, substring_column, 1)',
            $this->platform->getLocateExpression('string_column', 'substring_column', 1)
        );

        self::assertEquals("HASH(column, 'MD5')", $this->platform->getMd5Expression('column'));
        self::assertEquals('SUBSTRING(column, 5)', $this->platform->getSubstringExpression('column', 5));
        self::assertEquals('SUBSTRING(column, 5, 2)', $this->platform->getSubstringExpression('column', 5, 2));
        self::assertEquals('GLOBAL TEMPORARY', $this->platform->getTemporaryTableSQL());
        self::assertEquals(
            'LTRIM(column)',
            $this->platform->getTrimExpression('column', TrimMode::LEADING)
        );
        self::assertEquals(
            'RTRIM(column)',
            $this->platform->getTrimExpression('column', TrimMode::TRAILING)
        );
        self::assertEquals(
            'TRIM(column)',
            $this->platform->getTrimExpression('column')
        );
        self::assertEquals(
            'TRIM(column)',
            $this->platform->getTrimExpression('column', TrimMode::UNSPECIFIED)
        );
        self::assertEquals(
            "SUBSTR(column, PATINDEX('%[^' + c + ']%', column))",
            $this->platform->getTrimExpression('column', TrimMode::LEADING, 'c')
        );
        self::assertEquals(
            "REVERSE(SUBSTR(REVERSE(column), PATINDEX('%[^' + c + ']%', REVERSE(column))))",
            $this->platform->getTrimExpression('column', TrimMode::TRAILING, 'c')
        );
        self::assertEquals(
            "REVERSE(SUBSTR(REVERSE(SUBSTR(column, PATINDEX('%[^' + c + ']%', column))), PATINDEX('%[^' + c + ']%', " .
            "REVERSE(SUBSTR(column, PATINDEX('%[^' + c + ']%', column))))))",
            $this->platform->getTrimExpression('column', TrimMode::UNSPECIFIED, 'c')
        );
        self::assertEquals(
            "REVERSE(SUBSTR(REVERSE(SUBSTR(column, PATINDEX('%[^' + c + ']%', column))), PATINDEX('%[^' + c + ']%', " .
            "REVERSE(SUBSTR(column, PATINDEX('%[^' + c + ']%', column))))))",
            $this->platform->getTrimExpression('column', TrimMode::UNSPECIFIED, 'c')
        );
    }

    public function testDoesNotSupportRegexp(): void
    {
        $this->expectException(Exception::class);

        $this->platform->getRegexpExpression();
    }

    public function testHasCorrectDateTimeTzFormatString(): void
    {
        // Date time type with timezone is not supported before version 12.
        // For versions before we have to ensure that the date time with timezone format
        // equals the normal date time format so that it corresponds to the declaration SQL equality
        // (datetimetz -> datetime).
        self::assertEquals($this->platform->getDateTimeFormatString(), $this->platform->getDateTimeTzFormatString());
    }

    public function testHasCorrectDefaultTransactionIsolationLevel(): void
    {
        self::assertEquals(
            TransactionIsolationLevel::READ_UNCOMMITTED,
            $this->platform->getDefaultTransactionIsolationLevel()
        );
    }

    public function testGeneratesTransactionsCommands(): void
    {
        self::assertEquals(
            'SET TEMPORARY OPTION isolation_level = 0',
            $this->platform->getSetTransactionIsolationSQL(TransactionIsolationLevel::READ_UNCOMMITTED)
        );
        self::assertEquals(
            'SET TEMPORARY OPTION isolation_level = 1',
            $this->platform->getSetTransactionIsolationSQL(TransactionIsolationLevel::READ_COMMITTED)
        );
        self::assertEquals(
            'SET TEMPORARY OPTION isolation_level = 2',
            $this->platform->getSetTransactionIsolationSQL(TransactionIsolationLevel::REPEATABLE_READ)
        );
        self::assertEquals(
            'SET TEMPORARY OPTION isolation_level = 3',
            $this->platform->getSetTransactionIsolationSQL(TransactionIsolationLevel::SERIALIZABLE)
        );
    }

    public function testCannotGenerateTransactionCommandWithInvalidIsolationLevel(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->platform->getSetTransactionIsolationSQL('invalid_transaction_isolation_level');
    }

    public function testModifiesLimitQuery(): void
    {
        self::assertEquals(
            'SELECT TOP 10 * FROM user',
            $this->platform->modifyLimitQuery('SELECT * FROM user', 10, 0)
        );
    }

    public function testModifiesLimitQueryWithEmptyOffset(): void
    {
        self::assertEquals(
            'SELECT TOP 10 * FROM user',
            $this->platform->modifyLimitQuery('SELECT * FROM user', 10)
        );
    }

    public function testModifiesLimitQueryWithOffset(): void
    {
        self::assertEquals(
            'SELECT TOP 10 START AT 6 * FROM user',
            $this->platform->modifyLimitQuery('SELECT * FROM user', 10, 5)
        );
        self::assertEquals(
            'SELECT TOP 0 START AT 6 * FROM user',
            $this->platform->modifyLimitQuery('SELECT * FROM user', 0, 5)
        );
    }

    public function testModifiesLimitQueryWithSubSelect(): void
    {
        self::assertEquals(
            'SELECT TOP 10 * FROM (SELECT u.id as uid, u.name as uname FROM user) AS doctrine_tbl',
            $this->platform->modifyLimitQuery(
                'SELECT * FROM (SELECT u.id as uid, u.name as uname FROM user) AS doctrine_tbl',
                10
            )
        );
    }

    public function testModifiesLimitQueryWithoutLimit(): void
    {
        self::assertEquals(
            'SELECT TOP ALL START AT 11 n FROM Foo',
            $this->platform->modifyLimitQuery('SELECT n FROM Foo', null, 10)
        );
    }

    public function testPrefersIdentityColumns(): void
    {
        self::assertTrue($this->platform->prefersIdentityColumns());
    }

    public function testDoesNotPreferSequences(): void
    {
        self::assertFalse($this->platform->prefersSequences());
    }

    public function testSupportsIdentityColumns(): void
    {
        self::assertTrue($this->platform->supportsIdentityColumns());
    }

    public function testSupportsPrimaryConstraints(): void
    {
        self::assertTrue($this->platform->supportsPrimaryConstraints());
    }

    public function testSupportsForeignKeyConstraints(): void
    {
        self::assertTrue($this->platform->supportsForeignKeyConstraints());
    }

    public function testSupportsForeignKeyOnUpdate(): void
    {
        self::assertTrue($this->platform->supportsForeignKeyOnUpdate());
    }

    public function testSupportsAlterTable(): void
    {
        self::assertTrue($this->platform->supportsAlterTable());
    }

    public function testSupportsTransactions(): void
    {
        self::assertTrue($this->platform->supportsTransactions());
    }

    public function testSupportsSchemas(): void
    {
        self::assertFalse($this->platform->supportsSchemas());
    }

    public function testSupportsIndexes(): void
    {
        self::assertTrue($this->platform->supportsIndexes());
    }

    public function testSupportsCommentOnStatement(): void
    {
        self::assertTrue($this->platform->supportsCommentOnStatement());
    }

    public function testSupportsSavePoints(): void
    {
        self::assertTrue($this->platform->supportsSavepoints());
    }

    public function testSupportsReleasePoints(): void
    {
        self::assertTrue($this->platform->supportsReleaseSavepoints());
    }

    public function testSupportsCreateDropDatabase(): void
    {
        self::assertTrue($this->platform->supportsCreateDropDatabase());
    }

    public function testSupportsGettingAffectedRows(): void
    {
        self::assertTrue($this->platform->supportsGettingAffectedRows());
    }

    public function testDoesNotSupportSequences(): void
    {
        self::assertFalse($this->platform->supportsSequences());
    }

    public function testDoesNotSupportInlineColumnComments(): void
    {
        self::assertFalse($this->platform->supportsInlineColumnComments());
    }

    public function testCannotEmulateSchemas(): void
    {
        self::assertFalse($this->platform->canEmulateSchemas());
    }

    public function testInitializesDoctrineTypeMappings(): void
    {
        self::assertTrue($this->platform->hasDoctrineTypeMappingFor('integer'));
        self::assertSame('integer', $this->platform->getDoctrineTypeMapping('integer'));

        self::assertTrue($this->platform->hasDoctrineTypeMappingFor('binary'));
        self::assertSame('binary', $this->platform->getDoctrineTypeMapping('binary'));

        self::assertTrue($this->platform->hasDoctrineTypeMappingFor('varbinary'));
        self::assertSame('binary', $this->platform->getDoctrineTypeMapping('varbinary'));
    }

    protected function getBinaryDefaultLength(): int
    {
        return 1;
    }

    protected function getBinaryMaxLength(): int
    {
        return 32767;
    }

    public function testReturnsBinaryTypeDeclarationSQL(): void
    {
        self::assertSame('VARBINARY(1)', $this->platform->getBinaryTypeDeclarationSQL([]));
        self::assertSame('VARBINARY(1)', $this->platform->getBinaryTypeDeclarationSQL(['length' => 0]));
        self::assertSame('VARBINARY(32767)', $this->platform->getBinaryTypeDeclarationSQL(['length' => 32767]));

        self::assertSame('BINARY(1)', $this->platform->getBinaryTypeDeclarationSQL(['fixed' => true]));
        self::assertSame('BINARY(1)', $this->platform->getBinaryTypeDeclarationSQL(['fixed' => true, 'length' => 0]));

        self::assertSame(
            'BINARY(32767)',
            $this->platform->getBinaryTypeDeclarationSQL(['fixed' => true, 'length' => 32767])
        );
    }

    public function testReturnsBinaryTypeLongerThanMaxDeclarationSQL(): void
    {
        self::assertSame('LONG BINARY', $this->platform->getBinaryTypeDeclarationSQL(['length' => 32768]));

        self::assertSame('LONG BINARY', $this->platform->getBinaryTypeDeclarationSQL([
            'fixed' => true,
            'length' => 32768,
        ]));
    }

    /**
     * {@inheritDoc}
     */
    protected function getAlterTableRenameIndexSQL(): array
    {
        return ['ALTER INDEX idx_foo ON mytable RENAME TO idx_bar'];
    }

    /**
     * {@inheritDoc}
     */
    protected function getQuotedAlterTableRenameIndexSQL(): array
    {
        return [
            'ALTER INDEX "create" ON "table" RENAME TO "select"',
            'ALTER INDEX "foo" ON "table" RENAME TO "bar"',
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getQuotedAlterTableRenameColumnSQL(): array
    {
        return [
            'ALTER TABLE mytable RENAME unquoted1 TO unquoted',
            'ALTER TABLE mytable RENAME unquoted2 TO "where"',
            'ALTER TABLE mytable RENAME unquoted3 TO "foo"',
            'ALTER TABLE mytable RENAME "create" TO reserved_keyword',
            'ALTER TABLE mytable RENAME "table" TO "from"',
            'ALTER TABLE mytable RENAME "select" TO "bar"',
            'ALTER TABLE mytable RENAME quoted1 TO quoted',
            'ALTER TABLE mytable RENAME quoted2 TO "and"',
            'ALTER TABLE mytable RENAME quoted3 TO "baz"',
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getQuotedAlterTableChangeColumnLengthSQL(): array
    {
        $this->markTestIncomplete('Not implemented yet');
    }

    /**
     * {@inheritDoc}
     */
    protected function getAlterTableRenameIndexInSchemaSQL(): array
    {
        return ['ALTER INDEX idx_foo ON myschema.mytable RENAME TO idx_bar'];
    }

    /**
     * {@inheritDoc}
     */
    protected function getQuotedAlterTableRenameIndexInSchemaSQL(): array
    {
        return [
            'ALTER INDEX "create" ON "schema"."table" RENAME TO "select"',
            'ALTER INDEX "foo" ON "schema"."table" RENAME TO "bar"',
        ];
    }

    public function testReturnsGuidTypeDeclarationSQL(): void
    {
        self::assertSame('UNIQUEIDENTIFIER', $this->platform->getGuidTypeDeclarationSQL([]));
    }

    /**
     * {@inheritdoc}
     */
    public function getAlterTableRenameColumnSQL(): array
    {
        return ['ALTER TABLE foo RENAME bar TO baz'];
    }

    /**
     * {@inheritdoc}
     */
    protected function getQuotesTableIdentifiersInAlterTableSQL(): array
    {
        return [
            'ALTER TABLE "foo" DROP FOREIGN KEY fk1',
            'ALTER TABLE "foo" DROP FOREIGN KEY fk2',
            'ALTER TABLE "foo" RENAME id TO war',
            'ALTER TABLE "foo" ADD bloo INT NOT NULL, DROP baz, ALTER bar INT DEFAULT NULL',
            'ALTER TABLE "foo" RENAME "table"',
            'ALTER TABLE "table" ADD CONSTRAINT fk_add FOREIGN KEY (fk3) REFERENCES fk_table (id)',
            'ALTER TABLE "table" ADD CONSTRAINT fk2 FOREIGN KEY (fk2) REFERENCES fk_table2 (id)',
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getCommentOnColumnSQL(): array
    {
        return [
            'COMMENT ON COLUMN foo.bar IS \'comment\'',
            'COMMENT ON COLUMN "Foo"."BAR" IS \'comment\'',
            'COMMENT ON COLUMN "select"."from" IS \'comment\'',
        ];
    }

    public function testAltersTableColumnCommentWithExplicitlyQuotedIdentifiers(): void
    {
        $table1 = new Table('"foo"', [new Column('"bar"', Type::getType('integer'))]);
        $table2 = new Table('"foo"', [new Column('"bar"', Type::getType('integer'), ['comment' => 'baz'])]);

        $comparator = new Comparator();

        $tableDiff = $comparator->diffTable($table1, $table2);

        self::assertInstanceOf(TableDiff::class, $tableDiff);
        self::assertSame(
            ['COMMENT ON COLUMN "foo"."bar" IS \'baz\''],
            $this->platform->getAlterTableSQL($tableDiff)
        );
    }

    /**
     * {@inheritdoc}
     */
    public static function getReturnsForeignKeyReferentialActionSQL(): iterable
    {
        return [
            ['CASCADE', 'CASCADE'],
            ['SET NULL', 'SET NULL'],
            ['NO ACTION', 'RESTRICT'],
            ['RESTRICT', 'RESTRICT'],
            ['SET DEFAULT', 'SET DEFAULT'],
            ['CaScAdE', 'CASCADE'],
        ];
    }

    protected function getQuotesReservedKeywordInUniqueConstraintDeclarationSQL(): string
    {
        return 'CONSTRAINT "select" UNIQUE (foo)';
    }

    protected function getQuotesReservedKeywordInIndexDeclarationSQL(): string
    {
        return ''; // not supported by this platform
    }

    protected function getQuotesReservedKeywordInTruncateTableSQL(): string
    {
        return 'TRUNCATE TABLE "select"';
    }

    protected function supportsInlineIndexDeclaration(): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    protected function getAlterStringToFixedStringSQL(): array
    {
        return ['ALTER TABLE mytable ALTER name CHAR(2) NOT NULL'];
    }

    /**
     * {@inheritdoc}
     */
    protected function getGeneratesAlterTableRenameIndexUsedByForeignKeySQL(): array
    {
        return ['ALTER INDEX idx_foo ON mytable RENAME TO idx_foo_renamed'];
    }

    public function testQuotesSchemaNameInListTableColumnsSQL(): void
    {
        self::assertStringContainsStringIgnoringCase(
            "'Foo''Bar\\'",
            $this->platform->getListTableColumnsSQL("Foo'Bar\\.baz_table")
        );
    }

    public function testQuotesTableNameInListTableConstraintsSQL(): void
    {
        self::assertStringContainsStringIgnoringCase(
            "'Foo''Bar\\'",
            $this->platform->getListTableConstraintsSQL("Foo'Bar\\")
        );
    }

    public function testQuotesSchemaNameInListTableConstraintsSQL(): void
    {
        self::assertStringContainsStringIgnoringCase(
            "'Foo''Bar\\'",
            $this->platform->getListTableConstraintsSQL("Foo'Bar\\.baz_table")
        );
    }

    public function testQuotesTableNameInListTableForeignKeysSQL(): void
    {
        self::assertStringContainsStringIgnoringCase(
            "'Foo''Bar\\'",
            $this->platform->getListTableForeignKeysSQL("Foo'Bar\\")
        );
    }

    public function testQuotesSchemaNameInListTableForeignKeysSQL(): void
    {
        self::assertStringContainsStringIgnoringCase(
            "'Foo''Bar\\'",
            $this->platform->getListTableForeignKeysSQL("Foo'Bar\\.baz_table")
        );
    }

    public function testQuotesTableNameInListTableIndexesSQL(): void
    {
        self::assertStringContainsStringIgnoringCase(
            "'Foo''Bar\\'",
            $this->platform->getListTableIndexesSQL("Foo'Bar\\")
        );
    }

    public function testQuotesSchemaNameInListTableIndexesSQL(): void
    {
        self::assertStringContainsStringIgnoringCase(
            "'Foo''Bar\\'",
            $this->platform->getListTableIndexesSQL("Foo'Bar\\.baz_table")
        );
    }
}
