<?php
use PHPUnit\Framework\TestCase;
use Tests\Tnapf\Router\MissingDescriptionHttpException;
use Tests\Tnapf\Router\MissingPhraseHttpException;
use Tnapf\Router\Exceptions\HttpInternalServerError;

class HttpExceptionTests extends TestCase {
    public function testMissingPhrase(): void
    {
        $this->expectException(RuntimeException::class);

        MissingPhraseHttpException::buildJsonResponse();
    }

    public function testMissingDescription(): void
    {
        $this->expectException(RuntimeException::class);

        MissingDescriptionHttpException::buildHtmlResponse();
    }

    public function testHtmlResponse(): void
    {
        $response = HttpInternalServerError::buildHtmlResponse();

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals("Internal Server Error", $response->getReasonPhrase());
        $this->assertEquals("text/html; charset=UTF-8", $response->getHeaderLine("Content-Type"));
    }
}
