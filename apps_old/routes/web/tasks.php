<?php
/**
 * Tasks Routes (Updated - Complete)
 * File: routes/tasks.php
 */

// Tasks - Group/Section/Filter/ID pattern
$router->get('/{group:string}/{section:string}/tasks/{filter:string}/{id:id}', function($group, $section, $filter, $id, $params) {
    // Check if section is authority or general
    if (!in_array($section, ['authority', 'general'])) {
        return false;
    }
    
    $params['group'] = $group;
    $params['section'] = $section;
    $params['filter'] = $filter;
    $params['id'] = $id;
    
    return view('tasks', $params);
}, ['auth']);

// Tasks - Group/Section/Phase/Filter pattern  
$router->get('/{group:string}/{section:string}/tasks/{phase:string}/{filter:string}', function($group, $section, $phase, $filter, $params) {
    // Check if section is authority or general
    if (!in_array($section, ['authority', 'general'])) {
        return false;
    }
    
    // Only apply if the filter is NOT an ID pattern (avoid conflict with route above)
    if (preg_match('/^[A-Z0-9]{8}$/', $filter)) {
        return false;
    }
    
    $params['group'] = $group;
    $params['section'] = $section;
    $params['phase'] = $phase;
    $params['filter'] = $filter;
    
    return view('tasks', $params);
}, ['auth']);

// Tasks - Group/Section/Filter pattern
$router->get('/{group:string}/{section:string}/tasks/{filter:string}', function($group, $section, $filter, $params) {
    // Check if section is authority or general
    if (!in_array($section, ['authority', 'general'])) {
        return false;
    }
    
    $params['group'] = $group;
    $params['section'] = $section;
    $params['filter'] = $filter;
    
    return view('tasks', $params);
}, ['auth']);