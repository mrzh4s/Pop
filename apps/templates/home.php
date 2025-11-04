<?php extend('layouts.app'); ?>

<?php section('title'); ?>
<?= app_name().' - '. $title ?>
<?php endsection(); ?>

<?php section('page-title'); ?>
<?= $title ?>
<?php endsection(); ?>

<?php section('content'); ?>
<!-- Start here.... -->
<div class="row">
    <div class="col-xxl-12">
        <div class="row">
            <div class="col-md-3">
                <?= component('card.counter', ['icon' => 'file-import', 'title' => 'Pending Migration']) ?>
            </div> <!-- end col -->
            <div class="col-md-3">
                <?= component('card.counter', ['icon' => 'file-import', 'title' => 'Completed Migration']) ?>
            </div> <!-- end col -->
            <div class="col-md-3">
                <?= component('card.counter', ['icon' => 'file-import', 'title' => 'Corrupted Data']) ?>
            </div> <!-- end col -->
            <div class="col-md-3">
                <?= component('card.counter', ['icon' => 'file-import', 'title' => 'Overall']) ?>
            </div> <!-- end col -->
        </div> <!-- end row -->
    </div> <!-- end col -->
</div> <!-- end row -->

<div class="row">
    <div class="col-lg-4">
    <?= component('card.progress', [
        'id' => 'progressMigration', 
        'title' => 'Migration Progress', 
        'data' => '80', 
        'label' => 'Migration Progress', 
        'currentValue' => '80', 
        'totalValue' => '100', 
    ]) ?>
    </div> <!-- end col -->  
    <div class="col-lg-4">
        <?php
        $migrations = [
            ['REF-001', 'CRM System', 'John Doe', 'Completed'],
            ['REF-002', 'ERP System', 'Jane Smith', 'In Progress'],
            ['REF-003', 'HR System', 'Bob Johnson', 'Pending'],
            ['REF-004', 'Finance System', 'Alice Brown', 'Completed'],
            ['REF-005', 'Inventory System', 'Charlie Wilson', 'Failed'],
        ];
        ?>
        <?= component('card.recent', [
            'title' => 'Recent Migrations',
            'headers' => ['Reference No', 'System', 'User', 'Status'],
            'data' => $migrations, // from database
            'columnConfig' => [
                ['type' => 'link', 'class' => 'ps-3'],
                ['type' => 'text'],
                ['type' => 'text'],
                ['type' => 'badge', 'class' => fn($v) => match($v) {
                    'Completed' => 'badge badge-soft-success',
                    'In Progress' => 'badge badge-soft-warning',
                    'Pending' => 'badge badge-soft-secondary',
                    'Failed' => 'badge badge-soft-danger',
                    default => 'badge badge-soft-info'
                }]
            ]
        ]) ?>
    </div> <!-- end col -->

    <div class="col-xl-4">
        <?php
            $transactions = [
                ['date' => '2025-10-01', 'reference_no' => 'REF-001', 'type' => 'Completed', 'user' => 'John Doe'],
                ['date' => '2025-09-29', 'reference_no' => 'REF-002', 'type' => 'On Going', 'user' => 'John Doe'],
                ['date' => '2025-10-01', 'reference_no' => 'REF-003', 'type' => 'Completed', 'user' => 'John Doe'],
                ['date' => '2025-10-02', 'reference_no' => 'REF-004', 'type' => 'Completed', 'user' => 'John Doe'],
            ];
        ?>
        <?= component('card.transactions', [
            'title' => 'Recent Transactions',
            'maxHeight' => '400px',
            'data' => $transactions,
            'columns' => ['date', 'reference_no', 'user', 'type'],
            'badgeColumn' => 'type',
            'badgeConfig' => [
                'Completed' => 'bg-success',
                'On Going' => 'bg-warning',
                'Failed' => 'bg-danger',
            ],
        ]) ?>
    </div>

</div> <!-- end row -->

<div class="row">
    <div class="col">
<?php
$migrationActivities = [
    [
        'user' => 'Admin User',
        'action' => 'Created',
        'module' => 'Migration',
        'details' => 'Started migration REF-001 from i-mohon to KITER',
        'ip_address' => '192.168.1.1',
        'timestamp' => '2024-10-02 10:00:00'
    ],
    [
        'user' => 'John Doe',
        'action' => 'Updated',
        'module' => 'Migration',
        'details' => 'Updated status of REF-001 to In Progress',
        'ip_address' => '192.168.1.5',
        'timestamp' => '2024-10-02 10:30:00'
    ],
    [
        'user' => 'Jane Smith',
        'action' => 'Updated',
        'module' => 'Migration',
        'details' => 'Completed migration REF-001',
        'ip_address' => '192.168.1.10',
        'timestamp' => '2024-10-02 15:00:00'
    ],
];
?>

<?= component('card.activity-log', [
    'title' => 'Migration Activity Log',
    'data' => $migrationActivities,
    'badgeConfig' => [
        'Created' => 'bg-success',
        'Updated' => 'bg-primary',
        'Completed' => 'bg-success',
        'Failed' => 'bg-danger',
    ],
    'showButton' => true,
    'buttonText' => 'View All',
    'buttonLink' => '/migrations/activity',
    'buttonIcon' => 'list-ul',
]);
?>

        <!-- end card -->
    </div>
    <!-- end col -->
</div> <!-- end row -->
<?php endsection(); ?>