<?php

namespace Tests\Xala\Elomock;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;
use Xala\Elomock\PDOMock;

class ExecTest extends TestCase
{
    #[Test]
    public function itShouldExecuteQuery(): void
    {
        $pdo = new PDOMock();

        $pdo->expect('select * from "users"');

        $result = $pdo->exec('select * from "users"');

        static::assertSame(0, $result);
    }

    #[Test]
    public function itShouldFailWhenQueryDoesntMatch(): void
    {
        $pdo = new PDOMock();

        $pdo->expect('select * from "users"');

        $this->expectException(ExpectationFailedException::class);

        $pdo->exec('select * from "books"');
    }

    #[Test]
    public function itShouldFailWhenQueryIsNotExecuted(): void
    {
        $pdo = new PDOMock();

        $pdo->expect('select * from "users"');

        $this->expectException(ExpectationFailedException::class);

        $pdo->assertExpectationsFulfilled();
    }
}