<?php

declare(strict_types=1);

namespace FaustVik\Router\Middleware;

use FaustVik\Router\Http\Request;
use FaustVik\Router\Http\Response;
use FaustVik\Router\interfaces\Middleware\MiddlewareInterface;

final class MiddlewareStack
{
    /** @var MiddlewareInterface[] */
    private array $middleware = [];

    /** @var callable */
    private $finalHandler;

    public function __construct(callable $finalHandler)
    {
        $this->finalHandler = $finalHandler;
    }//end __construct()

    public function add(MiddlewareInterface $middleware): self
    {
        $this->middleware[] = $middleware;
        return $this;
    }//end add()

    public function addFromArray(array $middleware): self
    {
        foreach ($middleware as $item) {
            if (is_string($item)) {
                // Если это строка, создаем экземпляр класса
                $item = new $item();
            }

            if ($item instanceof MiddlewareInterface) {
                $this->add($item);
            }
        }
        return $this;
    }//end addFromArray()

    public function execute(Request $request): Response
    {
        $index = 0;

        $next = function (Request $request) use (&$next, &$index): Response {
            if ($index >= count($this->middleware)) {
                // Если все middleware выполнены, выполняем финальный обработчик
                return call_user_func($this->finalHandler, $request);
            }

            $middleware = $this->middleware[$index++];
            return $middleware->handle($request, $next);
        };

        return $next($request);
    }//end execute()

    public function count(): int
    {
        return count($this->middleware);
    }//end count()

    public function isEmpty(): bool
    {
        return empty($this->middleware);
    }//end isEmpty()

    public function clear(): self
    {
        $this->middleware = [];
        return $this;
    }//end clear()
}//end class
