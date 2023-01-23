<?php

namespace App\Theme\Template;

use App\Theme\Transformer\TransformerInterface as Transformer;
use InvalidArgumentException;
use Timber\CoreEntityInterface as CoreEntity;
use Timber\Loader;
use Timber\Timber;

abstract class AbstractTemplate
{
    /**
     * The template's context data.
     *
     * @var array<string, mixed>
     */
    private array $context;

    /**
     * The queried entity's ACF fields.
     *
     * @var ?array<string, mixed>
     */
    private ?array $fields = null;

    /**
     * The Timber entity.
     *
     * @var CoreEntity
     */
    private ?CoreEntity $queried_entity;

    /**
     * @param array<string, mixed> $data Any initial data to merge with
     *     the default template and context data.
     */
    public function __construct(array $data = [])
    {
        $this->context = Timber::context();
        $this->queried_entity = $this->resolve_queried_entity($this->context);

        if ($data) {
            $this->set_context($data);
        }
    }

    /**
     * Merges the provided context data.
     *
     * @param  array<string, mixed> $data Any data to merge with
     *     the current context data.
     * @return self
     */
    public function set_context(array $data) : self
    {
        if (!$data) {
            throw new InvalidArgumentException(
                'Expected $data parameter to be palpable'
            );
        }

        $this->context = $this->get_context($data);
        $this->update_queried_entity($this->context);

        return $this;
    }

    /**
     * Returns the context data.
     *
     * @param  array<string, mixed> $extra Any extra data to merge with
     *     the returned context data.
     * @return array<string, mixed>
     */
    public function get_context(array $extra = []) : array
    {
        if ($extra) {
            return array_replace($this->context, $extra);
        }

        return $this->context;
    }

    /**
     * Returns the queried entity's ACF fields.
     *
     * @return array<string, mixed>
     */
    public function get_fields() : array
    {
        return $this->fields ??= $this->load_fields();
    }

    /**
     * @return ?CoreEntity
     */
    public function get_queried_entity() : ?CoreEntity
    {
        return $this->queried_entity;
    }

    /**
     * Renders a Twig file.
     *
     * @param string[]|string $filenames Name or full path of the Twig file
     *     to render. If this is an array of file names or paths, Timber will
     *     render the first file that exists.
     * @param bool|int|array  $expires   Optional. In seconds. Use false to
     *     disable cache altogether. When passed an array, the first value is
     *     used for non-logged in visitors, the second for users. Default false.
     * @param string       $cache_mode Optional. Any of the cache mode constants
     *     defined in Timber\Loader.
     * @return bool|string The echoed output.
     */
    public function render(
        $filenames,
        $expires = false,
        $cache_mode = Loader::CACHE_USE_DEFAULT
    ) : void {
        Timber::render( $filenames, $this->get_context(), $expires, $cache_mode );
    }

    /**
     * Transform data.
     *
     * @param  array<string, mixed>                  $data
     * @param  Transformer|class-string<Transformer> $transformer
     * @throws InvalidArgumentException If the $transformer is invalid.
     * @return ?array<string, mixed>
     */
    public function transform(array $data, $transformer) : ?array
    {
        if (is_string($transformer) && class_exists($transformer)) {
            $transformer = new $transformer;
        }

        if ($transformer instanceof Transformer) {
            return $transformer->transform($data);
        }

        throw new InvalidArgumentException(sprintf(
            'Expected $transformer parameter to be a class string or instance of %s',
            Transformer::class
        ));
    }

    /**
     * Loads the queried entity's ACF fields.
     *
     * @return array<string, mixed>
     */
    protected function load_fields() : array
    {
        $entity = $this->get_queried_entity();

        return $entity
            ? (get_fields($entity->wp_object()) ?: [])
            : [];
    }

    /**
     * Returns the queried entity from the the provided dataset, if any.
     *
     * @param  array<string, mixed> $data
     * @return ?CoreEntity
     */
    protected function resolve_queried_entity(array $data) : ?CoreEntity
    {
        /** @see {@see \Timber\Timber::context()} List of resolved queried objects. */
        return (
            $data['author'] ??
            $data['term'] ??
            $data['post'] ??
            null
        );
    }

    /**
     * Updates the queried entity and related class properties
     * from the provided dataset.
     *
     * @param  array<string, mixed> $data
     * @return self
     */
    protected function update_queried_entity(array $data) : self
    {
        $queried_entity = $this->resolve_queried_entity($data);

        if ($queried_entity !== $this->queried_entity) {
            $this->queried_entity = $queried_entity;
            // If there is no queried object, flush the ACF fields.
            $this->fields = null;
        }

        return $this;
    }
}
