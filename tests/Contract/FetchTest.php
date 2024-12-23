<?php

namespace Tests\Xalaida\PDOMock\Contract;

use PDO;
use Tests\Xalaida\PDOMock\TestCase;
use Xalaida\PDOMock\PDOMock;

class FetchTest extends TestCase
{
    /**
     * @test
     * @dataProvider contracts
     * @param PDO $pdo
     * @return void
     */
    public function itShouldNotOverrideDefaultFetchMode($pdo)
    {
        $pdo->setAttribute($pdo::ATTR_STRINGIFY_FETCHES, true);
        $pdo->setAttribute($pdo::ATTR_DEFAULT_FETCH_MODE, $pdo::FETCH_OBJ);

        $statement = $pdo->prepare('select * from "books"');

        $statement->execute();

        static::assertIsArrayType(
            $statement->fetch($pdo::FETCH_ASSOC)
        );

        static::assertIsObjectType(
            $statement->fetch()
        );
    }

    /**
     * @test
     * @dataProvider contracts
     * @param PDO $pdo
     * @return void
     */
    public function itShouldBuildIterator($pdo)
    {
        if (PHP_VERSION_ID < 80000) {
            $this->markTestSkipped('PDOStatement::getIterator() is available only on PHP >= 8.0');
        }

        $pdo->setAttribute($pdo::ATTR_STRINGIFY_FETCHES, true);

        $statement = $pdo->prepare('select * from "books"');

        $statement->setFetchMode($pdo::FETCH_OBJ);

        $result = $statement->execute();

        static::assertTrue($result);

        $iterator = $statement->getIterator();

        static::assertInstanceOf(\Iterator::class, $iterator);

        static::assertEquals(0, $iterator->key());
        static::assertEquals((object) ['id' => '1', 'title' => 'Kaidash’s Family'], $iterator->current());

        $iterator->next();

        static::assertEquals(1, $iterator->key());
        static::assertEquals((object) ['id' => '2', 'title' => 'Shadows of the Forgotten Ancestors'], $iterator->current());

        $iterator->next();

        static::assertNull($iterator->current());
        static::assertFalse($iterator->valid());
    }

    /**
     * @test
     * @dataProvider contracts
     * @param PDO $pdo
     * @return void
     */
    public function itShouldReturnFalseWhenStatementIsNotExecuted($pdo)
    {
        $statement = $pdo->prepare('select * from "books"');

        $row = $statement->fetch($pdo::FETCH_ASSOC);

        static::assertFalse($row);
    }

    /**
     * @test
     * @dataProvider contracts
     * @param PDO $pdo
     * @return void
     */
    public function itShouldCloseCursor($pdo)
    {
        $statement = $pdo->prepare('select * from "books"');

        static::assertTrue($statement->closeCursor());

        $statement->execute();

        $row = $statement->fetch($pdo::FETCH_ASSOC);

        static::assertEquals(['id' => '1', 'title' => 'Kaidash’s Family'], $row);

        static::assertTrue($statement->closeCursor());

        static::assertTrue($statement->closeCursor());

        $row = $statement->fetch($pdo::FETCH_ASSOC);

        static::assertFalse($row);
    }

    /**
     * @test
     * @dataProvider contracts
     * @param PDO $pdo
     * @return void
     */
    public function itShouldFailOnInvalidFetchMode($pdo)
    {
        $this->markTestIncomplete('TODO');

        // @phpstan-ignore-next-line
        $statement = $pdo->prepare('select * from "books"');

        if (PHP_VERSION_ID < 80000) {
            try {
                $statement->setFetchMode(777);

                $this->fail('Expected exception is not thrown');
            } catch (\PDOException $e) {
                static::assertSame('PDOStatement::setFetchMode(): Argument #1 ($mode) must be a bitmask of PDO::FETCH_* constants', $e->getMessage());
            }
        } else {
            try {
                $statement->setFetchMode(777);

                $this->fail('Expected exception is not thrown');
            } catch (\ValueError $e) {
                static::assertSame('PDOStatement::setFetchMode(): Argument #1 ($mode) must be a bitmask of PDO::FETCH_* constants', $e->getMessage());
            }
        }
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

        $pdo->exec('insert into "books" ("title") values ("Kaidash’s Family"), ("Shadows of the Forgotten Ancestors")');

        return $pdo;
    }

    /**
     * @return PDOMock
     */
    protected static function configureMock()
    {
        $pdo = new PDOMock();

        $pdo->expect('select * from "books"')
            ->willFetchRows([
                ['id' => 1, 'title' => 'Kaidash’s Family'],
                ['id' => 2, 'title' => 'Shadows of the Forgotten Ancestors'],
            ]);

        return $pdo;
    }
}
