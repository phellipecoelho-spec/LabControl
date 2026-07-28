<?php

namespace App\Http\Controllers\Api\V1;

/**
 * @OA\Info(
 *     title="LabControl API",
 *     version="1.0.0",
 *     description="API de Gestão Laboracional — equipamentos, calibrações, aferições, empréstimos e manutenções"
 * )
 *
 * @OA\Server(url="http://localhost/api", description="Development")
 * @OA\Server(url="https://{host}/api", description="Production")
 *
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="http",
 *     scheme="session",
 *     description="Sanctum SPA authentication via session cookie"
 * )
 */
class DocsController
{
    //
}