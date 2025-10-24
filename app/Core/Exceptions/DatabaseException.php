<?php

namespace App\Core\Exceptions;

use Exception;
use PDOException;

class DatabaseException extends Exception
{
    private ?string $sqlState;

    public function __construct(string $message = '', int $code = 0, ?PDOException $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->sqlState = $previous ? $previous->getCode() : null;
    }

    public function getSqlState(): ?string
    {
        return $this->sqlState;
    }

    public function isConnectionError(): bool
    {
        return in_array($this->sqlState, ['HY000', '2002', '2003', '2006']);
    }
}
