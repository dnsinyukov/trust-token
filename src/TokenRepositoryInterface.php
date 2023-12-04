<?php

namespace Coderden\TrustToken;

interface TokenRepositoryInterface
{
    public function createNewToken();
    public function decode(string $token);
    public function encode(string $token);
}
