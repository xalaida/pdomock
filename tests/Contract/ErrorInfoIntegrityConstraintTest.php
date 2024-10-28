<?php

namespace Tests\Xala\Elomock\Contract;

use PDO;
use PDOException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Xala\Elomock\TestCase;
use Xala\Elomock\PDOExceptionMock;
use Xala\Elomock\PDOMock;

class ErrorInfoIntegrityConstraintTest extends TestCase
{
    #[Test]
    #[DataProvider('contracts')]
    public function itShouldFailWithIntegrityConstraintErrorExceptionUsingPreparedStatement(PDO $pdo): void
    {
        $statement = $pdo->prepare("insert into books (id, title) values (1, null)");

        try {
            $statement->execute();

            $this->fail('Exception was not thrown');
        } catch (PDOException $e) {
            static::assertSame('SQLSTATE[23000]: Integrity constraint violation: 19 NOT NULL constraint failed: books.title', $e->getMessage());
            static::assertSame('23000', $e->getCode());
            static::assertSame(['23000', 19, 'NOT NULL constraint failed: books.title'], $e->errorInfo);

            static::assertSame(['23000', 19, 'NOT NULL constraint failed: books.title'], $statement->errorInfo());
            static::assertSame('23000', $statement->errorCode());

            static::assertSame(['00000', null, null], $pdo->errorInfo());
            static::assertSame('00000', $pdo->errorCode());
        }
    }

    #[Test]
    #[DataProvider('contracts')]
    public function itShouldFailWithSyntaxErrorOnExecuteForPreparedStatementUsingWarningErrorMode(PDO $pdo): void
    {
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);

        $statement = $pdo->prepare('insert into books (id, title) values (1, null)');

        $result = $this->expectTriggerWarning(function () use ($statement) {
            return $statement->execute();
        }, 'PDOStatement::execute(): SQLSTATE[23000]: Integrity constraint violation: 19 NOT NULL constraint failed: books.title');

        static::assertFalse($result);

        static::assertSame(['23000', 19, 'NOT NULL constraint failed: books.title'], $statement->errorInfo());
        static::assertSame('23000', $statement->errorCode());

        static::assertSame(['00000', null, null], $pdo->errorInfo());
        static::assertSame('00000', $pdo->errorCode());
    }

    public static function contracts(): array
    {
        return [
            'SQLite' => [
                static::configureSqlite()
            ],

            'Mock' => [
                static::configureMock()
            ],
        ];
    }

    protected static function configureSqlite(): PDO
    {
        $pdo = new PDO('sqlite::memory:');

        $pdo->exec('create table "books" ("id" integer primary key autoincrement not null, "title" varchar not null)');

        return $pdo;
    }

    protected static function configureMock(): PDOMock
    {
        $pdo = new PDOMock();

        $pdo->expect('insert into books (id, title) values (1, null)')
            ->andFailOnExecute(PDOExceptionMock::fromErrorInfo(
                'SQLSTATE[23000]: Integrity constraint violation: 19 NOT NULL constraint failed: books.title',
                '23000',
                'NOT NULL constraint failed: books.title',
                19
            ));

        return $pdo;
    }
}
