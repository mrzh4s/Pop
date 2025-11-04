<?php
/**
 * All API Routes (Updated - No String Shortcuts)
 * File: routes/api.php
 */

// ============== SYSTEM API ==============
$router->post('/api/system/health', function($action, $params) {
    return json_encode(['success' => false, 'message' => 'System is healthy.']);
}, ['public']);



require_once 'api/accounts.php';

require_once 'api/auth.php';

require_once 'api/calendars.php';

require_once 'api/gateway.php';

require_once 'api/geospatial.php';

require_once 'api/letters.php';

require_once 'api/lists.php';

require_once 'api/notes.php';

require_once 'api/permits.php';

require_once 'api/projects.php';

require_once 'api/qrcode.php';

require_once 'api/records.php';

require_once 'api/reports.php';

require_once 'api/search.php';

require_once 'api/system.php';

require_once 'api/survey.php';

require_once 'api/tasks.php';

require_once 'api/telegrams.php';

require_once 'api/trackers.php';

require_once 'api/wayleave.php';


// ============== UUID API ==============
$router->get('/api/uuid', function($params) {
    // Direct file include since uuid.php is not in api/v1 folder
    include ROOT_PATH . '/uuid.php';
}, ['public']);
$router->post('/api/uuid', function($params) {
    // Direct file include since uuid.php is not in api/v1 folder
    include ROOT_PATH . '/uuid.php';
}, ['public']);

$router->get('/geommm/uuid', function($params) {
    return api('reports/geom/uuid', $params);
}, ['public']);
$router->post('/geommm/uuid', function($params) {
    return api('reports/geom/uuid', $params);
}, ['public']);