<?php

namespace Tests\Xala\Elomock;

use PDO;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;
use Xala\Elomock\PDOMock;

/**
 * @todo handle nested transactions
 */
class TransactionTest extends TestCase
{
    #[Test]
    public function itShouldExecuteQueryInTransaction(): void
    {
        $pdo = new PDOMock();

        $pdo->expectBeginTransaction();

        $pdo->expect('insert into "books" ("title") values ("Kaidash’s Family")');

        $pdo->expectCommit();

        static::assertFalse($pdo->inTransaction());

        $pdo->beginTransaction();

        $pdo->exec('insert into "books" ("title") values ("Kaidash’s Family")');

        static::assertTrue($pdo->inTransaction());

        $pdo->commit();

        static::assertFalse($pdo->inTransaction());

        $pdo->assertExpectationsFulfilled();
    }

    #[Test]
    public function itShouldRollbackTransaction(): void
    {
        $pdo = new PDOMock();
        $pdo->expectBeginTransaction();
        $pdo->expect('insert into "books" ("title") values ("Kaidash’s Family")');
        $pdo->expectRollback();

        $pdo->beginTransaction();
        $pdo->exec('insert into "books" ("title") values ("Kaidash’s Family")');
        $pdo->rollBack();

        $pdo->assertExpectationsFulfilled();
    }

    #[Test]
    public function itShouldFailWhenQueryExecutedWithoutTransaction(): void
    {
        $pdo = new PDOMock();

        $pdo->expectBeginTransaction();

        $pdo->expect('insert into "books" ("title") values ("Kaidash’s Family")');

        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('Unexpected query: insert into "books" ("title") values ("Kaidash’s Family")');

        $pdo->exec('insert into "books" ("title") values ("Kaidash’s Family")');
    }

    #[Test]
    public function itShouldExpectTransactionUsingCallableSyntax(): void
    {
        $pdo = new PDOMock();
        $pdo->expectTransaction(function () use ($pdo) {
            $pdo->expect('insert into "books" ("title") values ("Kaidash’s Family")');
            $pdo->expect('insert into "books" ("title") values ("Shadows of the Forgotten Ancestors")');
        });

        $pdo->beginTransaction();
        $pdo->exec('insert into "books" ("title") values ("Kaidash’s Family")');
        $pdo->exec('insert into "books" ("title") values ("Shadows of the Forgotten Ancestors")');
        $pdo->commit();

        $pdo->assertExpectationsFulfilled();
    }

    #[Test]
    public function itShouldFailWhenTransactionalQueryIsNotExecuted(): void
    {
        $pdo = new PDOMock();

        $pdo->expectTransaction(function () use ($pdo) {
            $pdo->expect('insert into "books" ("title") values ("Kaidash’s Family")');
            $pdo->expect('insert into "books" ("title") values ("Shadows of the Forgotten Ancestors")');
        });

        $pdo->beginTransaction();
        $pdo->exec('insert into "books" ("title") values ("Kaidash’s Family")');

        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('Unexpected PDO::commit()');

        $pdo->commit();
    }

    #[Test]
    public function itShouldHandleIgnoreTransactionsMode(): void
    {
        $pdo = new PDOMock();
        $pdo->ignoreTransactions();
        $pdo->expect('insert into "books" ("title") values ("Kaidash’s Family")');

        $pdo->beginTransaction();
        $pdo->exec('insert into "books" ("title") values ("Kaidash’s Family")');
        $pdo->commit();

        $pdo->assertExpectationsFulfilled();
    }
}
