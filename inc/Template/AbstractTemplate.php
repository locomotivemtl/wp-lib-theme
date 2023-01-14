<?php

namespace App\Theme\Template;

use App\Theme\Transformer\TransformerInterface as Transformer;
use InvalidArgumentException;
use Timber\CoreEntityInterface as CoreEntity;
use Timber\Loader;
use Timber\Timber;

abstract class AbstractTemplate
{
    /** @var array<string, mixed> */
    private array $context;

    /** @var ?array<string, mixed> */
    private ?array $fields;

    /** @var CoreEntity */
    private ?CoreEntity $queried_entity;

    /**
     * @param array<string, mixed> $data Context data.
     */
    public function __construct(array $data = [])
    {
        $context = Timber::context();

        $this->context = array_replace($context, $data);

        /** @see {@see \Timber\Timber::context()} List of resolved queried objects. */
        $this->queried_entity = (
            $context['author'] ??
            $context['term'] ??
            $context['post'] ??
            null
        );
    }

    /**
     * Set Context Data
     *
     * @param  array<string, mixed> $data Context data.
     * @return self
     */
    public function set_context(array $data = []) : self
    {
        $context = $this->get_context();

        $this->context = array_replace($context, $data);

        return $this;
    }

    /**
     * Get Context Data
     *
     * @return array<string, mixed>
     */
    public function get_context() : array
    {
        return $this->context ??= [];
    }

    /**
     * Get ACF Fields
     *
     * @return array<string, mixed>
     */
    public function get_fields() : array
    {
        if (!isset($this->fields)) {
            $this->fields = [];

            $entity = $this->get_queried_entity();
            if ($entity) {
                $this->fields = get_fields($entity->wp_object()) ?: [];
            }
        }

        return $this->fields;
    }

    /**
     * @return ?CoreEntity
     */
    public function get_queried_entity() : ?CoreEntity
    {
        return ($this->queried_entity ?? null);
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
}
