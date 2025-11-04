<?php
$title = $title ?? 'Activity Log';
$data = $data ?? [];
$emptyMessage = $emptyMessage ?? 'No activity logged yet';

// Button configuration
$showButton = $showButton ?? false;
$buttonText = $buttonText ?? 'Export Log';
$buttonLink = $buttonLink ?? '#!';
$buttonIcon = $buttonIcon ?? 'bx bx-download';

// Pagination configuration
$showPagination = $showPagination ?? true;
$currentPage = $currentPage ?? 1;
$totalRecords = $totalRecords ?? 0;
$perPage = $perPage ?? 10;
$paginationLink = $paginationLink ?? '#';

// Column configuration
$columns = $columns ?? ['user', 'action', 'module', 'details', 'ip_address', 'timestamp'];

// Column labels for headers
$columnLabels = $columnLabels ?? [
    'user' => 'User',
    'action' => 'Action',
    'module' => 'Module',
    'details' => 'Details',
    'ip_address' => 'IP Address',
    'timestamp' => 'Date & Time',
];

// Status/Action badge configuration
$badgeConfig = $badgeConfig ?? [
    'Created' => 'bg-success',
    'Updated' => 'bg-primary',
    'Deleted' => 'bg-danger',
    'Viewed' => 'bg-info',
    'Exported' => 'bg-warning',
    'Login' => 'bg-success',
    'Logout' => 'bg-secondary',
];

// Specify which column should be treated as badge
$badgeColumn = $badgeColumn ?? 'action';

// Date format
$dateFormat = $dateFormat ?? 'd M Y, h:i A';

// Renamed functions to avoid conflicts
if (!function_exists('getActivityBadgeClass')) {
    function getActivityBadgeClass($action, $config) {
        return $config[$action] ?? 'bg-secondary';
    }
}

if (!function_exists('formatActivityDateTime')) {
    function formatActivityDateTime($datetime, $format = 'd M Y, h:i A') {
        if (empty($datetime)) return '-';
        return date($format, strtotime($datetime));
    }
}

// Calculate pagination
$totalPages = $totalRecords > 0 ? ceil($totalRecords / $perPage) : 1;
$start = ($currentPage - 1) * $perPage + 1;
$end = min($currentPage * $perPage, $totalRecords);
?>

<div class="card">
    <div class="card-body">
        <div class="d-flex align-items-center justify-content-between">
            <h4 class="card-title">
                <?= htmlspecialchars($title) ?>
            </h4>

            <?php if($showButton): ?>
            <a href="<?= htmlspecialchars($buttonLink) ?>" class="btn btn-sm btn-soft-primary">
                <?= component('ui.icon', ['icon' => $buttonIcon, 'class' => 'me-1']) ?>
                <span class="align-middle"><?= htmlspecialchars($buttonText) ?></span>
            </a>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="table-responsive table-centered">
        <?php if(empty($data)): ?>
            <div class="text-center text-muted py-5">
                <?= htmlspecialchars($emptyMessage) ?>
            </div>
        <?php else: ?>
        <table class="table mb-0">
            <thead class="bg-light bg-opacity-50">
                <tr>
                    <?php foreach($columns as $index => $column): ?>
                        <th class="<?= $index === 0 ? 'ps-3' : '' ?>">
                            <?= htmlspecialchars($columnLabels[$column] ?? ucfirst(str_replace('_', ' ', $column))) ?>
                        </th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach($data as $row): ?>
                <?php
                // Support both indexed and associative arrays
                if (is_array($row) && !isset($row[$columns[0]])) {
                    $values = array_values($row);
                    $row = array_combine($columns, $values);
                }
                ?>
                <tr>
                    <?php foreach($columns as $index => $column): ?>
                        <td class="<?= $index === 0 ? 'ps-3' : '' ?>">
                            <?php 
                            $value = $row[$column] ?? '-';
                            
                            // Format based on column type
                            if ($column === $badgeColumn) {
                                // Show as badge
                                $badgeClass = getActivityBadgeClass($value, $badgeConfig);
                                echo '<span class="badge ' . $badgeClass . '">' . htmlspecialchars($value) . '</span>';
                            } 
                            elseif ($column === 'timestamp' || strpos($column, 'date') !== false || strpos($column, 'time') !== false) {
                                // Format datetime
                                echo htmlspecialchars(formatActivityDateTime($value, $dateFormat));
                            }
                            elseif ($column === 'user' && isset($row['user_link'])) {
                                // User as link
                                echo '<a href="' . htmlspecialchars($row['user_link']) . '">' . htmlspecialchars($value) . '</a>';
                            }
                            else {
                                // Regular text
                                echo htmlspecialchars($value);
                            }
                            ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <?php if($showPagination && $totalRecords > 0): ?>
    <div class="card-footer border-top">
        <div class="row g-3">
            <div class="col-sm">
                <div class="text-muted">
                    Showing
                    <span class="fw-semibold"><?= number_format($start) ?></span>
                    to
                    <span class="fw-semibold"><?= number_format($end) ?></span>
                    of
                    <span class="fw-semibold"><?= number_format($totalRecords) ?></span>
                    records
                </div>
            </div>

            <div class="col-sm-auto">
                <ul class="pagination m-0">
                    <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
                        <a href="<?= $currentPage > 1 ? htmlspecialchars($paginationLink . '?page=' . ($currentPage - 1)) : '#' ?>" class="page-link">
                            <i class="bx bx-left-arrow-alt"></i>
                        </a>
                    </li>
                    
                    <?php
                    // Show max 5 page numbers
                    $startPage = max(1, $currentPage - 2);
                    $endPage = min($totalPages, $startPage + 4);
                    $startPage = max(1, $endPage - 4);
                    
                    for($i = $startPage; $i <= $endPage; $i++):
                    ?>
                    <li class="page-item <?= $i === $currentPage ? 'active' : '' ?>">
                        <a href="<?= htmlspecialchars($paginationLink . '?page=' . $i) ?>" class="page-link">
                            <?= $i ?>
                        </a>
                    </li>
                    <?php endfor; ?>
                    
                    <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
                        <a href="<?= $currentPage < $totalPages ? htmlspecialchars($paginationLink . '?page=' . ($currentPage + 1)) : '#' ?>" class="page-link">
                            <i class="bx bx-right-arrow-alt"></i>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>