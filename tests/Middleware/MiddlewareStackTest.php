<?php

declare(strict_types=1);

namespace FaustVik\Router\Tests\Middleware;

use FaustVik\Router\Http\Request;
use FaustVik\Router\Http\Response;
use FaustVik\Router\interfaces\Middleware\MiddlewareInterface;
use FaustVik\Router\Middleware\MiddlewareStack;
use PHPUnit\Framework\TestCase;

/**
 * Тесты для класса MiddlewareStack
 */
final class MiddlewareStackTest extends TestCase
{
    private function createTestMiddleware(string $name): MiddlewareInterface
    {
        return new class($name) implements MiddlewareInterface {
            public function __construct(private string $name)
            {
            }

            public function handle(Request $request, callable $next): Response
            {
                $request = $request->withAttribute($this->name, true);
                return $next($request);
            }

            public function getName(): string
            {
                return $this->name;
            }
        };
    }

    public function testConstructor(): void
    {
        $finalHandler = fn(Request $request) => Response::json(['ok' => true]);
        $stack = new MiddlewareStack($finalHandler);

        $this->assertTrue($stack->isEmpty());
        $this->assertSame(0, $stack->count());
    }

    public function testAdd(): void
    {
        $stack = new MiddlewareStack(fn($r) => new Response());
        $middleware = $this->createTestMiddleware('test');

        $result = $stack->add($middleware);

        $this->assertSame($stack, $result);
        $this->assertSame(1, $stack->count());
        $this->assertFalse($stack->isEmpty());
    }

    public function testPrepend(): void
    {
        $stack = new MiddlewareStack(fn($r) => new Response());

        $middleware1 = $this->createTestMiddleware('first');
        $middleware2 = $this->createTestMiddleware('second');

        $stack->add($middleware1);
        $stack->prepend($middleware2);

        $this->assertSame(2, $stack->count());
        $this->assertSame($middleware2, $stack->getAt(0));
        $this->assertSame($middleware1, $stack->getAt(1));
    }

    public function testAddFromArray(): void
    {
        $stack = new MiddlewareStack(fn($r) => new Response());
        $middleware1 = $this->createTestMiddleware('test1');
        $middleware2 = $this->createTestMiddleware('test2');

        $stack->addFromArray([$middleware1, $middleware2]);

        $this->assertSame(2, $stack->count());
    }

    public function testRemove(): void
    {
        $stack = new MiddlewareStack(fn($r) => new Response());
        $middleware1 = $this->createTestMiddleware('test1');
        $middleware2 = $this->createTestMiddleware('test2');

        $stack->add($middleware1)->add($middleware2);
        $this->assertSame(2, $stack->count());

        $stack->remove(0);
        $this->assertSame(1, $stack->count());
        $this->assertSame($middleware2, $stack->getAt(0));
    }

    public function testRemoveInvalidIndex(): void
    {
        $stack = new MiddlewareStack(fn($r) => new Response());
        $middleware = $this->createTestMiddleware('test');

        $stack->add($middleware);
        $stack->remove(10); // Не должно вызывать ошибку

        $this->assertSame(1, $stack->count());
    }

    public function testRemoveByClass(): void
    {
        $stack = new MiddlewareStack(fn($r) => new Response());
        $middleware1 = $this->createTestMiddleware('test1');
        $middleware2 = $this->createTestMiddleware('test2');

        $stack->add($middleware1)->add($middleware2);

        $className = get_class($middleware1);
        $stack->removeByClass($className);

        $this->assertSame(0, $stack->count());
    }

    public function testExecute(): void
    {
        $finalResponse = Response::json(['final' => true]);
        $stack = new MiddlewareStack(fn(Request $request) => $finalResponse);

        $middleware1 = $this->createTestMiddleware('mw1');
        $middleware2 = $this->createTestMiddleware('mw2');

        $stack->add($middleware1)->add($middleware2);

        $request = new Request('GET', '/test');
        $response = $stack->execute($request);

        $this->assertSame($finalResponse, $response);
    }

