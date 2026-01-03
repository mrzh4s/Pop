<?php
/**
 * Inertia Helper Functions
 * File: apps/core/helpers/inertia.php
 *
 * Global helper functions for Inertia.js integration
 */
use Framework\View\Inertia;

if (!function_exists('inertia')) {
    /**
     * Create an Inertia response
     *
     * @param string $component Component name
     * @param array $props Component props
     * @return void
     */
    function inertia($component, $props = []) {
        return Inertia::render($component, $props);
    }
}

if (!function_exists('inertia_location')) {
    /**
     * Redirect to external URL (Inertia-aware)
     *
     * @param string $url
     * @return void
     */
    function inertia_location($url) {
        return Inertia::location($url);
    }
}

if (!function_exists('inertia_lazy')) {
    /**
     * Create a lazy prop (only loaded on partial reload)
     *
     * @param callable $callback
     * @return array
     */
    function inertia_lazy($callback) {
        return Inertia::lazy($callback);
    }
}
