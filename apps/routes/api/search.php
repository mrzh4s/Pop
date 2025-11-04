<?php

// ============== SEARCH API ==============
$router->post('/api/search/projects/{search:alphanum}/{page:number}', function($search, $page, $params) {
    $params['search'] = $search . '/' . $page;
    return api('search.projects', $params);
}, ['auth']);
$router->get('/api/search/projects/{search:alphanum}/{page:number}', function($search, $page, $params) {
    $params['search'] = $search . '/' . $page;
    return api('search.projects', $params);
}, ['auth']);

$router->post('/api/search/projects/{search:alphanum}', function($search, $params) {
    $params['search'] = $search;
    return api('search.projects', $params);
}, ['auth']);
$router->get('/api/search/projects/{search:alphanum}', function($search, $params) {
    $params['search'] = $search;
    return api('search.projects', $params);
}, ['auth']);