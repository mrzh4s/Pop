<?php
 namespace Framework\Log\Traits;

/**
 * Fluent Traffic Query Builder Class
 */
class TrafficQueryBuilder {
    private $filters = [];
    private $period = '24h';
    private $limit = null;
    
    /**
     * Filter by traffic type
     */
    public function traffic($type) {
        $this->filters['traffic'] = $type;
        return $this;
    }
    
    /**
     * Filter by outbound traffic
     */
    public function outbound() {
        return $this->traffic('outbound');
    }
    
    /**
     * Filter by inbound traffic
     */
    public function inbound() {
        return $this->traffic('inbound');
    }
    
    /**
     * Filter by HTTP method
     */
    public function method($method) {
        $this->filters['method'] = strtoupper($method);
        return $this;
    }
    
    /**
     * Filter by GET requests
     */
    public function get() {
        return $this->method('GET');
    }
    
    /**
     * Filter by POST requests
     */
    public function post() {
        return $this->method('POST');
    }
    
    /**
     * Filter by PUT requests
     */
    public function put() {
        return $this->method('PUT');
    }
    
    /**
     * Filter by DELETE requests
     */
    public function delete() {
        return $this->method('DELETE');
    }
    
    /**
     * Filter by status
     */
    public function status($status) {
        $this->filters['status'] = $status;
        return $this;
    }
    
    /**
     * Filter by successful requests
     */
    public function success() {
        return $this->status('success');
    }
    
    /**
     * Filter by error requests
     */
    public function errors() {
        return $this->status('error');
    }
    
    /**
     * Set date range
     */
    public function from($date) {
        $this->filters['date_from'] = $date;
        return $this;
    }
    
    public function to($date) {
        $this->filters['date_to'] = $date;
        return $this;
    }
    
    /**
     * Set time period for stats
     */
    public function period($period) {
        $this->period = $period;
        return $this;
    }
    
    /**
     * Set limit
     */
    public function limit($limit) {
        $this->filters['limit'] = $limit;
        return $this;
    }
    
    /**
     * Execute query and get results
     */
    public function fetch() {
        return get_traffic($this->filters);
    }
    
    /**
     * Get statistics
     */
    public function stats() {
        return traffic_stats($this->period);
    }
    
    /**
     * Get dashboard data
     */
    public function dashboard() {
        return traffic_dashboard();
    }
    
    /**
     * Export data
     */
    public function export($format = 'json') {
        return export_traffic($this->filters, $format);
    }
    
    /**
     * Count results
     */
    public function count() {
        $results = $this->fetch();
        return is_array($results) ? count($results) : 0;
    }
}