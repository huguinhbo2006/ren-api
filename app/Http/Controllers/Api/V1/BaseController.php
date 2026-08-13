<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;

/**
 * BaseController
 *
 * Controlador base para todos los controladores del API v1 de Rentame.
 * Incluye el trait ApiResponse para respuestas JSON consistentes.
 */
abstract class BaseController extends Controller
{
    use ApiResponse;
}
