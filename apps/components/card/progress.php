<?php
$id = $id ?? 'progressMigration'; // Default ID if not provided
$title = $title ?? 'Progress'; // Default title if not provided
$currentValue = $currentValue ?? '0'; // Default this week value if not provided
$totalValue = $totalValue ?? '0'; // Default last week value if
$data = $data ?? '0'; // Default progress data if not provided
$label = $label ?? 'Median Ratio'; // Default label if not provided
?>

<?= push('scripts') ?>
<script>
    var colors = ["#ff6c2f"];
    var options = {
        chart: {
            height: 280,
            type: 'radialBar',
        },
        plotOptions: {
            radialBar: {
                startAngle: -135,
                endAngle: 135,
                dataLabels: {
                    name: {
                        fontSize: '16px',
                        color: undefined,
                        offsetY: 120
                    },
                    value: {
                        offsetY: 76,
                        fontSize: '22px',
                        color: undefined,
                        formatter: function(val) {
                            return val + "%";
                        }
                    }
                },
                track: {
                    background: "rgba(170,184,197, 0.4)",
                    margin: 0
                },
            }
        },
        fill: {
            gradient: {
                enabled: true,
                shade: 'dark',
                shadeIntensity: 0.2,
                inverseColors: false,
                opacityFrom: 1,
                opacityTo: 1,
                stops: [0, 50, 65, 91]
            },
        },
        stroke: {
            dashArray: 4
        },
        colors: colors,
        series: ['<?= $data ?>'],
        labels: ['<?= $label ?>'],
        responsive: [{
            breakpoint: 380,
            options: {
                chart: {
                    height: 280
                }
            }
        }]
    }
    var chart = new ApexCharts(document.querySelector("#<?= $id ?>"), options )
    chart.render();
</script>
<?= endpush() ?>

<div class="card">
    <div class="card-body">
        <h5 class="card-title"><?= $title ?></h5>
        <div id="<?= $id ?>" class="apex-charts mb-2 mt-n2"></div>
        <div class="row text-center">
            <div class="col-6">
                <p class="text-muted mb-2">Current</p>
                <h3 class="text-dark"><?= $currentValue ?></h3>
            </div> <!-- end col -->
            <div class="col-6">
                <p class="text-muted mb-2">Total</p>
                <h3 class="text-dark"><?= $totalValue ?></h3>
            </div> <!-- end col -->
        </div> <!-- end row -->
    </div>
</div>