<?php
/**
 * AUTO-DISCOVERY ViewEngine for KITER 
 * File: apps/core/ViewEngine.php
 * 
 * New Features:
 * - Auto-discovers component folders and creates functions
 * - Dynamic function generation (lists(), reports(), dashboard(), etc.)
 * - No manual registration needed
 */

class ViewEngine {
    private static $instance = null;
    private $sections = [];
    private $stacks = [];
    private $components = [];
    private $data = [];
    private $currentLayout = null;
    private $currentSection = null;
    private $currentStack = null;
    private $viewPaths = [];
    private $componentPaths = [];
    private $discoveredComponents = [];
    
    public function __construct() {
        $this->viewPaths = [
            ROOT_PATH . '/templates/',
            ROOT_PATH . '/components/'
        ];
        
        $this->componentPaths = [
            ROOT_PATH . '/components/cards/',
            ROOT_PATH . '/components/tables/',
            ROOT_PATH . '/components/modals/',
            ROOT_PATH . '/components/widgets/',
            ROOT_PATH . '/components/layouts/',
            ROOT_PATH . '/components/partials/',
            ROOT_PATH . '/components/forms/',
            ROOT_PATH . '/components/lists/',
            ROOT_PATH . '/components/ui/',
            ROOT_PATH . '/components/'
        ];
        
        // Auto-discover components and register them
        $this->autoDiscoverComponents();
        $this->registerDefaultComponents();
        $this->registerDefaultComponents();
    }
    
