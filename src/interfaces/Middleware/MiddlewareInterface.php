<?php

declare(strict_types=1);

namespace FaustVik\Router\interfaces\Middleware;

use FaustVik\Router\Http\Request;
use FaustVik\Router\Http\Response;

interface MiddlewareInterface
{
    /**
     * Handle an incoming request
     *
     * @param Request $request
     * @param callable $next
     * @return Response
     */
    public function handle(Request $request, callable $next): Response;
}
