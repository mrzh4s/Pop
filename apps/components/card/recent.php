<?php
$title = $title ?? 'Recent Migrations';
$headers = $headers ?? ['Project', 'Files', 'Progress'];
$data = $data ?? [];
$showViewAll = $showViewAll ?? true;
$viewAllLink = $viewAllLink ?? '#';
$emptyMessage = $emptyMessage ?? 'No data available';

// Column configuration with more options
$columnConfig = $columnConfig ?? [
    [
        'type' => 'link',
        'class' => 'ps-3',
        'href' => '#', // Can be dynamic: fn($row) => "/project/{$row['id']}"
    ],
    [
        'type' => 'text',
        'class' => '',
        'format' => null, // Can be: 'number', 'date', 'currency', or callable
    ],
    [
        'type' => 'badge',
        'class' => fn($value) => match (strtolower($value)) {
            'completed' => 'badge badge-soft-success',
            'in progress' => 'badge badge-soft-warning',
            'pending' => 'badge badge-soft-secondary',
            default => 'badge badge-soft-info'
        }
    ],
];

// Helper function to format values
function formatValue($value, $format)
{
    if (is_callable($format)) {
        return $format($value);
    }

    return match ($format) {
        'number' => number_format($value),
        'currency' => '$' . number_format($value, 2),
        'date' => date('M d, Y', strtotime($value)),
        default => $value
    };
}
?>

<div class="card card-height-100">
    <div class="card-header d-flex align-items-center justify-content-between gap-2">
        <h4 class="card-title flex-grow-1"><?= htmlspecialchars($title) ?></h4>

        <?php if ($showViewAll): ?>
            <a href="<?= htmlspecialchars($viewAllLink) ?>" class="btn btn-sm btn-soft-primary">View All</a>
        <?php endif; ?>
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-nowrap table-centered m-0">
            <thead class="bg-light bg-opacity-50">
                <tr>
                    <?php foreach ($headers as $header): ?>
                        <th class="text-muted"><?= htmlspecialchars($header) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($data)): ?>
                    <tr>
                        <td colspan="<?= count($headers) ?>" class="text-center text-muted py-4">
                            <?= htmlspecialchars($emptyMessage) ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($data as $row): ?>
                        <tr>
                            <?php
                            $values = is_array($row) ? array_values($row) : [$row];
                            foreach ($values as $index => $value):
                                $config = $columnConfig[$index] ?? ['type' => 'text', 'class' => ''];
                                // Fix: Check if 'class' exists and handle callable
                                $tdClass = '';
                                if (isset($config['class'])) {
                                    $tdClass = is_callable($config['class']) ? '' : $config['class'];
                                }
                            ?>
                                <td class="<?= $tdClass ?>">
                                    <?php
                                    $displayValue = isset($config['format'])
                                        ? formatValue($value, $config['format'])
                                        : $value;
                                    ?>

                                    <?php if ($config['type'] === 'link'): ?>
                                        <a href="<?= htmlspecialchars($config['href'] ?? '#') ?>" class="text-muted">
                                            <?= htmlspecialchars($displayValue) ?>
                                        </a>
                                    <?php elseif ($config['type'] === 'badge'): ?>
                                        <?php
                                        $badgeClass = 'badge badge-soft-info'; // Default badge class
                                        if (isset($config['class'])) {
                                            $badgeClass = is_callable($config['class'])
                                                ? $config['class']($value)
                                                : $config['class'];
                                        }
                                        ?>
                                        <span class="<?= $badgeClass ?>">
                                            <?= htmlspecialchars($displayValue) ?>
                                        </span>
                                    <?php else: ?>
                                        <?= htmlspecialchars($displayValue) ?>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>