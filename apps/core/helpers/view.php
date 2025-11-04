<?php


// ============== GLOBAL HELPER FUNCTIONS ==============

/**
 * Main view function
 */
if (!function_exists('view')) {
    function view($view, $data = []) {
        if (class_exists('ViewEngine')) {
            global $currentView;
            $currentView = $view;
            try {
                $engine = ViewEngine::getInstance();
                return $engine->render($view, $data);
            } catch (Exception $e) {
                if (defined('APP_DEBUG')) {
                    return "ViewEngine Error: " . $e->getMessage();
                }
                
                error_log("ViewEngine Error: " . $e->getMessage());
                return view_fallback($view, $data);
            }
        }
        
        return view_fallback($view, $data);
    }
}

/**
 * Fallback view function
 */
if (!function_exists('view_fallback')) {
    function view_fallback($view, $data = []) {
        try {
            $viewPath = str_replace('.', '/', $view);
            $fullPath = ROOT_PATH . '/views/' . $viewPath . '.php';
            
            if (!file_exists($fullPath)) {
                return "<!-- View not found: $fullPath -->";
            }
            
            extract($data);
            ob_start();
            include $fullPath;
            return ob_get_clean();
            
        } catch (Exception $e) {
            return "<!-- Fallback view error: " . $e->getMessage() . " -->";
        }
    }
}

/**
 * Component functions
 */
if (!function_exists('component')) {
    function component($component, $data = []) {
        try {
            return ViewEngine::getInstance()->component($component, $data);
        } catch (Exception $e) {
            return "<!-- Component Error: " . $e->getMessage() . " -->";
        }
    }
}

// Layout functions
if (!function_exists('extend')) {
    function extend($layout) {
        ViewEngine::getInstance()->extend($layout);
    }
}

if (!function_exists('section')) {
    function section($name, $content = null) {
        return ViewEngine::getInstance()->section($name, $content);
    }
}

if (!function_exists('endsection')) {
    function endsection() {
        ViewEngine::getInstance()->endsection();
    }
}

if (!function_exists('slot')) {
    function slot($section, $default = '') {
        return ViewEngine::getInstance()->yield($section, $default);
    }
}

if (!function_exists('has_section')) {
    function has_section($section) {
        return ViewEngine::getInstance()->hasSection($section);
    }
}

// Asset management functions
if (!function_exists('push')) {
    function push($stack, $content = null) {
        return ViewEngine::getInstance()->push($stack, $content);
    }
}

if (!function_exists('endpush')) {
    function endpush() {
        ViewEngine::getInstance()->endpush();
    }
}

if (!function_exists('stack')) {
    function stack($name, $separator = "\n") {
        return ViewEngine::getInstance()->stack($name, $separator);
    }
}

// Data sharing
if (!function_exists('view_share')) {
    function view_share($key, $value = null) {
        ViewEngine::getInstance()->share($key, $value);
    }
}

if(!function_exists('partial')) {
    function partial($name, $value = []) {
        global $currentView;
        if (!empty($currentView)) {
            // Convert dot notation to path (applications.migrate.new -> applications/migrate/new)
            $viewPath = str_replace('.', '/', $currentView);
            $viewDir = dirname($viewPath);
            
            // If dirname returns '.' it means no subdirectory, skip to global fallback
            if ($viewDir !== '.') {
                $path = ROOT_PATH . '/templates/' . $viewDir . '/partials/' . $name . '.php';
                if (file_exists($path)) {
                    extract($value, EXTR_SKIP);
                    ob_start();
                    include $path;
                    return ob_get_clean();
                }
            }
        }
        
        // Fallback to root partials
        $path = ROOT_PATH . '/templates/partials/' . $name . '.php';
        if (file_exists($path)) {
            extract($value, EXTR_SKIP);
            ob_start();
            include $path;
            return ob_get_clean();
        }
        
        error_log("Partial not found: {$name} (View: {$currentView})");
        return '';
    }
}

// ============== GLOBAL ASSET HELPER FUNCTIONS ==============

/**
 * Generate asset URL with versioning support
 */
if (!function_exists('asset')) {
    function asset($path) {
        // Remove leading slash if present
        $path = ltrim($path, '/');
        
        // Get app URL - DO NOT use rtrim here to preserve protocol
        $appUrl = app_url().'/assets';
        
        // Build full URL
        $url = $appUrl . '/' . $path;
        
        return $url;
    }
}
/**
 * Generate image URL with alt text support
 */
if (!function_exists('img')) {
    function img($src,$attributes = [], $alt = '') {
        $src = asset('images/' . ltrim($src, '/'));
        $alt = htmlspecialchars($alt);
        
        // Build attributes string
        $attrs = '';
        foreach ($attributes as $key => $value) {
            $attrs .= ' ' . $key . '="' . htmlspecialchars($value) . '"';
        }
        
        return '<img src="' . $src . '" alt="' . $alt . '"' . $attrs . '>';
    }
}

/**
 * Generate CSS link tag
 */
if (!function_exists('css')) {
    function css($path,$attributes = []) {
        $url = asset($path);
        
        // Build attributes string
        $attrs = 'type="text/css"';
        foreach ($attributes as $key => $value) {
            $attrs .= ' ' . $key . '="' . htmlspecialchars($value) . '"';
        }
        
        return '<link rel="stylesheet" href="' . $url . '"' . $attrs . ' />';
    }
}

/**
 * Generate JavaScript script tag
 */
if (!function_exists('js')) {
    function js($path, $attributes = []) {
        $url = asset($path);
        
        // Build attributes string
        $attrs = '';
        foreach ($attributes as $key => $value) {
            if ($key === 'defer' || $key === 'async') {
                $attrs .= ' ' . $key;
            } else {
                $attrs .= ' ' . $key . '="' . htmlspecialchars($value) . '"';
            }
        }
        
        return '<script src="' . $url . '"' . $attrs . '></script>';
    }
}

/**
 * Generate inline style tag
 */
if (!function_exists('style')) {
    function style($css, $attributes = []) {
        // Build attributes string
        $attrs = '';
        foreach ($attributes as $key => $value) {
            $attrs .= ' ' . $key . '="' . htmlspecialchars($value) . '"';
        }
        
        return '<style' . $attrs . '>' . $css . '</style>';
    }
}

/**
 * Generate favicon link tag
 */
if (!function_exists('favicon')) {
    function favicon($path, $type = 'image/x-icon') {
        $url = asset($path);
        return '<link rel="icon" type="' . $type . '" href="' . $url . '">';
    }
}

/**
 * Generate meta tag
 */
if (!function_exists('meta')) {
    function meta($name, $content, $type = 'name') {
        return '<meta ' . $type . '="' . htmlspecialchars($name) . '" content="' . htmlspecialchars($content) . '">';
    }
}