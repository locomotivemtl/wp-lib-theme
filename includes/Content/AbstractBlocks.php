<?php

namespace Locomotive\Theme\Content;

use Locomotive\Theme\Content\BuildingBlocks;
use Closure;
use Generator;
use InvalidArgumentException;
use Traversable;

/**
 * Base Page Builder Blocks
 *
 * Blockset:
 * ```json
 * {
 *     "block_type": (string) -- The ACF layout name (`acf_fc_layout`).
 *     "block_view": (string) -- The template file (`web/partials/blocks/<view>.blade.php`).
 * }
 * ```
 *
 * @event filter:locomotive/theme/content/blocks/pre_parse_block
 * @event filter:locomotive/theme/content/blocks/pre_parse_block/layout={$layout} {
 *     Filters whether to preempt generating the content block from a flexible content layout.
 *
 *     Passing FALSE to the filter will effectively short-circuit the
 *     block-generation process, skipping that row instead.
 *
 *     Passing a non-NULL value to the filter will effectively short-circuit the
 *     block-generation process, returning that value instead.
 *
 *     Passing NULL to the filter will proceed with block-generation process.
 *
 *     @param  mixed          $block   Short circuit return value.
 *         Either FALSE to skip the ACF row,
 *         NULL to proceed with ACF row,
 *         or the value to replace the block with.
 *     @param  array          $row     The ACF row of a flexible content field.
 *     @param  string         $layout  The ACF layout of the $row.
 *     @param  BuildingBlocks $builder The content builder.
 * }
 *
 * @event filter:locomotive/theme/content/blocks/parse_block
 * @event filter:locomotive/theme/content/blocks/parse_block/layout={$layout} {
 *     Filters the content block from a flexible content layout.
 *
 *     @param  object|null    $block   The content block composed from the $row.
 *     @param  array          $row     The ACF row of a flexible content field.
 *     @param  string         $layout  The ACF layout of the $row.
 *     @param  BuildingBlocks $builder The content builder.
 * }
 *
 */
abstract class AbstractBlocks implements BuildingBlocks
{
    /**
     * The source from which to generate items.
     *
     * @var callable|iterable|object
     */
    public $source;

    /**
     * Create a new block collection instance.
     *
     * @param  mixed $source  The flexible content field name or an array of layouts.
     * @param  mixed $context The field's parent object if $source is a field name.
     * @return void
     */
    public function __construct( $source = null, $context = null )
    {
        if ( $source instanceof Closure ) {
            $this->source = $source;
        } elseif ( is_string( $source ) ) {
            $this->source = (object) [
                'selector' => $source,
                'context'  => $context,
            ];
        } elseif ( is_iterable( $source ) ) {
            $this->source = $source;
        }
    }

    /**
     * Parse the content blocks.
     *
     * @param  iterable $rows The content blocks to prepare.
     * @return object[]|Generator Expected to be an array of object.
     */
    public function parse_blocks( iterable $rows ) : Generator
    {
        if ( empty( $rows ) ) {
            return;
        }

        foreach ( $rows as $row ) {
            if ( empty( $row['acf_fc_layout'] ) ) {
                continue;
            }

            $block = $this->parse_block( $row, $row['acf_fc_layout'] );

            if ( $block instanceof Generator ) {
                yield from $block;
            } elseif ( ! empty( $block ) ) {
                yield $block;
            }
        }
    }

    /**
     * Parse the content blocks from the given field name.
     *
     * @param  string $selector The flexible content field name.
     * @param  mixed  $context  The field's parent object.
     * @return object[]|Generator Expected to be an array of object.
     */
    public function parse_field( string $selector, $context = null ) : Generator
    {
        if ( ! have_rows( $selector, $context ) ) {
            return;
        }

        while ( have_rows( $selector, $context ) ) {
            $row   = the_row( true );
            $block = $this->parse_block( $row, get_row_layout() );

            if ( $block instanceof Generator ) {
                yield from $block;
            } elseif ( ! empty( $block ) ) {
                yield $block;
            }
        }
    }

