<?php

/**
 * @
 * Laravel Helper Functions Stub File
 * This file provides IDE support for Laravel helper functions
 * Do not execute this file
 */

namespace {

    use Illuminate\Database\Eloquent\Collection;
    use Illuminate\Contracts\View\View;
    use Illuminate\Http\RedirectResponse;
    use Illuminate\Support\Collection as SupportCollection;
    use Illuminate\Support\Carbon;

    /**
     * Helper function: collect()
     * @template TKey of array-key
     * @template TValue
     * @param array<TKey, TValue>|null $value
     * @return SupportCollection<TKey, TValue>
     */
    function collect($value = null) {}

    /**
     * Helper function: view()
     * @param string $view
     * @param array $data
     * @return View
     */
    function view($view, $data = []) {}

    /**
     * Helper function: redirect()
     * @param string|null $to
     * @return RedirectResponse
     */
    function redirect($to = null) {}

    /**
     * Helper function: abort()
     * @param int $code
     * @param string $message
     * @return void
     */
    function abort($code, $message = '') {}

    /**
     * Helper function: storage_path()
     * @param string $path
     * @return string
     */
    function storage_path($path = '') {}

    /**
     * Helper function: public_path()
     * @param string $path
     * @return string
     */
    function public_path($path = '') {}

    /**
     * Helper function: route()
     * @param string $name
     * @param array $parameters
     * @return string
     */
    function route($name, $parameters = []) {}

    /**
     * Helper function: back()
     * @return RedirectResponse
     */
    function back() {}

    /**
     * Helper function: response()
     * @return void
     */
    function response() {}

    /**
     * Helper function: now()
     * @return Carbon
     */
    function now() {}

    /**
     * Helper function: config()
     * @param string|array $key
     * @return mixed
     */
    function config($key) {}

    /**
     * Helper function: auth()
     * @return \Illuminate\Auth\AuthManager
     */
    function auth() {}

    /**
     * Helper function: env()
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function env($key, $default = null) {}

    /**
     * Helper function: dd()
     * @return void
     */
    function dd(...$args) {}

    /**
     * Helper function: optional()
     * @template T
     * @param ?T $value
     * @return T|null
     */
    function optional($value = null) {}

    /**
     * Helper function: request()
     * @return \Illuminate\Http\Request
     */
    function request() {}
}

namespace Illuminate\Database\Eloquent {

    /**
     * These are stub definitions for Eloquent Model methods
     */
    abstract class Model
    {
        /**
         * Get a new query builder for the model's table.
         * @return \Illuminate\Database\Eloquent\Builder
         */
        public static function query() {}

        /**
         * Find a model by its primary key.
         * @param mixed $id
         * @return static|null
         */
        public static function find($id) {}

        /**
         * Find a model by its primary key or throw an exception.
         * @param mixed $id
         * @return static
         */
        public static function findOrFail($id) {}

        /**
         * Convert the model to an array.
         * @return array
         */
        public function toArray() {}

        /**
         * Update the model in the database.
         * @param array $attributes
         * @return bool
         */
        public function update($attributes = []) {}

        /**
         * Reload a fresh model instance from the database.
         * @return static|null
         */
        public function fresh() {}

        /**
         * Refresh the model's attributes from the database.
         * @return $this
         */
        public function refresh() {}

        /**
         * Load the relationships for the models if they are not already loaded.
         * @param array|string $relations
         * @return $this
         */
        public function loadMissing($relations = []) {}

        /**
         * Delete the model from the database.
         * @return bool|null
         */
        public function delete() {}

        /**
         * Get the model data with only the specified keys.
         * @param array $attributes
         * @return array
         */
        public function only($attributes = []) {}

        /**
         * @var int
         */
        public $id;
    }
}

namespace Illuminate\Support\Facades {

    class Auth
    {
        /**
         * Get the currently authenticated user.
         * @return \App\Models\User|null
         */
        public static function user() {}

        /**
         * Get the authenticated user's ID.
         * @return int|null
         */
        public static function id() {}
    }
}
