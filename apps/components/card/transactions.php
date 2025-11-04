<?php
$title = $title ?? 'Recent Transactions';
$data = $data ?? [];
$maxHeight = $maxHeight ?? '398px';
$emptyMessage = $emptyMessage ?? 'No transactions available';
$currency = $currency ?? '$';
$dateFormat = $dateFormat ?? null;

// Column configuration - defines which fields to display and in what order
$columns = $columns ?? ['date', 'amount', 'type', 'description'];

// Column labels (optional - for showing headers)
$showHeaders = $showHeaders ?? false;
$columnLabels = $columnLabels ?? [];

// Badge configuration
$badgeConfig = $badgeConfig ?? [
    'Cr' => 'bg-success',
    'Dr' => 'bg-danger',
    'Credit' => 'bg-success',
    'Debit' => 'bg-danger',
    'Pending' => 'bg-warning',
    'Failed' => 'bg-danger',
    'Completed' => 'bg-success',
    'On Going' => 'bg-warning',
];

// Specify which column should be treated as badge
$badgeColumn = $badgeColumn ?? 'type';

// Specify which column should be treated as currency
$currencyColumn = $currencyColumn ?? 'amount';

// Callback for custom rendering
$onRenderRow = $onRenderRow ?? null;

function getBadgeClass($type, $config) {
    return $config[$type] ?? 'bg-secondary';
}

function formatCurrency($amount, $currency = '$') {
    if (!is_numeric($amount)) return $amount;
    return $currency . number_format($amount, 2);
}

function formatDate($date, $format = null) {
    if (!$format) return $date;
    return date($format, strtotime($date));
}
?>

<div class="card card-height-100">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4 class="card-title"><?= htmlspecialchars($title) ?></h4>
    </div>
    <div class="card-body p-0">
        <?php if(empty($data)): ?>
            <div class="text-center text-muted py-5">
                <?= htmlspecialchars($emptyMessage) ?>
            </div>
        <?php else: ?>
        <div class="px-3" data-simplebar style="max-height: <?= htmlspecialchars($maxHeight) ?>;">
            <table class="table table-hover mb-0 table-centered">
                <?php if($showHeaders): ?>
                <thead>
                    <tr>
                        <?php foreach($columns as $column): ?>
                            <th><?= htmlspecialchars($columnLabels[$column] ?? ucfirst($column)) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <?php endif; ?>
                <tbody>
                    <?php foreach($data as $transaction): ?>
                    <?php
                    // Support both indexed and associative arrays
                    if (is_array($transaction) && !isset($transaction[$columns[0]])) {
                        $values = array_values($transaction);
                        $transaction = array_combine($columns, $values);
                    }
                    
                    // Custom row rendering
                    if (is_callable($onRenderRow)) {
                        echo $onRenderRow($transaction);
                        continue;
                    }
                    ?>
                    <tr>
                        <?php foreach($columns as $column): ?>
                            <td>
                                <?php 
                                $value = $transaction[$column] ?? '';
                                
                                // Check if this column should be formatted as date
                                if ($column === 'date' || strpos($column, 'date') !== false) {
                                    echo htmlspecialchars(formatDate($value, $dateFormat));
                                }
                                // Check if this column should be formatted as currency
                                elseif ($column === $currencyColumn) {
                                    echo is_numeric($value) ? formatCurrency($value, $currency) : htmlspecialchars($value);
                                }
                                // Check if this column should be shown as badge
                                elseif ($column === $badgeColumn) {
                                    $badgeClass = getBadgeClass($value, $badgeConfig);
                                    echo '<span class="badge ' . $badgeClass . '">' . htmlspecialchars($value) . '</span>';
                                }
                                // Regular text
                                else {
                                    echo htmlspecialchars($value);
                                }
                                ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>