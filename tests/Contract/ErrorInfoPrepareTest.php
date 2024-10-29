<?php

namespace Tests\Xala\Elomock\Contract;

use PDO;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Xala\Elomock\TestCase;
use Xala\Elomock\PDOMock;

class ErrorInfoPrepareTest extends TestCase
{
    #[Test]
    #[DataProvider('contracts')]
    public function itShouldDisplayErrorInformationForSuccessfullyPreparedStatement(PDO $pdo): void
    {
        $statement = $pdo->prepare('insert into "books" ("id", "title") values (1, "Stolen Happiness by Ivan Franko")');

        static::assertNull($statement->errorCode());
        static::assertSame(['', null, null], $statement->errorInfo());

        static::assertSame('00000', $pdo->errorCode());
        static::assertSame(['00000', null, null], $pdo->errorInfo());
    }

    #[Test]
    #[DataProvider('contracts')]
    public function itShouldDisplayErrorInformationForSuccessfullyExecutedPreparedStatement(PDO $pdo): void
    {
        $statement = $pdo->prepare('insert into "books" ("id", "title") values (1, "Stolen Happiness by Ivan Franko")');

        $statement->execute();

        static::assertSame('00000', $statement->errorCode());
        static::assertSame(['00000', null, null], $statement->errorInfo());

        static::assertSame('00000', $pdo->errorCode());
        static::assertSame(['00000', null, null], $pdo->errorInfo());
    }

    public static function contracts(): array
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

    protected static function configureSqlite(): PDO
    {
        $pdo = new PDO('sqlite::memory:');

        $pdo->exec('create table "books" ("id" integer primary key autoincrement not null, "title" varchar not null)');

        return $pdo;
    }

    protected static function configureMock(): PDOMock
    {
        $pdo = new PDOMock();

        $pdo->expect('insert into "books" ("id", "title") values (1, "Stolen Happiness by Ivan Franko")');

        return $pdo;
    }
}