    /**
     * AUTO-DISCOVERY: Scan components folder and register component types
     */
    private function autoDiscoverComponents() {
        $componentsDir = ROOT_PATH . '/components/';
        
        if (!is_dir($componentsDir)) {
            return;
        }
        
        // Scan for subdirectories in components/
        $items = scandir($componentsDir);
        
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            
            $fullPath = $componentsDir . $item;
            
            // If it's a directory, register it as a component type
            if (is_dir($fullPath)) {
                $this->discoveredComponents[$item] = new ComponentRenderer($item);
                
                // Create dynamic global function for this component type
                $this->createComponentFunction($item);
            }
        }
    }
    
    /**
     * Dynamically create global component functions
     */
    private function createComponentFunction($componentType) {
        $functionName = $componentType;
        
        // Skip if function already exists
        if (function_exists($functionName)) {
            return;
        }
        
        // Create the function dynamically using eval (carefully controlled)
        $functionCode = "
        if (!function_exists('{$functionName}')) {
            function {$functionName}(\$component, \$data = []) {
                try {
                    return ViewEngine::getInstance()->renderComponent('{$componentType}', \$component, \$data);
                } catch (Exception \$e) {
                    return '<!-- Component Error (' . '{$componentType}' . '): ' . \$e->getMessage() . ' -->';
                }
            }
        }";
        
        eval($functionCode);
    }
    
    /**
     * Get singleton instance
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Register default component types (legacy support)
     */
    private function registerDefaultComponents() {
        // Merge discovered components with manual ones
        $this->components = array_merge($this->discoveredComponents, [
            'card' => new ComponentRenderer('cards'),
            'table' => new ComponentRenderer('tables'),
            'modal' => new ComponentRenderer('modals'),
            'widget' => new ComponentRenderer('widgets'),
            'layout' => new ComponentRenderer('layouts'),
            'form' => new ComponentRenderer('forms'),
            'ui' => new ComponentRenderer('ui')
        ]);
    }
    
    /**
     * MAIN RENDER METHOD
     */
    public function render($view, $data = []) {
        try {
            // Reset state for new render
            $this->currentLayout = null;
            $this->sections = [];
            $this->stacks = [];
            
            // Merge data
            $renderData = array_merge($this->data, $data);
            
            // Resolve view path
            $viewPath = $this->resolveViewPath($view);
            
            if (!file_exists($viewPath)) {
                throw new Exception("View not found: {$view} at {$viewPath}");
            }
            
            // Start output buffering
            ob_start();
            
            // Extract data to variables
            extract($renderData);
            
            // Make ViewEngine available in views
            $view_engine = $this;
            $__view_engine = $this;
            
            // Include the view directly
            include $viewPath;
            
            $content = ob_get_clean();
            
            // If layout was set, render it
            if ($this->currentLayout) {
                return $this->renderLayout($this->currentLayout, $content, $renderData);
            }
            
            return $content;
            
        } catch (Exception $e) {
            return $this->handleViewError($e, $view, $data);
        }
    }
    
    /**
     * Fallback rendering method
     */
    private function renderFallback($view, $data = []) {
        try {
            $viewPath = str_replace('.', '/', $view);
            $fullPath = ROOT_PATH . '/views/' . $viewPath . '.php';
            
            if (!file_exists($fullPath)) {
                if (defined('APP_DEBUG')) {
                    return "<!-- Fallback: View not found: $fullPath -->";
                }
                return "";
            }
            
            extract($data);
            ob_start();
            include $fullPath;
            return ob_get_clean();
            
        } catch (Exception $e) {
            if (defined('APP_DEBUG')) {
                return "<!-- Fallback render failed: " . $e->getMessage() . " -->";
            }
            error_log("ViewEngine fallback failed: " . $e->getMessage());
            return "";
        }
    }
    
    /**
     * Render layout with content - FIXED CONTENT CONFLICT
     */
    private function renderLayout($layoutName, $content, $data) {
        try {
            $layoutPath = $this->resolveLayoutPath($layoutName);
            
            if (!file_exists($layoutPath)) {
                throw new Exception("Layout not found: {$layoutName} at {$layoutPath}");
            }
            
            // Store content as main section - USE DIFFERENT NAME TO AVOID CONFLICT
            $this->sections['__main_content'] = $content;
            
            // Render layout
            ob_start();
            extract($data);
            
            $view_engine = $this;
            $__view_engine = $this;
            
            include $layoutPath;
            
            return ob_get_clean();
            
        } catch (Exception $e) {
            if (defined('APP_DEBUG')) {
                return $this->handleViewError($e, "Layout: " . $layoutName, $data);
            } else {
                error_log("Layout render error ({$layoutName}): " . $e->getMessage());
                return $content;
            }
        }
    }
    
    /**
     * Resolve layout path specifically for layouts
     */
    private function resolveLayoutPath($layout) {
        $path = str_replace('.', '/', $layout) . '.php';
        
        // Check components/layouts/ first
        $layoutsPath = ROOT_PATH . '/components/layouts/' . $path;
        if (file_exists($layoutsPath)) {
            return $layoutsPath;
        }
        
        // Check other component paths
        foreach ($this->componentPaths as $basePath) {
            $fullPath = rtrim($basePath, '/') . '/' . $path;
            if (file_exists($fullPath)) {
                return $fullPath;
            }
        }
        
        return ROOT_PATH . '/components/layouts/' . $path;
    }
    
    /**
     * Extend a layout
     */
    public function extend($layout) {
        $this->currentLayout = $layout;
    }
    
    /**
     * Start a section
     */
    public function section($name, $content = null) {
        if ($content !== null) {
            $this->sections[$name] = $content;
        } else {
            ob_start();
            $this->currentSection = $name;
        }
    }
    
    /**
     * End a section
     */
    public function endsection() {
        if (isset($this->currentSection)) {
            $this->sections[$this->currentSection] = ob_get_clean();
            unset($this->currentSection);
        }
    }
    
    /**
     * Yield a section - FIXED FOR CONTENT CONFLICT
     */
    public function yield($section, $default = '') {
        // Special handling for 'content'
        if ($section === 'content') {
            if (isset($this->sections['content']) && !empty($this->sections['content'])) {
                return $this->sections['content'];
            }
            if (isset($this->sections['__main_content'])) {
                return $this->sections['__main_content'];
            }
        }
        
        return $this->sections[$section] ?? $default;
    }
    
    /**
     * Check if section has content
     */
    public function hasSection($section) {
        return isset($this->sections[$section]) && !empty($this->sections[$section]);
    }
    
    /**
     * Push to a stack
     */
    public function push($stack, $content = null) {
        if ($content !== null) {
            if (!isset($this->stacks[$stack])) {
                $this->stacks[$stack] = [];
            }
            $this->stacks[$stack][] = $content;
        } else {
            ob_start();
            $this->currentStack = $stack;
        }
    }
    
    /**
     * End push
     */
    public function endpush() {
        if (isset($this->currentStack)) {
            if (!isset($this->stacks[$this->currentStack])) {
                $this->stacks[$this->currentStack] = [];
            }
            $this->stacks[$this->currentStack][] = ob_get_clean();
            unset($this->currentStack);
        }
    }
    
    /**
     * Render stack content
     */
    public function stack($name, $separator = "\n") {
        if (isset($this->stacks[$name])) {
            return implode($separator, $this->stacks[$name]);
        }
        return '';
    }
    
    /**
     * Include a component
     */
    public function component($component, $data = []) {
        try {
            $componentPath = $this->resolveComponentPath($component);
            
            if (!file_exists($componentPath)) {
                throw new Exception("Component not found: {$component} at {$componentPath}");
            }
            
            // Merge data
            $componentData = array_merge($this->data, $data);
            extract($componentData);
            
            // Component-specific data
            $slot = $data['slot'] ?? '';
            $attributes = $data['attributes'] ?? [];
            
            // Make ViewEngine available
            $view_engine = $this;
            $__view_engine = $this;
            
            ob_start();
            include $componentPath;
            return ob_get_clean();
            
        } catch (Exception $e) {
            return "<!-- Component Error: {$e->getMessage()} -->";
        }
    }
    
    /**
     * Render component with auto-discovery
     */
    public function renderComponent($type, $name, $data = []) {
        if (isset($this->components[$type])) {
            return $this->components[$type]->render($name, $data);
        }
        
        return $this->component("{$type}.{$name}", $data);
    }
    
    /**
     * Resolve view path
     */
    private function resolveViewPath($view) {
        $path = str_replace('.', '/', $view) . '.php';
        
        foreach ($this->viewPaths as $basePath) {
            $fullPath = rtrim($basePath, '/') . '/' . $path;
            if (file_exists($fullPath)) {
                return $fullPath;
            }
        }
        
        return rtrim($this->viewPaths[0], '/') . '/' . $path;
    }
    
    /**
     * Resolve component path
     */
    private function resolveComponentPath($component) {
        $path = str_replace('.', '/', $component) . '.php';
        
        foreach ($this->componentPaths as $basePath) {
            $fullPath = rtrim($basePath, '/') . '/' . $path;
            if (file_exists($fullPath)) {
                return $fullPath;
            }
        }
        
        return rtrim($this->componentPaths[0], '/') . '/' . $path;
    }
    
    /**
     * Share data with all views
     */
    public function share($key, $value = null) {
        if (is_array($key)) {
            $this->data = array_merge($this->data, $key);
        } else {
            $this->data[$key] = $value;
        }
    }
    
    /**
     * Handle view rendering errors
     */
    private function handleViewError($e, $view = '', $data = []) {
        if (defined('APP_DEBUG')) {
            $errorOutput = "<div style='background:#f8d7da;border:1px solid #f5c6cb;color:#721c24;padding:15px;margin:10px;border-radius:4px;'>";
            $errorOutput .= "<h3>ViewEngine Error</h3>";
            $errorOutput .= "<p><strong>View:</strong> " . htmlspecialchars($view) . "</p>";
            $errorOutput .= "<p><strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
            $errorOutput .= "<p><strong>File:</strong> " . $e->getFile() . ":" . $e->getLine() . "</p>";
            
            if (!empty($data)) {
                $errorOutput .= "<p><strong>Data:</strong><br><pre style='background:#fff;padding:10px;'>" . print_r($data, true) . "</pre></p>";
            }
            
            $errorOutput .= "<p><strong>Debug Info:</strong><br><pre style='background:#fff;padding:10px;'>" . print_r($this->getDebugInfo(), true) . "</pre></p>";
            $errorOutput .= "<p><strong>Stack Trace:</strong><br><pre style='background:#fff;padding:10px;font-size:12px;'>" . htmlspecialchars($e->getTraceAsString()) . "</pre></p>";
            $errorOutput .= "</div>";
            
            return $errorOutput;
        } else {
            error_log("ViewEngine Error ({$view}): " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
            return "<!-- ViewEngine Error: " . htmlspecialchars($e->getMessage()) . " -->";
        }
    }
    
    /**
     * Get debug information - ENHANCED WITH AUTO-DISCOVERY INFO
     */
    public function getDebugInfo() {
        return [
            'view_paths' => $this->viewPaths,
            'component_paths' => $this->componentPaths,
            'sections' => array_keys($this->sections),
            'stacks' => array_keys($this->stacks),
            'shared_data' => array_keys($this->data),
            'registered_components' => array_keys($this->components),
            'discovered_components' => array_keys($this->discoveredComponents),
            'available_functions' => array_keys($this->discoveredComponents),
            'current_sections_content' => $this->sections
        ];
    }
}

/**
 * Component Renderer Class
 */
class ComponentRenderer {
    private $basePath;
    
    public function __construct($basePath) {
        $this->basePath = $basePath;
    }
    
    public function render($component, $data = []) {
        $engine = ViewEngine::getInstance();
        return $engine->component("{$this->basePath}.{$component}", $data);
    }
}