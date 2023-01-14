<?php

namespace App\Theme\Transformer;

abstract class AbstractTransformer
{
    /**
     * Alias of {@see self::__invoke()}.
     *
     * @param  array<string, mixed> $data
     * @return ?array<string, mixed>
     */
    public function transform(array $data) : ?array
    {
        return $this($data);
    }
}
