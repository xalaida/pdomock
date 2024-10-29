<?php

namespace Xala\Elomock;

use PDOException;

class PDOExceptionMock extends PDOException
{
    /**
     * @param string $message
     * @param string $code
     * @param string $driverMessage
     * @param int $driverCode
     * @return static
     */
    public static function fromErrorInfo($message, $code, $driverMessage, $driverCode): static
    {
        $exception = new self($message);

        $exception->code = $code;

        $exception->errorInfo = [$code, $driverCode, $driverMessage];

        return $exception;
    }
}
