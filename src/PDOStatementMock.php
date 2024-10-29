<?php

namespace Xala\Elomock;

use ArrayIterator;
use InvalidArgumentException;
use Iterator;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use ValueError;

class PDOStatementMock extends PDOStatement
{
    /**
     * @var PDOMock
     */
    protected $pdo;

    /**
     * @var Expectation
     */
    protected $expectation;

    public $query;

    /**
     * @var array
     */
    public $params = [];

    /**
     * @var array
     */
    public $columns = [];

    /**
     * @var int
     */
    protected $fetchMode;

    /**
     * @var int
     */
    protected $cursor = 0;

    /**
     * @var array
     */
    protected $errorInfo;

    /**
     * @var string|null
     */
    protected $errorCode;

    /**
     * @var bool
     */
    public $executed;

    /**
     * @param PDOMock $pdo
     * @param Expectation $expectation
     * @param string $query
     */
    public function __construct($pdo, $expectation, $query)
    {
        $this->pdo = $pdo;
        $this->expectation = $expectation;
        $this->executed = false;
        $this->errorInfo = ['', null, null];
        $this->errorCode = null;
        $this->query = $query;

        // This property does not work on PHP v8.0 because it is impossible to override internally readonly property.
        if (PHP_VERSION_ID > 81000) {
            $this->queryString = $query;
        }
    }

    /**
     * @param int $mode
     * @param $className
     * @param ...$params
     * @return void
     */
    public function setFetchMode($mode, $className = null, ...$params)
    {
        $this->fetchMode = $mode;
    }

    /**
     * @param $param
     * @param $value
     * @param $type
     * @return bool
     */
    public function bindValue($param, $value, $type = PDO::PARAM_STR)
    {
        $this->params[$param] = [
            'value' => $value,
            'type' => $type,
        ];

        return true;
    }

    /**
     * @param $param
     * @param $var
     * @param $type
     * @param $maxLength
     * @param $driverOptions
     * @return bool
     */
    public function bindParam($param, &$var, $type = PDO::PARAM_STR, $maxLength = 0, $driverOptions = null)
    {
        $this->params[$param] = [
            'value' => $var,
            'type' => $type,
        ];

        return true;
    }

    /**
     * @param $column
     * @param $var
     * @param $type
     * @param $maxLength
     * @param $driverOptions
     * @return bool
     */
    public function bindColumn($column, &$var, $type = PDO::PARAM_STR, $maxLength = 0, $driverOptions = null)
    {
        $this->columns[$column] = [
            'value' => &$var,
            'type' => $type,
        ];

        return true;
    }

    /**
     * @param array|null $params
     * @return bool
     */
    public function execute($params = null)
    {
        $this->expectation->statement = $this;

        if (! is_null($params)) {
            $boundParams = [];

            foreach ($params as $key => $value) {
                $param = is_int($key)
                    ? $key + 1
                    : $key;

                $boundParams[$param] = [
                    'value' => $value,
                    'type' => PDO::PARAM_STR,
                ];
            }
        } else {
            $boundParams = $this->params;
        }

        if (! is_null($this->expectation->prepared)) {
            TestCase::assertTrue($this->expectation->prepared, 'Statement is not prepared');
        }

        TestCase::assertSame($this->expectation->query, $this->query, 'Query does not match');

        if (! is_null($this->expectation->params)) {
            if (is_callable($this->expectation->params)) {
                $result = call_user_func($this->expectation->params, $boundParams);

                TestCase::assertNotFalse($result, 'Params do not match');
            } else {
                TestCase::assertEquals($this->expectation->params, $boundParams, 'Params do not match');
            }
        }

        $this->executed = true;

        if ($this->expectation->exceptionOnExecute && $this->expectation->exceptionOnExecute->errorInfo) {
            $this->errorInfo = $this->expectation->exceptionOnExecute->errorInfo;
            $this->errorCode = $this->expectation->exceptionOnExecute->errorInfo[0];
        } else {
            $this->errorInfo = ['00000', null, null];
            $this->errorCode = $this->errorInfo[0];
        }

        if ($this->expectation->exceptionOnExecute) {
            if ($this->pdo->getAttribute(PDO::ATTR_ERRMODE) === PDO::ERRMODE_SILENT) {
                return false;
            }

            if ($this->pdo->getAttribute(PDO::ATTR_ERRMODE) === PDO::ERRMODE_WARNING) {
                trigger_error('PDOStatement::execute(): ' . $this->expectation->exceptionOnExecute->getMessage(), E_USER_WARNING);

                return false;
            }

            if ($this->pdo->getAttribute(PDO::ATTR_ERRMODE) === PDO::ERRMODE_EXCEPTION) {
                throw $this->expectation->exceptionOnExecute;
            }
        }

        if (! is_null($this->expectation->insertId)) {
            $this->pdo->lastInsertId = $this->expectation->insertId;
        }

        return true;
    }

