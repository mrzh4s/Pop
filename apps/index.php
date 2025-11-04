<?php

// Define global root path
define('ROOT_PATH', realpath($_SERVER['DOCUMENT_ROOT']));


// Set include path using ROOT_PATH
set_include_path(ROOT_PATH);

require_once ROOT_PATH . '/core/bootstrap.php';
require_once ROOT_PATH . '/routes.php';
?>