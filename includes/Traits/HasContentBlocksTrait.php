<?php

namespace Locomotive\Theme\Traits;

use Locomotive\Theme\Transformer\Content\ContentBlock;
use Locomotive\Theme\Transformer\TransformerInterface as Transformer;
use Timber\CoreEntityInterface as CoreEntity;

trait HasContentBlocksTrait
{
    /**
     * ACF field selector for the content blocks.
     */
    protected string $content_blocks_acf_selector = 'content_blocks';

    /**
     * PHP namespace for content blocks.
     */
    protected string $content_blocks_namespace = 'App\\Theme\\Transformer\\Content';

    /**
     * PHP fallback class name for content blocks.
     */
    protected string $content_blocks_class = 'ContentBlock';

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

        if (!empty($layout)) {
            $layout = str_replace('_', '', ucwords($layout, '_'));
            $class = $this->content_blocks_namespace . '\\' . $layout;

            if (class_exists($class)) {
                return $class;
            }
        }

        $fallback_class = $this->content_blocks_namespace . '\\' . $this->content_blocks_class;
        if (class_exists($fallback_class)) {
            return $fallback_class;
        }

        return ContentBlock::class;
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

        $selector = $this->content_blocks_acf_selector;
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
