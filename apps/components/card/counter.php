<?php

$card_icon = !empty($icon) ? $icon : 'circle-question'; // Default icon if not provided
$color = $color ?? 'primary'; // Default background color if not provided
$title = $title ?? 'Total'; // Default title if not provided
$value = $value ?? '0'; // Default value if not provided
$percentage = $percentage ?? '0'; // Default percentage if not provided
$percentage_direction = $percentage_direction ?? 'up'; // 'up' or 'down'
$percentage_class = $percentage_direction === 'up' ? 'text-success' : 'text-danger';
$percentage_icon = $percentage_direction === 'up' ? 'arrow-trend-up' : 'arrow-trend-down';
$show_view = $show_view ?? false;
$show_link = $show_link ?? '#!'; // Default link if not provided

?>

<div class="card overflow-hidden">
    <div class="card-body">
        <div class="row">
            <div class="col-6">
                <div class="d-flex align-items-center justify-content-center avatar-md bg-soft-<?= $color ?> rounded">
                    <?= component('ui.icon', ['icon' => $card_icon, 'class' => 'fs-24 text-'.$color]) ?>
                </div>
            </div> <!-- end col -->
            <div class="col-6 text-end">
                <p class="text-muted mb-0 text-truncate"><?= $title ?></p>
                <h3 class="text-dark mt-1 mb-0"><?= $value ?></h3>
            </div> <!-- end col -->
        </div> <!-- end row-->
    </div> <!-- end card body -->
    <?php if($show_view || $percentage !== '0'):?>
    <div class="card-footer py-2 bg-light bg-opacity-50">
        <div class="d-flex align-items-center justify-content-between">
            <div>
                <?php
                if($percentage !== '0'):
                ?>
                <span class="<?= $percentage_class ?>"> <?= component('ui.icon', ['icon' => $percentage_icon, 'class' => 'fs-12']) ?> <?= $percentage ?>%</span>
                <span class="text-muted ms-1 fs-12">Last Week</span>
                <?php
                endif;
                ?>
            </div>
            <?php if($show_view): ?>
            <a href="<?= $show_link ?>" class="text-reset fw-semibold fs-12">View More</a>
            <?php endif; ?>
        </div>
    </div> <!-- end card body -->
    <?php endif; ?>
</div> <!-- end card -->