<?php

namespace Locomotive\Theme\Action;

use WP_AJAX;

/**
 * Abstract AJAX action based on WP_AJAX.
 */
abstract class AbstractAction extends WP_AJAX
{
    /**
     * The state of the action.
     *
     * @var bool
     */
    protected bool $success = true;

    /**
     * A list of errors encountered during action execution.
     *
     * @var array<string, mixed>[]
     */
    protected array $errors = [];

    /**
     * The results of the action, in any format.
     *
     * @var mixed
     */
    protected $results;

    /**
     * The response HTTP status.
     *
     * @var int
     */
    protected int $responseStatus = 200;

    /**
     * Retrieves the URL to the AJAX controller for the current action.
     *
     * @overrides WP_AJAX::url()
     *
     * @param  array  $params Optional associative array of query variables.
     * @return string AJAX URL link with optional query parameters appended.
     */
    public static function url( $params = [] )
    {
        if ( ! isset( $params['lang'] ) ) {
            if ( function_exists( 'pll_current_language' ) && ( $current_language = pll_current_language() ) ) {
                $params['lang'] = $current_language;
            } elseif ( function_exists( 'wpml_get_current_language' ) && ( $current_language = wpml_get_current_language() ) ) {
                $params['lang'] = $current_language;
            }
        }

        $params = wp_parse_args( $params, [
            'action' => (new static())->action,
        ]);

        $url = admin_url( '/admin-ajax.php' );
        $url = add_query_arg( $params, $url );

        return $url;
    }

    /**
     * The JSON response configuration. Override the parent class to specify a status code.
     *
     * @param  mixed $response Variable to encode as JSON, then print and die.
     * @param  int   $status   Optional. The HTTP status code to output.
     * @return void
     */
    public function JSONResponse( $response, int $status = null ) : void
    {
        if ( $status === null ) {
            $status = $this->responseStatus;
        }

        wp_send_json( $response, $status );
    }

    /**
     * Set the state of the action.
     *
     * @param  bool $state A truthy state.
     * @return self
     */
    protected function setSuccess( bool $state ) : self
    {
        $this->success = (bool) $state;

        return $this;
    }

    /**
     * Retrieve the state of the action.
     *
     * @return bool
     */
    protected function success() : bool
    {
        return $this->success;
    }

    /**
     * Add an error to the error list.
     *
     * @param  mixed $message The description for the error.
     * @return self
     */
    protected function addError( $error ) : self
    {
        if ( ! is_array( $error ) ) {
            $error = [
                'message' => $error,
            ];
        }

        $this->errors[] = $error;

        return $this;
    }

    /**
     * Set the list of errors.
     *
     * @param array<string, mixed>[] $errors
     */
    public function setErrors( array $errors ) : self
    {
        $this->errors = $errors;

        return $this;
    }

    /**
     * Retrieve the list of errors.
     *
     * @return array<string, mixed>[]
     */
    protected function errors() : array
    {
        return $this->errors;
    }

    /**
     * Set the action's results.
     *
     * @param  mixed $results The action's results.
     * @return self
     */
    protected function setResults( $results ) : self
    {
        $this->results = $results;

        return $this;
    }

    /**
     * Retrieve the action's results.
     *
     * @return mixed
     */
    public function results()
    {
        return $this->results;
    }
}
