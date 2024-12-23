<?php

namespace Xalaida\PDOMock;

interface ExpectationValidatorInterface
{
    /**
     * @param QueryExpectation $expectation
     * @param string $reality
     * @return void
     */
    public function assertQueryMatch($expectation, $reality);

    /**
     * @param QueryExpectation $expectation
     * @param array<int|string, mixed> $params
     * @param array<int|string, int> $types
     * @return void
     */
    public function assertParamsMatch($expectation, $params, $types);

    /**
     * @param bool $reality
     * @return void
     */
    public function assertIsPrepared($reality);

    /**
     * @param bool $reality
     * @return void
     */
    public function assertIsNotPrepared($reality);

    /**
     * @param FunctionExpectation $expectation
     * @param string $reality
     * @return void
     */
    public function assertFunctionMatch($expectation, $reality);
}