    public function testExecutePassesModifiedRequest(): void
    {
        $stack = new MiddlewareStack(function (Request $request) {
            // Проверяем что атрибуты были добавлены middleware
            $this->assertTrue($request->getAttribute('mw1'));
            $this->assertTrue($request->getAttribute('mw2'));
            return Response::json(['ok' => true]);
        });

        $middleware1 = $this->createTestMiddleware('mw1');
        $middleware2 = $this->createTestMiddleware('mw2');

        $stack->add($middleware1)->add($middleware2);

        $request = new Request('GET', '/test');
        $stack->execute($request);
    }

    public function testExecuteWithEmptyStack(): void
    {
        $finalResponse = Response::json(['empty' => true]);
        $stack = new MiddlewareStack(fn(Request $request) => $finalResponse);

        $request = new Request('GET', '/test');
        $response = $stack->execute($request);

        $this->assertSame($finalResponse, $response);
    }

    public function testClear(): void
    {
        $stack = new MiddlewareStack(fn($r) => new Response());
        $middleware = $this->createTestMiddleware('test');

        $stack->add($middleware);
        $this->assertSame(1, $stack->count());

        $result = $stack->clear();

        $this->assertSame($stack, $result);
        $this->assertSame(0, $stack->count());
        $this->assertTrue($stack->isEmpty());
    }

    public function testGetMiddleware(): void
    {
        $stack = new MiddlewareStack(fn($r) => new Response());
        $middleware1 = $this->createTestMiddleware('test1');
        $middleware2 = $this->createTestMiddleware('test2');

        $stack->add($middleware1)->add($middleware2);

        $allMiddleware = $stack->getMiddleware();

        $this->assertIsArray($allMiddleware);
        $this->assertCount(2, $allMiddleware);
        $this->assertSame($middleware1, $allMiddleware[0]);
        $this->assertSame($middleware2, $allMiddleware[1]);
    }

    public function testGetAt(): void
    {
        $stack = new MiddlewareStack(fn($r) => new Response());
        $middleware = $this->createTestMiddleware('test');

        $stack->add($middleware);

        $this->assertSame($middleware, $stack->getAt(0));
        $this->assertNull($stack->getAt(1));
        $this->assertNull($stack->getAt(-1));
    }

    public function testHas(): void
    {
        $stack = new MiddlewareStack(fn($r) => new Response());
        $middleware = $this->createTestMiddleware('test');

        $this->assertFalse($stack->has(get_class($middleware)));

        $stack->add($middleware);

        $this->assertTrue($stack->has(get_class($middleware)));
    }

    public function testFindByClass(): void
    {
        $stack = new MiddlewareStack(fn($r) => new Response());
        $middleware1 = $this->createTestMiddleware('test1');
        $middleware2 = $this->createTestMiddleware('test2');

        $stack->add($middleware1)->add($middleware2);

        $found = $stack->findByClass(get_class($middleware1));

        $this->assertSame($middleware1, $found);
    }

    public function testFindByClassReturnsNull(): void
    {
        $stack = new MiddlewareStack(fn($r) => new Response());
        $middleware = $this->createTestMiddleware('test');

        $found = $stack->findByClass(get_class($middleware));

        $this->assertNull($found);
    }

    public function testMiddlewareCanStopChain(): void
    {
        $stopResponse = Response::json(['stopped' => true], 403);

        $stoppingMiddleware = new class($stopResponse) implements MiddlewareInterface {
            public function __construct(private Response $response)
            {
            }

            public function handle(Request $request, callable $next): Response
            {
                // Не вызываем $next, останавливаем цепочку
                return $this->response;
            }
        };

        $stack = new MiddlewareStack(function () {
            // Это не должно вызваться
            $this->fail('Final handler should not be called');
            return new Response();
        });

        $stack->add($stoppingMiddleware);

        $request = new Request('GET', '/test');
        $response = $stack->execute($request);

        $this->assertSame($stopResponse, $response);
    }

    public function testFluentInterface(): void
    {
        $stack = new MiddlewareStack(fn($r) => new Response());
        $middleware1 = $this->createTestMiddleware('test1');
        $middleware2 = $this->createTestMiddleware('test2');
        $middleware3 = $this->createTestMiddleware('test3');

        $result = $stack
            ->add($middleware1)
            ->add($middleware2)
            ->remove(1)
            ->prepend($middleware3)
            ->clear()
            ->add($middleware1);

        $this->assertSame($stack, $result);
        $this->assertSame(1, $stack->count());
    }
}

