<?php

namespace App\Theme\Template;

use App\Theme\Transformer\TransformerInterface as Transformer;
use InvalidArgumentException;
use Timber\Post;
use Timber\Timber;

abstract class AbstractTemplate
{
    /** @var ?array<string, mixed> */
    private ?array $context;

    /** @var ?array<string, mixed> */
    private ?array $fields;

    /** @var Timber\Post */
    private ?Post $post;

    public function __construct()
    {
        $context    = Timber::context();
        $this->post = Timber::get_post();

        $context['post'] = $this->post;
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
        $this->context = array_merge($context, $data);
        return $this;
    }

    /**
     * Get Context Data
     *
     * @return array<string, mixed>
     */
    public function get_context() : array
    {
        if (!isset($this->context)) {
            $this->context = [];
        }
        return $this->context;
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

            if (!empty($this->post->ID)) {
                $this->fields = get_fields($this->post->ID);
            }
        }
        return $this->fields;
    }

    /**
     * Get Post
     *
     * @return ?Post
     */
    public function get_post() : ?Post
    {
        if (!isset($this->post)) {
            return null;
        }
        return $this->post;
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
