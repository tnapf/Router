<?php

namespace Tnapf\Router\Exceptions;

use Exception;
use HttpSoft\Response\EmptyResponse;
use HttpSoft\Response\HtmlResponse;
use HttpSoft\Response\JsonResponse;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

abstract class HttpException extends Exception
{
    public const CODE = 500;
    public const DESCRIPTION = "";
    public const PHRASE = "";
    public const HREF = "";

    public function __construct(public readonly ServerRequestInterface $request)
    {
        parent::__construct(static::DESCRIPTION . " " . static::HREF, static::CODE);
    }

    public static function buildEmptyResponse(): EmptyResponse
    {
        return new EmptyResponse(static::CODE);
    }

    public static function buildHtmlResponse(): HtmlResponse
    {
        $code = static::CODE;
        $description = static::DESCRIPTION;
        $phrase = static::PHRASE;
        $href = static::HREF;

        if (empty($phrase)) {
            throw new RuntimeException("Phrase constant is not defined.");
        }

        if (!strlen($description)) {
            throw new RuntimeException("Description constant defined.");
        }

        $title = "{$code} - {$phrase}";

        ob_start();
        include_once __DIR__ . "/HttpExceptionHtmlResponse.php";
        $html = ob_get_clean();

        return new HtmlResponse($html, static::CODE);
    }

    public static function buildJsonResponse(): JsonResponse
    {
        $code = static::CODE;
        $description = static::DESCRIPTION;
        $phrase = static::PHRASE;
        $href = static::HREF;

        if (!strlen($phrase)) {
            throw new RuntimeException("Phrase constant is not defined.");
        }

        if (!strlen($description)) {
            throw new RuntimeException("Description constant defined.");
        }

        $json = compact("description", "phrase");

        if ($code) {
            $json["code"] = $code;
        }

        if (strlen($href)) {
            $json["href"] = $href;
        }

        return new JsonResponse($json, static::CODE);
    }
}
