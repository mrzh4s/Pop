<?php extend('layouts.app'); ?>

<?php section('title'); ?>
<?= app_name().' - '. $title ?>
<?php endsection(); ?>

<?php section('page-title'); ?>
<?= $title ?>
<?php endsection(); ?>

<?php section('content'); ?>
<?php endsection(); ?>