    /**
     * Parse the content block.
     *
     * @fires filter:locomotive/theme/content/blocks/pre_parse_block
     * @fires filter:locomotive/theme/content/blocks/pre_parse_block/layout={$layout}
     *
     * @fires filter:locomotive/theme/content/blocks/parse_block
     * @fires filter:locomotive/theme/content/blocks/parse_block/layout={$layout}
     *
     * @param  array       $row    The layout row to process.
     * @param  string|null $layout The block layout to render.
     * @return mixed       Expected to be an object or NULL but can be anything.
     */
    public function parse_block( array $row, string $layout = null )
    {
        $layout = $this->parse_layout( $layout, $row );

        if ( empty( $layout ) ) {
            return null;
        }

        if ( empty( $row['acf_fc_layout'] ) ) {
            $row['acf_fc_layout'] = $layout;
        }

        $block = null;
        $block = apply_filters_ref_array( 'locomotive/theme/content/blocks/pre_parse_block',                  [ $block, $row, $layout, $this ] );
        $block = apply_filters_ref_array( "locomotive/theme/content/blocks/pre_parse_block/layout={$layout}", [ $block, $row, $layout, $this ] );

        if ( false === $block ) {
            return null;
        }

        if ( null !== $block ) {
            return $block;
        }

        $method = "block_{$layout}";

        if ( method_exists( $this, $method ) ) {
            $block = $this->{$method}( $row );
        }

        $block = apply_filters_ref_array( 'locomotive/theme/content/blocks/parse_block',                  [ $block, $row, $layout, $this ] );
        $block = apply_filters_ref_array( "locomotive/theme/content/blocks/parse_block/layout={$layout}", [ $block, $row, $layout, $this ] );

        if ( false === $block ) {
            return null;
        }

        return $block;
    }

    /**
     * Split the content blocks into groups.
     *
     * @see https://packagist.org/packages/mcaskill/php-array-chunk-by
     *
     * @param  array           $blocks     The content blocks to prepare.
     * @param  string|callable $groupFn    The comparison function or block property for grouping.
     * @param  string|callable $subgroupFn The comparison function or block property for sub-grouping.
     * @param  array|null      $struct     The default structure for each chunk.
     * @throws InvalidArgumentException
     * @return array|null
     */
    public function group_blocks( array $blocks, $groupFn, $subgroupFn = null, array $struct = null ) : ?array
    {
        if ( ! ( $groupFn instanceof Closure ) ) {
            if ( ! is_string( $groupFn ) ) {
                throw new InvalidArgumentException(
                    '$groupFn must be a Closure or a block property name.'
                );
            }

            $property = $groupFn;
            $groupFn  = function ( $a, $b ) use ( $property ) {
                $a = isset( $a->{$property} ) ? $a->{$property} : null;
                $b = isset( $b->{$property} ) ? $b->{$property} : null;

                return ($a !== $b);
            };
        }

        if ( $subgroupFn !== null ) {
            if ( ! ( $subgroupFn instanceof Closure ) ) {
                if ( ! is_string( $subgroupFn ) ) {
                    throw new InvalidArgumentException(
                        '$subgroupFn must be a Closure or a block property name.'
                    );
                }

                $property   = $subgroupFn;
                $subgroupFn = function ( $a, $b ) use ( $property ) {
                    $a = isset( $a->{$property} ) ? $a->{$property} : null;
                    $b = isset( $b->{$property} ) ? $b->{$property} : null;

                    return ($a !== $b);
                };
            }
        }

        $defaults = [
            'blocks' => [],
        ];

        if ( $struct !== null ) {
            $defaults = array_replace( $defaults, $struct );
        }

        $reducer = function ( array $carry, $current ) use ( $groupFn, $subgroupFn, $defaults, $struct ) {
            $length  = count($carry);

            if ( $length > 0 ) {
                $chunk    = end($carry);
                $previous = $chunk->blocks[ count($chunk->blocks) - 1 ];
                if ( $groupFn($previous, $current) ) {
                    if ( $subgroupFn ) {
                        $chunk->blocks = $this->group_blocks( $chunk->blocks, $subgroupFn, null, $struct );
                    }

                    // Split, create a new group.
                    $carry[] = (object) array_replace( $defaults, [
                        'blocks' => [ $current ],
                    ] );
                } else {
                    // Put into the $currentrent group.
                    $chunk->blocks[] = $current;
                }
            } else {
                // The first group.
                $carry[] = (object) array_replace( $defaults, [
                    'blocks' => [ $current ],
                ] );
            }
            return $carry;
        };

        $chunks = array_reduce($blocks, $reducer, []);

        if ( $subgroupFn && $chunks ) {
            $chunk = end($chunks);
            $chunk->blocks = $this->group_blocks( $chunk->blocks, $subgroupFn, null, $struct );
        }

        return $chunks;
    }

