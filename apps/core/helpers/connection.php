<?php

/**
 * Modern helper functions
 */
function db() {
    return DB::connection();
}

function db_query($query, $params = []) {
    return DB::query($query, $params);
}

function db_health() {
    return DB::health();
}

function ftp() {
    return FTP::connection();
}