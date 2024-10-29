<?php

namespace Tests\Xala\Elomock\Contract;

use PDO;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Xala\Elomock\TestCase;
use Xala\Elomock\PDOMock;

class LastInsertIdFreshTest extends TestCase
{
    #[Test]
    #[DataProvider('contracts')]
    public function itShouldReturnZeroAsLastInsertId(PDO $pdo): void
    {
        static::assertSame('0', $pdo->lastInsertId());
        static::assertSame('0', $pdo->lastInsertId());
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
        return new PDO('sqlite::memory:');
    }

    protected static function configureMock(): PDOMock
    {
        return new PDOMock();
    }
}
