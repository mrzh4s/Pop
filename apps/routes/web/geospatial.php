<?php
/**
 * Geospatial Routes (Updated)
 * File: routes/geospatial.php
 */

// Geospatial Extract
$router->get('/geospatial/{option:[A-Z]+}/extract/{sid:id}', function($option, $sid, $params) {
    $params['option'] = $option;
    return view('geospatial.extract', $params);
}, ['auth']);

// Geospatial Letters Route
$router->get('/geospatial/letters/route/{sid:id}', function($sid, $params) {
    return view('geospatial.letters.route', $params);
}, ['auth']);

// Geospatial PIL Upload
$router->get('/geospatial/PIL/upload/{sid:id}', function($sid, $params) {
    return view('geospatial.upload', $params);
}, ['auth']);