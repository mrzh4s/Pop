<?php

namespace Framework\Database\Factories;
use Exception;

// ============== FTP CLASS ==============
class FTPConnection
{
    private $ftp;
    private $connect;

    public function __construct()
    {
        $this->ftp = null;
    }

    public function createConnection()
    {
        if (!$this->ftp) {
            try {
                $host = ftp_config('host');
                $port = ftp_config('port');
                $username = ftp_config('username');
                $password = ftp_config('password');

                $this->connect = ftp_connect($host, $port);

                if (!$this->connect) {
                    throw new Exception("FTP connection failed to {$host}:{$port}");
                }

                $this->ftp = ftp_login($this->connect, $username, $password);

                if (!$this->ftp) {
                    throw new Exception("FTP login failed for user: {$username}");
                }

                ftp_pasv($this->connect, true);

                if (app_debug()) {
                    error_log("FTP connected successfully to {$host}");
                }
            } catch (Exception $e) {
                $errorMsg = "FTP connection initialization failed: " . $e->getMessage();

                if (app_debug()) {
                    error_log($errorMsg);
                }

                throw new Exception($errorMsg);
            }
        }

        return $this->connect;
    }

    public function getConfig()
    {
        return [
            'host' => ftp_config('host'),
            'port' => ftp_config('port'),
            'username' => ftp_config('username'),
            'path' => ftp_config('path'),
            'environment' => app_env()
        ];
    }
}
