<?php

namespace App\Exceptions;

use Exception;

class LoanException extends Exception
{
    /**
     * Create a new loan exception.
     *
     * @param  string  $message
     * @param  int  $code
     */
    public function __construct(
        string $message = 'Operação de empréstimo inválida.',
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
            'error' => 'loan_error',
        ], $this->getCode());
    }
}
