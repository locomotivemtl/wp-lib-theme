<?php

namespace App\Theme\Traits;

use App\Theme\Transformer\TransformerInterface as Transformer;
use Timber\CoreEntityInterface as CoreEntity;

trait HasContentBlocksTrait
{
    /**
     * ACF field selector for the content blocks.
     */
    protected string $content_blocks_selector = 'content_blocks';

    /**
     * The queried entity's processed content blocks from ACF fields.
     *
     * @var ?array<string, mixed>[]
     */
    protected ?array $content_blocks = null;

    /**
     * @return ?CoreEntity
     */
    abstract public function get_queried_entity() : ?CoreEntity;

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
     * @return array<string, mixed>[]
     */
    protected function load_content_blocks() : array
    {
        $entity = $this->get_queried_entity();
        if (!$entity) {
            return [];
        }

        $selector = $this->content_blocks_selector;
        $context  = $entity->wp_object();

        if (!have_rows($selector, $context)) {
            return [];
        }

        $blocks = [];

        while (have_rows($selector, $context)) {
            $row = the_row(true);

            $block = $this->transform_block($row);
            if ($block) {
                $blocks[] = $block;
            }
        }

        return $blocks;
    }
}
