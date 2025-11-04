<?php

require_once ROOT_PATH . '/pages/BasePage.php';

class MigratePage extends BasePage
{

    public function show()
    {
        $gravityData = $this->getGravityFormsEntries();
        $v2Data = $this->getSourceEntries();

        $data = array_merge($gravityData, $v2Data);

        // Uncomment to debug data
        //$this->debugData($data, $gravityData, $v2Data, $ieData);

        // Format data for datatable component
        $tableData = $this->formatDataForTable($data);

        return $this->view('applications.migrate', [
            'title' => 'Migrate Applications',
            'data' => $tableData,
            'columns' => $this->getTableColumns(),
            'stats' => $this->getStats($gravityData, $v2Data)
        ]);
    }

    private function getTableColumns()
    {
        return [
            [
                'name' => 'Reference No',
                'id' => 'reference_no',
                'width' => '120px',
                'formatter' => 'cell => gridjs.html(`<span class="fw-bold">${cell}</span>`)'
            ],
            [
                'name' => 'Title',
                'id' => 'title',
                'width' => '250px'
            ],
            [
                'name' => 'District',
                'id' => 'district',
                'width' => '120px',
                'formatter' => 'cell => {
                    if (!cell || cell === "-") return "-";
                    // Handle both array and string
                    const districts = Array.isArray(cell) ? cell : [cell];
                    const badges = districts.map(d => 
                        `<span class="badge bg-secondary me-1">${d}</span>`
                    ).join("");
                    return gridjs.html(badges);
                }'
            ],
            [
                'name' => 'System',
                'id' => 'system',
                'width' => '100px',
                'formatter' => 'cell => {
                    const systemColors = {
                        "Imohon": "info",
                        "V2": "primary",
                        "Interedge": "success",
                    };
                    const color = systemColors[cell] || "secondary";
                    return gridjs.html(`<span class="badge bg-${color}">${cell}</span>`);
                }'
            ],
            [
                'name' => 'Status',
                'id' => 'status',
                'width' => '120px',
                'formatter' => 'cell => {
                    const statusColors = {
                        "Completed": "success",
                        "Approved": "success",
                        "In Progress": "warning",
                        "Under Review": "info",
                        "Submitted": "primary",
                        "Partial": "secondary",
                        "Draft": "light",
                        "Rejected": "danger"
                    };
                    const color = statusColors[cell] || "secondary";
                    return gridjs.html(`<span class="badge bg-${color}">${cell}</span>`);
                }'
            ],
            [
                'name' => 'Length',
                'id' => 'length',
                'width' => '100px',
                'formatter' => 'cell => cell ? `${cell} m` : "-"'
            ],
            [
                'name' => 'Created Date',
                'id' => 'created_at',
                'width' => '120px',
                'formatter' => 'cell => {
                    if (!cell) return "-";
                    const date = new Date(cell);
                    return date.toLocaleDateString("en-MY", {
                        day: "2-digit",
                        month: "short",
                        year: "numeric"
                    });
                }'
            ],
            [
                'name' => 'Actions',
                'id' => 'actions',
                'width' => '75px',
                'sort' => false,
                'formatter' => '(cell, row) => {
                    const id = row.cells[7].data;
                    const system = row.cells[3].data;
                    return gridjs.html(`
                        <div class="d-flex justify-content-start gap-2">
                            <a href="/applications/migrate/view/${id}?system=${system}" 
                               class="btn btn-light btn-sm"
                               data-bs-toggle="tooltip"
                               data-bs-placement="top"
                               title="View Details">
                                <i class="fad fa-eye" class="align-middle fs-18"></i>
                            </a>
                            <a href="/applications/migrate/new/${id}?system=${system}" 
                                    class="btn btn-soft-primary btn-sm"
                                    data-bs-toggle="tooltip"
                                    data-bs-placement="top"
                                    title="Migrate Entry">
                                <i class="fad fa-file-import" class="align-middle fs-18"></i>
                            </a>
                        </div>
                    `);
                }'
            ]
        ];
    }

    /**
     * Normalize district field to always return array
     */
    private function normalizeDistrict($district)
    {
        if (empty($district)) {
            return [];
        }

        // Already an array
        if (is_array($district)) {
            return array_values(array_unique(array_filter($district)));
        }

        // String - could be PostgreSQL array format or comma-separated
        if (is_string($district)) {
            // PostgreSQL array format: {KM,KT}
            if (strpos($district, '{') === 0) {
                $cleaned = trim($district, '{}');
                if (empty($cleaned)) {
                    return [];
                }
                return array_values(array_unique(array_map('trim', explode(',', $cleaned))));
            }
            
            // Comma-separated string: KM,KT
            if (strpos($district, ',') !== false) {
                return array_values(array_unique(array_map('trim', explode(',', $district))));
            }
            
            // Single district string
            return [trim($district)];
        }

        return [];
    }

    /**
     * Format district for display (convert array to string for table)
     */
    private function formatDistrictForDisplay($district)
    {
        $normalized = $this->normalizeDistrict($district);
        
        if (empty($normalized)) {
            return '-';
        }

        return implode(', ', $normalized);
    }

    private function formatDataForTable($data)
    {
        $formatted = [];

        foreach ($data as $item) {
            $formatted[] = [
                $item['reference_no'] ?? '-',                                       // Index 0
                $item['title'] ?? 'Untitled Project',                               // Index 1
                $this->normalizeDistrict($item['district'] ?? null),      // Index 2 - Now always array
                ucfirst($item['system'] ?? 'unknown'),                      // Index 3
                $item['status'] ?? 'Unknown',                                       // Index 4
                $item['length'] ?? 0,                                               // Index 5
                $item['created_at'] ?? null,                                        // Index 6
                $item['id']                                                         // Index 7 - For actions
            ];
        }

        return $formatted;
    }

    private function getStats($gravityData = [], $v2Data = [], $ieData = [])
    {
        return [
            'total_imohon' => count($gravityData),
            'total_v2' => count($v2Data),
            'total_interedge' => count($ieData),
            'total_records' => count($gravityData) + count($v2Data) + count($ieData),
            'by_status' => $this->groupByStatus(array_merge($gravityData, $v2Data, $ieData)),
            'by_system' => [
                'imohon' => count($gravityData),
                'v2' => count($v2Data),
                'interedge' => count($ieData)
            ]
        ];
    }

    private function groupByStatus($data)
    {
        $grouped = [];
        foreach ($data as $item) {
            $status = $item['status'] ?? 'Unknown';
            if (!isset($grouped[$status])) {
                $grouped[$status] = 0;
            }
            $grouped[$status]++;
        }
        return $grouped;
    }

    private function getGravityFormsEntries()
    {
        function getDistrict($entry)
        {
            // Check all fields that start with 129.
            foreach ($entry as $fieldId => $value) {
                if (is_string($fieldId) && strpos($fieldId, '129.') === 0) {
                    if (!empty($value)) {
                        return $value;
                    }
                }
            }
            return null;
        }

        // Helper function to calculate final status
        function getFinalStatus($entry)
        {
            // Check for completion based on specific field IDs
            $hasPermitDetails = !empty($entry['87']);  // Field 87 - Permit Details
            $hasQuotation = !empty($entry['89']);      // Field 89 - Sebut Harga

            if ($hasPermitDetails && $hasQuotation) {
                return 'Completed';
            } elseif ($hasPermitDetails) {
                return 'In Progress';
            } elseif ($hasQuotation) {
                return 'Partial';
            }

            // Check for decision/approval fields
            foreach ($entry as $fieldId => $value) {
                if (is_string($value)) {
                    $lowerValue = strtolower($value);
                    if (strpos($lowerValue, 'approved') !== false || strpos($lowerValue, 'lulus') !== false) {
                        return 'Approved';
                    }
                    if (strpos($lowerValue, 'rejected') !== false || strpos($lowerValue, 'ditolak') !== false) {
                        return 'Rejected';
                    }
                    if (strpos($lowerValue, 'pending') !== false || strpos($lowerValue, 'review') !== false) {
                        return 'Under Review';
                    }
                }
            }

            // Check if form has substantial data
            $filledFields = 0;
            foreach ($entry as $key => $value) {
                if (is_numeric($key) && !empty($value)) {
                    $filledFields++;
                }
            }

            if ($filledFields > 5) {
                return 'Submitted';
            }

            return 'Draft';
        }

        // Get all entries from Form 3
        $response = gf_entries(3, [
            'paging[page_size]' => 400,
            'status' => 'active'
        ]);

        // Filter entries that have field 45 data
        $filteredEntries = [];
        foreach ($response['entries'] as $entry) {
            if (!empty($entry['45'])) {
                $filteredEntries[] = [
                    'id' => $entry['id'],
                    'system_id' => $entry['id'] ?? null,
                    'reference_no' => $entry['45'] ?? null,
                    'title' => $entry['4'] ?? null,
                    'district' => getDistrict($entry), // Keep as string, will be normalized in formatDataForTable
                    'length' => $entry['102'] ?? 0,
                    'project_status' => $entry['status'] ?? 'unknown',
                    'status' => getFinalStatus($entry),
                    'created_at' => $entry['date_created'] ?? null,
                    'system' => 'imohon'
                ];
            }
        }

        return $filteredEntries;
    }

    private function getSourceEntries()
    {
        $rows = db_query('source', 'SELECT * FROM core.mig_flw_appl_entry')->fetchAll();

        $data = [];
        foreach ($rows as &$row) {
            if (!empty($row->no_reference)) {
                $data[] = [
                    'id' => $row->id,
                    'system_id' => $row->id_sys ?? null,
                    'district' => $row->districts ?? null, // Keep raw format, will be normalized in formatDataForTable
                    'length' => $row->length ?? 0,
                    'entry_status' => 'active',
                    'status' => $row->status ?? 'unknown',
                    'system' => 'v2',
                    'created_at' => $row->dt_created ?? null,
                    'title' => $row->project_title ?? null,
                    'reference_no' => $row->no_reference ?? null,
                ];
            }
        }

        return $data;
    }

    private function getDestEntries()
    {
        $rows = db_query('dest', 'SELECT * FROM view_entry_migrates')->fetchAll();

        $data = [];
        foreach ($rows as &$row) {
            if (!empty($row->reference_no)) {
                $data[] = [
                    'id' => $row->id,
                    'system_id' => $row->system_id ?? $row->submission_code,
                    'district' => $row->districts ?? null, // Keep raw format, will be normalized in formatDataForTable
                    'length' => $row->application_length ?? 0,
                    'entry_status' => 'active',
                    'status' => $row->status ?? 'unknown',
                    'system' => 'interedge',
                    'created_at' => $row->created_at ?? null,
                    'title' => $row->project_title ?? null,
                    'reference_no' => $row->reference_no ?? null,
                ];
            }
        }

        return $data;
    }

    private function debugData($data, $gravityData, $v2Data, $ieData)
    {
        $grouped = [];
        foreach ($data as $item) {
            $refNo = $item['reference_no'];
            $grouped[$refNo][] = $item;
        }

        // Filter only duplicates
        $exactDuplicates = array_filter($grouped, function ($group) {
            return count($group) > 1;
        });

        // Group by last two parts
        $groupedByPattern = [];
        foreach ($data as $item) {
            $refNo = $item['reference_no'];
            $parts = explode('/', $refNo);

            if (count($parts) >= 2) {
                $lastTwoParts = '/' . $parts[count($parts) - 2] . '/' . $parts[count($parts) - 1];
                $groupedByPattern[$lastTwoParts][] = $item;
            }
        }

        // Filter only pattern duplicates
        $patternDuplicates = array_filter($groupedByPattern, function ($group) {
            return count($group) > 1;
        });

        dd([
            'stats' => [
                'total_imohon' => count($gravityData),
                'total_v2' => count($v2Data),
                'total_interedge' => count($ieData),
                'total_merged' => count($data),
                'total_duplicates' => count($exactDuplicates) + count($patternDuplicates),
                'unique_references' => count($grouped),
                'exact_duplicate' => count($exactDuplicates),
                'reference_no_duplicate' => count($patternDuplicates)
            ],
            'exact_duplicates' => $exactDuplicates,
            'reference_no_duplicates' => $patternDuplicates
        ]);
    }
}