<?php extend('layouts.app'); ?>

<?php section('title'); ?>
<?= app_name() . ' - ' . $title ?>
<?php endsection(); ?>

<?php section('page-title'); ?>
<?= $title ?>
<?php endsection(); ?>

<?php section('content'); ?>
<?php
// Calculate statistics from the data
$stats = [
    'imohon' => $stats['total_imohon'],
    'v2' => $stats['total_v2'],
    'completed' => 0,
    'in_progress' => 0,
];

?>

<!-- Statistics Cards -->
<div class="row">

    <div class="col-md-3 col-sm-6 ">
        <?= component('card.counter', [
            'icon' => 'solar-system',
            'color' => 'info',
            'title' => 'iMohon',
            'value' => number_format($stats['imohon'])
        ]) ?>
    </div>

    <div class="col-md-3 col-sm-6">
        <?= component('card.counter', [
            'icon' => 'browser',
            'color' => 'primary',
            'title' => 'System V2',
            'value' => number_format($stats['v2'])
        ]) ?>
    </div>


    <div class="col-md-3 col-sm-6">
        <?= component('card.counter', [
            'icon' => 'circle-check',
            'color' => 'success',
            'title' => 'Completed',
            'value' => number_format($stats['completed'])
        ]) ?>
    </div>

    <div class="col-md-3 col-sm-6">
        <?= component('card.counter', [
            'icon' => 'hourglass-clock',
            'color' => 'warning',
            'title' => 'In Progress',
            'value' => number_format($stats['in_progress'])
        ]) ?>
    </div>
</div>

<!-- DataTable -->
<div class="row">
    <div class="col-xl-12">
        <?= component('table.datatable', [
            'title' => 'Migration Application List',
            'data' => $data,
            'columns' => $columns,
            'tooltips' => ['reference_no' => 'title']
        ]) ?>
    </div>
</div>
<?php endsection(); ?>