<?php

namespace App\Theme\Traits;

use App\Theme\Transformer\TransformerInterface as Transformer;

trait HasContentBlocksTrait
{
    /**
     * The queried entity's processed content blocks from ACF fields.
     *
     * @var ?array<string, mixed>[]
     */
    protected ?array $content_blocks = null;

    /**
     * @return array<string, mixed>[]
     */
    public function get_content_blocks() : array
    {
        return $this->content_blocks ??= $this->load_content_blocks();
    }

    /**
     * @param  array<string, mixed> $block
     * @return ?array<string, mixed>
     */
    public function transform_block(array $block = []) : ?array
    {
        $transformer = $this->resolve_block_transformer($block);
        return $this->transform($block, $transformer);
    }

    /**
     * @param  array<string, mixed> $block
     * @return array<string, mixed>
     */
    public function resolve_block_transformer(array $block) : string
    {
        $layout = $block['acf_fc_layout'] ?? null;

        $namespace = 'App\\Theme\\Transformer\\Content\\';

        if (!empty($layout)) {
            $layout = str_replace('_', '', ucwords($layout, '_'));
            $class = $namespace . $layout;

            if (class_exists($class)) {
                return $class;
            }
        }

        return $namespace . 'ContentBlock';
    }

    /**
     * Transform data.
     *
     * @param  array<string, mixed>                  $data
     * @param  Transformer|class-string<Transformer> $transformer
     * @return ?array<string, mixed>
     */
    abstract public function transform(array $data, $transformer) : ?array;

    /**
     * @return array<string, mixed>[]
     */
    public function load_content_blocks() : array
    {
        $entity = $this->get_queried_entity();
        if (!$entity) {
            return [];
        }

        $key    = 'content_blocks';
        $blocks = $this->get_fields()[$key] ?? [];

        if (!$blocks || !have_rows($key, $entity->wp_object())) {
            return [];
        }

        $content_blocks = [];

        foreach ($blocks as $block_data) {
            the_row();

            $block = $this->transform_block($block_data);
            if ($block) {
                $content_blocks[] = $block;
            }
        }

        return $content_blocks;
    }
}
