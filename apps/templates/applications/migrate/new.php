<?php extend('layouts.app'); ?>

<?php section('title'); ?>
<?= app_name() . ' - ' . $title ?>
<?php endsection(); ?>

<?php section('page-title'); ?>
<?= $title ?>
<?php endsection(); ?>
<?= $system ?>
<?php section('content'); ?>

<?php if($system === 'Imohon'): ?>
<?= partial('imohon', ['data' => $data, 'entryId' => $entryId]); ?>
<?php else: ?>
<?= partial('v2', ['data' => $data, 'entryId' => $entryId]); ?>
<?php endif; ?>

<?php endsection(); ?>