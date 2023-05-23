<?php

namespace Tests\Tnapf\Router;

use HttpSoft\Message\ServerRequest;
use HttpSoft\Response\TextResponse;
use LogicException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Tnapf\Router\Handlers\ClosureRequestHandler;
use Tnapf\Router\Routing\RouteRunner;

class RouterRunnerTest extends TestCase
{
    public function testThrowingWhenRunningTwice()
    {
        $runner = new RouteRunner();
        $request = new ServerRequest();

        $runner->appendControllersToRun(
            ClosureRequestHandler::new(static function (ServerRequestInterface $request) use ($runner) {
                $runner->run($request);
                return new TextResponse("Hello World");
            })
        );

        $this->expectException(LogicException::class);

        $runner->run($request);
    }

    public function testCallingNextWhenNoMoreAppendedControllers()
    {
        $runner = new RouteRunner();
        $request = new ServerRequest();

        $runner->appendControllersToRun(
            ClosureRequestHandler::new(static function (
                ServerRequestInterface $request,
                ResponseInterface $response,
                RouteRunner $runner
            ) {
                return $runner->next($request, $response);
            })
        );

        $response = $runner->run($request);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testSettingAndGettingParameters()
    {
        $runner = new RouteRunner();

        $runner->args->test = "Hello World";

        $this->assertSame("Hello World", $runner->args->test);
        $this->assertSame("Hello World", $runner->getParameter("test"));
    }
}
