<?php


// ============== GLOBAL HELPER FUNCTIONS ==============

/**
 * Main view function
 */
if (!function_exists('view')) {
    function view($view, $data = []) {
        if (class_exists('ViewEngine')) {
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

if (!function_exists('media')) {
    function media (string $path ) {
        return asset('media'). '/'. $path;
    }
}

/**
 * Generate image URL with alt text support
 */
if (!function_exists('img')) {
    function img($src, $alt = '', $attributes = []) {
        $url = media($src).'';
        $alt = htmlspecialchars($alt);
        
        // Build attributes string
        $attrs = '';
        foreach ($attributes as $key => $value) {
            $attrs .= ' ' . $key . '="' . htmlspecialchars($value) . '"';
        }
        
        return '<img src="' . $url . '" alt="' . $alt . '"' . $attrs . '>';
    }
}

/**
 * Generate CSS link tag
 */
if (!function_exists('css')) {
    function css($path, $media = 'all', $attributes = []) {
        $url = asset($path);
        
        // Build attributes string
        $attrs = '';
        foreach ($attributes as $key => $value) {
            $attrs .= ' ' . $key . '="' . htmlspecialchars($value) . '"';
        }
        
        return '<link rel="stylesheet" href="' . $url . '" media="' . $media . '"' . $attrs . ' />';
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

/**
 * Generate link tag
 */
if (!function_exists('link_tag')) {
    function link_tag($href, $rel = 'stylesheet', $attributes = []) {
        $url = strpos($href, 'http') === 0 ? $href : asset($href);
        
        // Build attributes string
        $attrs = '';
        foreach ($attributes as $key => $value) {
            $attrs .= ' ' . $key . '="' . htmlspecialchars($value) . '"';
        }
        
        return '<link rel="' . $rel . '" href="' . $url . '"' . $attrs . '>';
    }
}



// ============== PURE PHP PERMISSION HELPER FUNCTIONS ==============

/**
 * Check if user can perform action - outputs opening PHP if tag
 */
if (!function_exists('if_can')) {
    function if_can($permission, $value = null, $attribute = null, $attributeValue = null) {
        if (function_exists('can') && can($permission, $value, $attribute, $attributeValue)) {
            echo '<!-- if_can: true -->';
            return true;
        }
        echo '<!-- if_can: false -->';
        return false;
    }
}

/**
 * Check if user cannot perform action
 */
if (!function_exists('if_cannot')) {
    function if_cannot($permission, $value = null, $attribute = null, $attributeValue = null) {
        if (function_exists('cannot') && cannot($permission, $value, $attribute, $attributeValue)) {
            echo '<!-- if_cannot: true -->';
            return true;
        }
        echo '<!-- if_cannot: false -->';
        return false;
    }
}

/**
 * Check if user has role
 */
if (!function_exists('if_role')) {
    function if_role($role) {
        if (function_exists('can') && can('role', $role)) {
            echo '<!-- if_role: true -->';
            return true;
        }
        echo '<!-- if_role: false -->';
        return false;
    }
}

/**
 * Check if user is authenticated
 */
if (!function_exists('if_auth')) {
    function if_auth() {
        if (!empty(session('username'))) {
            echo '<!-- if_auth: true -->';
            return true;
        }
        echo '<!-- if_auth: false -->';
        return false;
    }
}

/**
 * Check if user is guest
 */
if (!function_exists('if_guest')) {
    function if_guest() {
        if (empty(session('username'))) {
            echo '<!-- if_guest: true -->';
            return true;
        }
        echo '<!-- if_guest: false -->';
        return false;
    }
}

/**
 * Check if user is in department
 */
if (!function_exists('if_department')) {
    function if_department($department) {
        if (function_exists('can') && can('department', $department, 'department', $department)) {
            echo "<!-- if_department: true ({$department}) -->";
            return true;
        }
        echo "<!-- if_department: false ({$department}) -->";
        return false;
    }
}

/**
 * Check if user is in location
 */
if (!function_exists('if_location')) {
    function if_location($location) {
        if (function_exists('can') && can('location', null, 'location', $location)) {
            echo "<!-- if_location: true ({$location}) -->";
            return true;
        }
        echo "<!-- if_location: false ({$location}) -->";
        return false;
    }
}

/**
 * Show content only to specific role
 */
if (!function_exists('show_if_role')) {
    function show_if_role($role, $content) {
        if (function_exists('can') && can('role', $role)) {
            echo $content;
        }
    }
}

/**
 * Show content only to specific department
 */
if (!function_exists('show_if_department')) {
    function show_if_department($department, $content) {
        if (function_exists('can') && can('department', $department, 'department', $department)) {
            echo $content;
        }
    }
}

/**
 * Show content only to authenticated users
 */
if (!function_exists('show_if_auth')) {
    function show_if_auth($content) {
        if (!empty(session('username'))) {
            echo $content;
        }
    }
}

/**
 * Show content only to guests
 */
if (!function_exists('show_if_guest')) {
    function show_if_guest($content) {
        if (empty(session('username'))) {
            echo $content;
        }
    }
}

/**
 * Render content based on multiple conditions (AND logic)
 */
if (!function_exists('show_if_all')) {
    function show_if_all($conditions, $content) {
        $canShow = true;
        
        foreach ($conditions as $condition) {
            $type = $condition['type'] ?? 'role';
            $value = $condition['value'] ?? null;
            $attribute = $condition['attribute'] ?? null;
            $attributeValue = $condition['attributeValue'] ?? null;
            
            switch ($type) {
                case 'role':
                    if (!function_exists('can') || !can('role', $value)) {
                        $canShow = false;
                        break 2;
                    }
                    break;
                case 'department':
                    if (!function_exists('can') || !can('department', $value, 'department', $value)) {
                        $canShow = false;
                        break 2;
                    }
                    break;
                case 'location':
                    if (!function_exists('can') || !can('location', null, 'location', $value)) {
                        $canShow = false;
                        break 2;
                    }
                    break;
                case 'auth':
                    if (empty(session('username'))) {
                        $canShow = false;
                        break 2;
                    }
                    break;
            }
        }
        
        if ($canShow) {
            echo $content;
        }
    }
}

/**
 * Render content based on multiple conditions (OR logic)
 */
if (!function_exists('show_if_any')) {
    function show_if_any($conditions, $content) {
        $canShow = false;
        
        foreach ($conditions as $condition) {
            $type = $condition['type'] ?? 'role';
            $value = $condition['value'] ?? null;
            
            switch ($type) {
                case 'role':
                    if (function_exists('can') && can('role', $value)) {
                        $canShow = true;
                        break 2;
                    }
                    break;
                case 'department':
                    if (function_exists('can') && can('department', $value, 'department', $value)) {
                        $canShow = true;
                        break 2;
                    }
                    break;
                case 'location':
                    if (function_exists('can') && can('location', null, 'location', $value)) {
                        $canShow = true;
                        break 2;
                    }
                    break;
                case 'auth':
                    if (!empty(session('username'))) {
                        $canShow = true;
                        break 2;
                    }
                    break;
            }
        }
        
        if ($canShow) {
            echo $content;
        }
    }
}