    /**
     * @return int
     */
    public function rowCount(): int
    {
        if (! $this->executed) {
            return 0;
        }

        return $this->expectation->rowCount;
    }

    /**
     * @return string|null
     */
    public function errorCode()
    {
        return $this->errorCode;
    }

    /**
     * @return array
     */
    public function errorInfo(): array
    {
        return $this->errorInfo;
    }

    /**
     * @return Iterator
     */
    public function getIterator(): Iterator
    {
        return new ArrayIterator($this->fetchAll());
    }

    /**
     * @param int $mode
     * @param $cursorOrientation
     * @param $cursorOffset
     * @return array|bool|mixed|object
     */
    public function fetch($mode = PDO::FETCH_DEFAULT, $cursorOrientation = PDO::FETCH_ORI_NEXT, $cursorOffset = 0)
    {
        if ($this->executed && isset($this->expectation->resultSet->rows[$this->cursor])) {
            $row = $this->applyFetchMode($this->expectation->resultSet->cols, $this->expectation->resultSet->rows[$this->cursor], $mode);

            $this->cursor++;

            return $row;
        }

        return false;
    }

    /**
     * @param int $mode
     * @param ...$args
     * @return array|array[]|false|object[]|true[]
     */
    public function fetchAll($mode = PDO::FETCH_DEFAULT, ...$args)
    {
        if ($mode === PDO::FETCH_LAZY) {
            throw new ValueError('PDOStatement::fetchAll(): Argument #1 ($mode) cannot be PDO::FETCH_LAZY in PDOStatement::fetchAll()');
        }

        if (! $this->executed) {
            return [];
        }

        return array_map(function ($row) use ($mode) {
            return $this->applyFetchMode($this->expectation->resultSet->cols, $row, $mode);
        }, $this->expectation->resultSet->rows);
    }

    /**
     * @param array $cols
     * @param array $row
     * @param int $mode
     * @return object|array|true
     */
    protected function applyFetchMode($cols, $row, $mode)
    {
        if ($mode === PDO::FETCH_DEFAULT) {
            $mode = $this->fetchMode;
        }

        if ($mode !== PDO::FETCH_NUM && empty($cols)) {
            throw new RuntimeException('Specify columns to result set');
        }

        switch ($mode) {
            case PDO::FETCH_NUM:
                return $row;

            case PDO::FETCH_ASSOC:
                return array_combine($cols, $row);

            case PDO::FETCH_OBJ:
                return (object) array_combine($cols, $row);

            case PDO::FETCH_BOTH:
                return array_merge($row, array_combine($cols, $row));

            case PDO::FETCH_BOUND:
                return $this->applyFetchModeBound($cols, $row);

            default:
                throw new InvalidArgumentException("Unsupported fetch mode: " . $mode);
        }
    }

    /**
     * @param array $columns
     * @param array $row
     * @return bool
     */
    protected function applyFetchModeBound($columns, $row)
    {
        foreach ($this->columns as $column => $params) {
            if (is_int($column)) {
                $index = $column - 1;

                if (! isset($row[$index])) {
                    throw new ValueError('Invalid column index');
                }
            } else {
                $index = array_search($column, $columns, true);
            }

            if ($index === false) {
                $params['value'] = null;
            } else {
                $params['value'] = $this->applyParamType($params['type'], $row[$index]);
            }
        }

        return true;
    }

    /**
     * @param int $type
     * @param mixed $value
     * @return mixed
     */
    protected function applyParamType($type, $value)
    {
        switch ($type) {
            case PDO::PARAM_NULL:
                return null;

            case PDO::PARAM_INT:
                return (int) $value;

            case PDO::PARAM_STR:
                return (string) $value;

            case PDO::PARAM_BOOL:
                return (bool) $value;

            default:
                throw new RuntimeException('Unsupported column type: ' . $type);
        }
    }
}
