<?php

namespace Tests\Xala\Elomock\Mirror;

use PDO;
use PHPUnit\Framework\Attributes\Test;
use Xala\Elomock\PDOMock;

/**
 * @todo handle nested transactions
 */
class TransactionTest extends TestCase
{
    #[Test]
    public function itShouldExecuteQueryInTransaction(): void
    {
        $scenario = function (PDO $pdo) {
            static::assertFalse($pdo->inTransaction());

            static::assertTrue($pdo->beginTransaction());

            $pdo->exec('insert into "books" ("title") values ("Kaidash’s Family")');

            static::assertTrue($pdo->inTransaction());

            static::assertTrue($pdo->commit());

            static::assertFalse($pdo->inTransaction());
        };

        $scenario($this->sqlite());

        $mock = new PDOMock();
        $mock->expectBeginTransaction();
        $mock->expect('insert into "books" ("title") values ("Kaidash’s Family")');
        $mock->expectCommit();

        $scenario($mock);

        $mock->assertExpectationsFulfilled();
    }

    #[Test]
    public function itShouldRollbackTransaction(): void
    {
        $scenario = function (PDO $pdo) {
            static::assertTrue($pdo->beginTransaction());

            $pdo->exec('insert into "books" ("title") values ("Kaidash’s Family")');

            static::assertTrue($pdo->rollBack());
        };

        $scenario($this->sqlite());

        $mock = new PDOMock();
        $mock->expectBeginTransaction();
        $mock->expect('insert into "books" ("title") values ("Kaidash’s Family")');
        $mock->expectRollback();

        $scenario($mock);

        $mock->assertExpectationsFulfilled();
    }

    #[Test]
    public function itShouldExpectTransactionUsingCallableSyntax(): void
    {
        $scenario = function (PDO $pdo) {
            $pdo->beginTransaction();
            $pdo->exec('insert into "books" ("title") values ("Kaidash’s Family")');
            $pdo->exec('insert into "books" ("title") values ("Shadows of the Forgotten Ancestors")');
            $pdo->commit();
        };

        $scenario($this->sqlite());

        $mock = new PDOMock();
        $mock->expectTransaction(function () use ($mock) {
            $mock->expect('insert into "books" ("title") values ("Kaidash’s Family")');
            $mock->expect('insert into "books" ("title") values ("Shadows of the Forgotten Ancestors")');
        });

        $scenario($mock);

        $mock->assertExpectationsFulfilled();
    }

    #[Test]
    public function itShouldHandleIgnoreTransactionsMode(): void
    {
        $scenario = function (PDO $pdo) {
            $pdo->beginTransaction();
            $pdo->exec('insert into "books" ("title") values ("Kaidash’s Family")');
            $pdo->commit();
        };

        $scenario($this->sqlite());

        $mock = new PDOMock();
        $mock->ignoreTransactions();
        $mock->expect('insert into "books" ("title") values ("Kaidash’s Family")');

        $scenario($mock);

        $mock->assertExpectationsFulfilled();
    }
}
