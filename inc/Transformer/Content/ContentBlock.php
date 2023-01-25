<?php

namespace App\Theme\Transformer\Content;

use App\Theme\Transformer\AbstractTransformer;

/**
 * Transformer: Content Block
 */
class ContentBlock extends AbstractTransformer
{
    /**
     * @param  array<string, mixed> $data The ACF content block data.
     * @return ?array<string, mixed>
     */
    public function transform(array $data) : ?array
    {
        return $this->wrap($data);
    }

    /**
     * @param  ?string $layout The ACF layout name.
     * @return string
     */
    protected function get_template_path(?string $layout = null) : string
    {
        $path = get_stylesheet_directory() . '/views/blocks/block';

        if ($layout) {
            $path .= '-' . $layout;
        }

        return $path . '.twig';
    }

    /**
     * @param  array<string, mixed> $data   The ACF content block data.
     * @param  ?string              $layout The ACF layout name.
     * @return array<string, mixed>
     */
    public function wrap(array $data, ?string $layout = null) : array
    {
        $layout ??= ($data['acf_fc_layout'] ?? null);

        if ($layout) {
            $layout = str_replace('_', '-', $layout);
        }

        return [
            'layout'   => $layout,
            'template' => $this->get_template_path($layout),
            'data'     => $data,
        ];
    }
}
