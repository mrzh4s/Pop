<?php
/**
 * FontAwesome Icon Component
 * File: components/ui/icon.php
 * 
 * Usage:
 * component('ui.icon', ['icon' => 'question-mark', 'class' => 'fs-2x', 'variant' => 'fad'])
 * component('ui.icon', ['icon' => 'user', 'class' => 'text-primary me-2'])
 * component('ui.icon', ['icon' => 'heart', 'variant' => 'far', 'class' => 'text-danger'])
 * component('ui.icon', ['icon' => 'save', 'variant' => 'fas', 'class' => 'me-2', 'title' => 'Save'])
 */

// Extract data with defaults
$icon = $data['icon'] ?? 'question-mark';
$variant = $data['variant'] ?? 'fas';
$class = $data['class'] ?? '';
$title = $data['title'] ?? '';
$attributes = $data['attributes'] ?? [];
$ariaHidden = $data['aria_hidden'] ?? true;

// Build the icon class
$iconClass = $variant . ' fa-' . $icon;

// Add additional classes if provided
if (!empty($class)) {
    $iconClass .= ' ' . $class;
}

// Build attributes array
$attrs = [];
$attrs['class'] = $iconClass;

// Add title for accessibility if provided
if (!empty($title)) {
    $attrs['title'] = $title;
}

// Add aria-hidden for accessibility
if ($ariaHidden) {
    $attrs['aria-hidden'] = 'true';
}

// Merge with custom attributes
if (!empty($attributes) && is_array($attributes)) {
    $attrs = array_merge($attrs, $attributes);
}

// Build the HTML attributes string
$attrString = '';
foreach ($attrs as $key => $value) {
    if (is_bool($value)) {
        if ($value) {
            $attrString .= ' ' . htmlspecialchars($key, ENT_QUOTES, 'UTF-8');
        }
    } else {
        $attrString .= ' ' . htmlspecialchars($key, ENT_QUOTES, 'UTF-8') . '="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '"';
    }
}
?>
<i<?= $attrString ?> ></i>