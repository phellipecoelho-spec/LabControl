<?php

namespace App\Exceptions;

use Exception;

class InsufficientStockException extends Exception
{
    /**
     * Create a new insufficient stock exception.
     *
     * @param  string  $message
     * @param  int  $code
     */
    public function __construct(
        string $message = 'Saldo insuficiente em estoque.',
        int $code = 422
    ) {
        parent::__construct($message, $code);
    }

    /**
     * Render the exception as an HTTP response.
     */
    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'error' => 'insufficient_stock',
        ], $this->getCode());
    }
}
