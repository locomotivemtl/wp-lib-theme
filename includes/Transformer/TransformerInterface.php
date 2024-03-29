<?php

namespace Locomotive\Theme\Transformer;

interface TransformerInterface
{
    /**
     * @param  array<string, mixed> $data
     * @return ?array<string, mixed>
     */
    public function transform(array $data) : ?array;
}
