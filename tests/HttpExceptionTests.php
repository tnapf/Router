<?php

namespace Tests\Tnapf\Router;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Tnapf\Router\Exceptions\HttpInternalServerError;
use Tnapf\Router\Exceptions\HttpException;
use RuntimeException;
use HttpSoft\Message\ServerRequest;

class HttpExceptionTests extends TestCase {
    public ServerRequestInterface $request;

    public function getMissingDescriptionHttpException(): HttpException
    {
        $this->request ??= new ServerRequest([], [], [], [], [], "GET", "/");

        return new class($this->request) extends HttpException
        {
            public const CODE = 500;
            public const PHRASE = "Internal Server Error";
            public const DESCRIPTION = "";
            public const HREF = "https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/502";
        };
    }

    public function getMissingPhraseHttpException(): HttpException
    {
        $this->request ??= new ServerRequest([], [], [], [], [], "GET", "/");

        return new class($this->request) extends HttpException
        {
            public const CODE = 0;
            public const PHRASE = "";
            public const DESCRIPTION =
                "Indicates that the server encountered an unexpected condition that prevented it from fulfilling the request.";
            public const HREF = "https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/502";
        };
    }

    public function testMissingPhrase(): void
    {
        $this->expectException(RuntimeException::class);

        $this->getMissingPhraseHttpException()::buildJsonResponse();
    }

    public function testMissingDescription(): void
    {
        $this->expectException(RuntimeException::class);

        $this->getMissingDescriptionHttpException()::buildHtmlResponse();
    }

    public function testHtmlResponse(): void
    {
        $response = HttpInternalServerError::buildHtmlResponse();

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals("Internal Server Error", $response->getReasonPhrase());
        $this->assertEquals("text/html; charset=UTF-8", $response->getHeaderLine("Content-Type"));
    }
}
