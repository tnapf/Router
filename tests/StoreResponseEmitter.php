<?php

namespace Tests\Tnapf\Router;

use HttpSoft\Emitter\EmitterInterface;
use Psr\Http\Message\ResponseInterface;

class StoreResponseEmitter implements EmitterInterface {
    protected ResponseInterface $response;

    public function emit(ResponseInterface $response, bool $withoutBody = false): void
    {
        $this->response = $response;
    }

    public function getResponse(): ?ResponseInterface
    {
        return $this->response ?? null;
    }
}