    /**
     * Retrieve the default block structure.
     *
     * @return array
     */
    protected function default_block_struct()
    {
        return [
            'block_id'         => uniqid(),
            'block_type'       => null,
            'block_view'       => null,
            'block_classes'    => [],
        ];
    }

    /**
     * Parse the ACF layout.
     *
     * @param  ?string    $layout The block layout to parse.
     * @param  array|null $row    The layout row to parse.
     * @return ?string
     */
    protected function parse_layout( ?string $layout, array $row = null ) : ?string
    {
        if ( null !== $layout ) {
            return $layout;
        }

        if ( ! empty( $row['acf_fc_layout'] ) ) {
            return $row['acf_fc_layout'];
        }

        return null;
    }

    /**
     * Determine if a post exists based on ID.
     *
     * @param  int|null $post_id The post ID.
     * @return bool
     */
    protected function post_exists( $post_id )
    {
        if ( empty( $post_id ) ) {
            return false;
        }

        $post = get_post( $post_id );

        if ( empty( $post ) || is_wp_error( $post ) ) {
            return false;
        }

        return true;
    }

    /**
     * Determine if a term exists based on ID.
     *
     * @param  int|null $term_id  The term ID.
     * @param  string   $taxonomy Optional. Taxonomy name that $term is part of.
     * @return bool
     */
    protected function term_exists( $term_id, $taxonomy = '' )
    {
        if ( empty( $term_id ) ) {
            return false;
        }

        $term = get_term( $term_id, $taxonomy );

        if ( empty( $term ) || is_wp_error( $term ) ) {
            return false;
        }

        return true;
    }

    /**
     * Outputs the view templates for each block.
     *
     * @param  callable|null $callback Optional. A user-supplied display function.
     * @return void
     */
    public function display( callable $callback = null ) : void
    {
        if ( null === $callback ) {
            foreach ( $this as $block ) {
                \Hybrid\View\display( 'web/blocks', $block->block_view, [
                    'block' => $block
                ] );
            }
        } else {
            foreach ( $this as $block ) {
                $callback( $block );
            }
        }
    }

    /**
     * Get all blocks in the builder.
     *
     * @return array
     */
    public function all() : array
    {
        if ( is_array( $this->source ) ) {
            return $this->source;
        }

        return iterator_to_array( $this->getIterator() );
    }

    /**
     * Count the number of blocks in the collection.
     *
     * @return int
     */
    public function count() : int
    {
        if ( is_array( $this->source ) ) {
            return count( $this->source );
        }

        return iterator_count( $this->getIterator() );
    }

    /**
     * Get the blocks iterator.
     *
     * @return Traversable
     */
    public function getIterator() : Traversable
    {
        return $this->makeIterator( $this->source );
    }

    /**
     * Make an iterator from the given source.
     *
     * @param  mixed $source The source of blocks.
     * @return Traversable
     */
    protected function makeIterator( $source ) : Traversable
    {
        if ( $source instanceof Closure ) {
            return $source->call( $this );
        }

        if ( is_iterable( $source ) ) {
            return $this->parse_blocks( $source );
        }

        if ( isset( $source->selector ) ) {
            return $this->parse_field( $source->selector, $source->context );
        }

        return $this->parse_field( $source );
    }
}
