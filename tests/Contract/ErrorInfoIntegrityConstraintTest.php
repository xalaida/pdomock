<?php

namespace Tests\Xalaida\PDOMock\Contract;

use PDO;
use PDOException;
use Tests\Xalaida\PDOMock\TestCase;
use Xalaida\PDOMock\PDOMockException;
use Xalaida\PDOMock\PDOMock;

class ErrorInfoIntegrityConstraintTest extends TestCase
{
    /**
     * @test
     * @dataProvider contracts
     * @param PDO $pdo
     * @return void
     */
    public function itShouldFailWithIntegrityConstraintErrorExceptionUsingPreparedStatement($pdo)
    {
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

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

    /**
     * @test
     * @dataProvider contracts
     * @param PDO $pdo
     * @return void
     */
    public function itShouldFailWithSyntaxErrorOnExecuteForPreparedStatementUsingWarningErrorMode($pdo)
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

    /**
     * @return array<string, array<int, PDO>>
     */
    public static function contracts()
    {
        return [
            'SQLite' => [
                static::configureSqlite(),
            ],

            'Mock' => [
                static::configureMock(),
            ],
        ];
    }

    /**
     * @return PDO
     */
    protected static function configureSqlite()
    {
        $pdo = new PDO('sqlite::memory:');

        $pdo->exec('create table "books" ("id" integer primary key autoincrement not null, "title" varchar not null)');

        return $pdo;
    }

    /**
     * @return PDOMock
     */
    protected static function configureMock()
    {
        $pdo = new PDOMock();

        $pdo->expect('insert into books (id, title) values (1, null)')
            ->willFail(PDOMockException::fromErrorInfo(
                'SQLSTATE[23000]: Integrity constraint violation: 19 NOT NULL constraint failed: books.title',
                '23000',
                'NOT NULL constraint failed: books.title',
                19
            ));

        return $pdo;
    }
}
