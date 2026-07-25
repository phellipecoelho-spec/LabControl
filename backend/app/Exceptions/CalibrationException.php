<?php

namespace App\Exceptions;

use Exception;

class CalibrationException extends Exception
{
    /**
     * Create a new calibration exception.
     *
     * @param  string  $message
     * @param  int  $code
     */
    public function __construct(
        string $message = 'Operação de calibração inválida.',
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
            'error' => 'calibration_error',
        ], $this->getCode());
    }
}
