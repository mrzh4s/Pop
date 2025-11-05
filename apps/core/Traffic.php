<?php
/**
 * Traffic Core Class
 * Handles API traffic logging and monitoring
 * File: core/Traffic.php
 */

class Traffic {
    private static $instance = null;

    private function __construct() {
    }

    /**
     * Singleton pattern
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get all API traffic records
     */
    public function getAPI($filters = []) {
        try {
            $conn = db();
            
            $query = "SELECT * FROM api_traffic";
            $params = [];
            $conditions = [];

            // Apply filters
            if (!empty($filters['traffic'])) {
                $conditions[] = "traffic = :traffic";
                $params['traffic'] = $filters['traffic'];
            }

            if (!empty($filters['method'])) {
                $conditions[] = "method = :method";
                $params['method'] = $filters['method'];
            }

            if (!empty($filters['status'])) {
                $conditions[] = "status = :status";
                $params['status'] = $filters['status'];
            }

            if (!empty($filters['date_from'])) {
                $conditions[] = "created_at >= :date_from";
                $params['date_from'] = $filters['date_from'];
            }

            if (!empty($filters['date_to'])) {
                $conditions[] = "created_at <= :date_to";
                $params['date_to'] = $filters['date_to'];
            }

            if (!empty($conditions)) {
                $query .= " WHERE " . implode(" AND ", $conditions);
            }

            $query .= " ORDER BY created_at DESC";

            if (!empty($filters['limit'])) {
                $query .= " LIMIT :limit";
                $params['limit'] = (int)$filters['limit'];
            }

            $stmt = $conn->prepare($query);
            
            foreach ($params as $key => $value) {
                if ($key === 'limit') {
                    $stmt->bindValue(':' . $key, $value, PDO::PARAM_INT);
                } else {
                    $stmt->bindValue(':' . $key, $value);
                }
            }

            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log("Traffic getAPI error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Log API request traffic
     */
    public function requestAPI($traffic, $url, $method, $headers, $body, $response, $status) {
        try {
            $conn = db();
            
            $query = "INSERT INTO api_traffic (traffic, url, method, headers, body, response, status, created_at)
                     VALUES (:traffic, :url, :method, :headers, :body, :response, :status, CURRENT_TIMESTAMP)";
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':traffic', $traffic);
            $stmt->bindParam(':url', $url);
            $stmt->bindParam(':method', $method);
            $stmt->bindParam(':headers', $headers);
            $stmt->bindParam(':body', $body);
            $stmt->bindParam(':response', $response);
            $stmt->bindParam(':status', $status);
            
            return $stmt->execute();

        } catch (Exception $e) {
            error_log("Traffic requestAPI error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get traffic statistics
     */
    public function getStats($period = '24h') {
        try {
            $conn = db();
            
            $dateCondition = $this->buildDateCondition($period);
            
            $query = "SELECT 
                        traffic,
                        method,
                        status,
                        COUNT(*) as count,
                        AVG(CASE WHEN response_time IS NOT NULL THEN response_time END) as avg_response_time
                      FROM api_traffic
                      WHERE {$dateCondition}
                      GROUP BY traffic, method, status
                      ORDER BY count DESC";
            
            $stmt = $conn->prepare($query);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log("Traffic getStats error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get error logs
     */
    public function getErrors($limit = 100) {
        try {
            $conn = db();
            
            $query = "SELECT * FROM api_traffic
                     WHERE status IN ('error', 'false', 'failed')
                     ORDER BY created_at DESC
                     LIMIT :limit";
            
            $stmt = $conn->prepare($query);
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log("Traffic getErrors error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get traffic by endpoint
     */
    public function getByEndpoint($endpoint, $limit = 50) {
        try {
            $conn = db();
            
            $query = "SELECT * FROM api_traffic
                     WHERE url LIKE :endpoint
                     ORDER BY created_at DESC
                     LIMIT :limit";
            
            $stmt = $conn->prepare($query);
            $stmt->bindValue(':endpoint', '%' . $endpoint . '%');
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log("Traffic getByEndpoint error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Clean old traffic logs
     */
    public function cleanup($daysToKeep = 30) {
        try {
            $conn = db();
            
            $query = "DELETE FROM api_traffic
                     WHERE created_at < datetime('now', '-' || :days || ' days')";
            
            $stmt = $conn->prepare($query);
            $stmt->bindValue(':days', (int)$daysToKeep, PDO::PARAM_INT);
            
            $result = $stmt->execute();
            $deletedRows = $stmt->rowCount();
            
            return [
                'success' => $result,
                'deleted_rows' => $deletedRows
            ];

        } catch (Exception $e) {
            error_log("Traffic cleanup error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get summary dashboard data
     */
    public function getDashboard() {
        try {
            $conn = db();
            
            $stats = [
                'total_requests' => $this->getTotalRequests(),
                'today_requests' => $this->getTodayRequests(),
                'error_rate' => $this->getErrorRate(),
                'top_endpoints' => $this->getTopEndpoints(10),
                'recent_errors' => $this->getErrors(5)
            ];
            
            return $stats;

        } catch (Exception $e) {
            error_log("Traffic getDashboard error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Private helper methods
     */
    private function buildDateCondition($period) {
        switch ($period) {
            case '1h':
                return "created_at >= datetime('now', '-1 hour')";
            case '24h':
            case '1d':
                return "created_at >= datetime('now', '-1 day')";
            case '7d':
            case '1w':
                return "created_at >= datetime('now', '-7 days')";
            case '30d':
            case '1m':
                return "created_at >= datetime('now', '-30 days')";
            default:
                return "created_at >= datetime('now', '-1 day')";
        }
    }

    private function getTotalRequests() {
        try {
            $conn = db();
            $stmt = $conn->prepare("SELECT COUNT(*) FROM api_traffic");
            $stmt->execute();
            return (int)$stmt->fetchColumn();
        } catch (Exception $e) {
            return 0;
        }
    }

    private function getTodayRequests() {
        try {
            $conn = db();
            $stmt = $conn->prepare("SELECT COUNT(*) FROM api_traffic WHERE DATE(created_at) = DATE('now')");
            $stmt->execute();
            return (int)$stmt->fetchColumn();
        } catch (Exception $e) {
            return 0;
        }
    }

    private function getErrorRate() {
        try {
            $conn = db();
            $stmt = $conn->prepare("
                SELECT 
                    ROUND(
                        (COUNT(CASE WHEN status IN ('error', 'false', 'failed') THEN 1 END) / COUNT(*)) * 100,
                        2
                    ) as error_rate
                FROM api_traffic
                WHERE created_at >= datetime('now', '-1 day')
            ");
            $stmt->execute();
            return (float)$stmt->fetchColumn();
        } catch (Exception $e) {
            return 0.0;
        }
    }

    private function getTopEndpoints($limit = 10) {
        try {
            $conn = db();
            $stmt = $conn->prepare("
                SELECT 
                    url,
                    COUNT(*) as request_count,
                    AVG(CASE WHEN status = 'success' THEN 1 ELSE 0 END) * 100 as success_rate
                FROM api_traffic
                WHERE created_at >= datetime('now', '-1 day')
                GROUP BY url
                ORDER BY request_count DESC
                LIMIT :limit
            ");
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Export traffic data
     */
    public function export($filters = [], $format = 'json') {
        try {
            $data = $this->getAPI($filters);
            
            switch ($format) {
                case 'csv':
                    return $this->exportToCsv($data);
                case 'xml':
                    return $this->exportToXml($data);
                case 'json':
                default:
                    return json_encode($data, JSON_PRETTY_PRINT);
            }
        } catch (Exception $e) {
            error_log("Traffic export error: " . $e->getMessage());
            return false;
        }
    }

    private function exportToCsv($data) {
        if (empty($data)) return '';
        
        $output = fopen('php://temp', 'r+');
        fputcsv($output, array_keys($data[0]));
        
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return $csv;
    }

    private function exportToXml($data) {
        $xml = new SimpleXMLElement('<traffic_logs/>');
        
        foreach ($data as $record) {
            $log = $xml->addChild('log');
            foreach ($record as $key => $value) {
                $log->addChild($key, htmlspecialchars($value));
            }
        }
        
        return $xml->asXML();
    }
}
