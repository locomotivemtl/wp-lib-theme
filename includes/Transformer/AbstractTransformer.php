<?php

namespace Locomotive\Theme\Transformer;

abstract class AbstractTransformer implements TransformerInterface
{
    /**
     * Alias of {@see TransformerInterface::transform()}.
     *
     * @param  ?array<string, mixed> $data
     * @return ?array<string, mixed>
     */
    public function __invoke(?array $data) : ?array
    {
        return $this->transform($data);
    }
}
