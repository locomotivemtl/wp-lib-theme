<?php

namespace Locomotive\Theme\Content;

use Countable;
use Generator;
use InvalidArgumentException;
use IteratorAggregate;

/**
 * Page Builder Interface
 */
interface BuildingBlocks extends Countable, IteratorAggregate
{
    /**
     * Parse the content blocks.
     *
     * @param  iterable $rows The content blocks to prepare.
     * @return object[]|Generator Expected to be an array of object.
     */
    public function parse_blocks( iterable $rows ) : Generator;

    /**
     * Parse the content blocks from the given field name.
     *
     * @param  string $selector The flexible content field name.
     * @param  mixed  $context  The field's parent object.
     * @return object[]|Generator Expected to be an array of object.
     */
    public function parse_field( string $selector, $context = false ) : Generator;

    /**
     * Parse the content block.
     *
     * @param  array       $row    The layout row to process.
     * @param  string|null $layout The block layout to render.
     * @return mixed       Expected to be an object or NULL but can be anything.
     */
    public function parse_block( array $row, string $layout = null );

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
    public function group_blocks( array $blocks, $groupFn, $subgroupFn = null, array $struct = null ) : ?array;

    /**
     * Outputs the view templates for each block.
     *
     * @param  callable|null $callback Optional. A user-supplied display function.
     * @return void
     */
    public function display( callable $callback = null ) : void;

    /**
     * Get all blocks in the builder.
     *
     * @return array
     */
    public function all() : array;
}
