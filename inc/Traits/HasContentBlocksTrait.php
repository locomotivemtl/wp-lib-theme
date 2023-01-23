<?php

namespace App\Theme\Traits;

use App\Theme\Transformer\TransformerInterface as Transformer;

trait HasContentBlocksTrait
{
    /**
     * @var ?array<string, mixed>[]
     */
    protected ?array $content_blocks;

    /**
     * @return array<string, mixed>[]
     */
    public function get_content_blocks() : array
    {
        if (!isset($this->content_blocks)) {
            $this->content_blocks = [];

            $entity = $this->get_queried_entity();
            if ($entity) {
                $key    = 'content_blocks';
                $blocks = $this->get_fields()[$key] ?? [];
                if ($blocks && have_rows($key, $entity->wp_object())) {
                    foreach ($blocks as $block_data) {
                        the_row();

                        $block = $this->transform_block($block_data);
                        if ($block) {
                            $this->content_blocks[] = $block;
                        }
                    }
                }
            }
        }

        return $this->content_blocks;
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
}
