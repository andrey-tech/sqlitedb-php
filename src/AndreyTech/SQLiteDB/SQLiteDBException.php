<?php

/**
 * @author    andrey-tech
 * @copyright 2019-2023 andrey-tech
 * @link      https://github.com/andrey-tech/
 * @license   MIT
 * @version   3.0.0
 */

declare(strict_types=1);

namespace AndreyTech\SQLiteDB;

use RuntimeException;
use Throwable;

final class SQLiteDBException extends RuntimeException
{
    /**
     * @param int|mixed $code
     */
    public function __construct(string $message = '', $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, (int) $code, $previous);
    }
}
