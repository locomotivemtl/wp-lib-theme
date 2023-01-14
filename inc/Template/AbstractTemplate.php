<?php

namespace App\Theme\Template;

use App\Theme\Transformer\TransformerInterface as Transformer;
use InvalidArgumentException;
use Timber\CoreEntityInterface as CoreEntity;
use Timber\Timber;

abstract class AbstractTemplate
{
    /** @var ?array<string, mixed> */
    private ?array $context;

    /** @var ?array<string, mixed> */
    private ?array $fields;

    /** @var CoreEntity */
    private ?CoreEntity $queried_entity;

    public function __construct()
    {
        $context = Timber::context();

        /** @see {@see \Timber\Timber::context()} List of resolved queried objects. */
        $this->queried_entity = (
            $context['author'] ??
            $context['term'] ??
            $context['post'] ??
            null
        );

        $this->set_context($context);
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
