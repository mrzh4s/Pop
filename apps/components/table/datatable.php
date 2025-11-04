<?php
$title = $title ?? '';
$columns = $columns ?? [];
$data = $data ?? [];
$actions = $actions ?? '';
$id = $id ?? 'datatable-' . uniqid();
$pagination = $pagination ?? 10;
$search = $search ?? true;
$sort = $sort ?? true;
$tooltips = $tooltips ?? [];
?>

<div class="card">
    <?php if ($title || $actions || $search): ?>
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <?php if ($title): ?>
                    <h4 class="mb-0"><?= htmlspecialchars($title) ?></h4>
                <?php endif; ?>
                
                <div class="d-flex align-items-center gap-3">
                    <?php if ($search): ?>
                        <div class="position-relative">
                            <input type="text" 
                                   id="<?= $id ?>-search" 
                                   class="form-control" 
                                   placeholder="Type a keyword..." 
                                   style="min-width: 250px;">
                            <i class="fad fa-search position-absolute top-50 end-0 translate-middle-y me-2 text-muted"></i>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($actions): ?>
                        <div><?= $actions ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
    <div class="card-body p-0">
        <div id="<?= $id ?>"></div>
    </div>
</div>

<?php push('styles'); ?>
<link rel="stylesheet" href="<?= asset('vendor/gridjs/theme/mermaid.min.css') ?>">
<?php endpush(); ?>

<?php push('scripts'); ?>
<script src="<?= asset('vendor/gridjs/gridjs.umd.js') ?>"></script>
<script>
    (function() {
        // Debug flag - set to true to enable debugging
        const DEBUG = <?= isset($_GET['dd']) ? 'true' : 'false' ?>;
        
        // Debug helper function
        function debug(label, data) {
            if (DEBUG) {
                console.group(`🐛 DataTable Debug: ${label}`);
                console.log(data);
                console.groupEnd();
            }
        }
        
        // Define columns configuration
        let columns = <?= json_encode($columns) ?>;
        const tooltips = <?= json_encode($tooltips) ?>;
        const originalData = <?= json_encode($data) ?>;
        
        debug('Initial Configuration', {
            columns: columns,
            tooltips: tooltips,
            dataCount: originalData.length,
            sampleData: originalData.slice(0, 2)
        });
        
        // Get list of columns used as tooltip sources (to hide them)
        const tooltipSourceColumns = Object.values(tooltips);
        debug('Tooltip Source Columns to Hide', tooltipSourceColumns);
        
        // Create column mapping for original indices
        const columnMapping = {};
        columns.forEach((col, index) => {
            columnMapping[col.id] = index;
        });
        debug('Column Mapping', columnMapping);
        
        // Parse formatter functions from strings and add tooltip formatters
        const parsedColumns = columns.map((col, colIndex) => {
            debug(`Processing Column: ${col.name} (${col.id})`, {
                column: col,
                index: colIndex,
                willBeHidden: tooltipSourceColumns.includes(col.id),
                hasTooltip: !!tooltips[col.id]
            });
            
            // Hide columns that are used as tooltip sources
            if (tooltipSourceColumns.includes(col.id)) {
                col.hidden = true;
                debug(`Column Hidden: ${col.name}`, col);
                return col;
            }
            
            // Check if this column has a tooltip configuration
            const tooltipSourceId = tooltips[col.id];
            
            if (tooltipSourceId) {
                debug(`Adding Tooltip to Column: ${col.name}`, {
                    targetColumn: col.id,
                    tooltipSource: tooltipSourceId,
                    tooltipSourceIndex: columnMapping[tooltipSourceId]
                });
                
                // Store original formatter if exists
                const originalFormatterStr = col.formatter;
                
                // Find the tooltip source column index
                const tooltipColIndex = columnMapping[tooltipSourceId];
                
                // Create new formatter that adds tooltip
                col.formatter = function(cell, row) {
                    debug(`Formatter Called for ${col.name}`, {
                        cell: cell,
                        cellType: typeof cell,
                        rowCells: row.cells.map(c => c.data),
                        tooltipColIndex: tooltipColIndex,
                        hasOriginalFormatter: !!originalFormatterStr
                    });
                    
                    let cellContent = '';
                    
                    // If there was an original formatter, apply it first
                    if (originalFormatterStr && typeof originalFormatterStr === 'string') {
                        try {
                            debug(`Applying Original Formatter`, {
                                formatterString: originalFormatterStr
                            });
                            
                            // For gridjs.html formatters, extract the HTML template directly
                            if (originalFormatterStr.includes('gridjs.html')) {
                                // Extract the template string from the formatter
                                const templateMatch = originalFormatterStr.match(/gridjs\.html\(`([^`]+)`\)/);
                                if (templateMatch) {
                                    let template = templateMatch[1];
                                    // Replace ${cell} with actual cell value
                                    cellContent = template.replace(/\$\{cell\}/g, cell);
                                    debug(`Extracted HTML template and substituted cell value`, cellContent);
                                } else {
                                    // Fallback: execute the formatter but handle the result
                                    const formatterFunc = eval(`(${originalFormatterStr})`);
                                    const formatted = formatterFunc(cell, row);
                                    cellContent = String(cell); // Use raw cell as fallback
                                    debug(`Could not extract template, using raw cell`, cellContent);
                                }
                            } else {
                                // Regular formatter (not gridjs.html)
                                const formatterFunc = eval(`(${originalFormatterStr})`);
                                const formatted = formatterFunc(cell, row);
                                cellContent = String(formatted);
                                debug(`Regular formatter result`, cellContent);
                            }
                        } catch(e) {
                            console.error('Error parsing original formatter:', e);
                            cellContent = String(cell);
                            debug(`Formatter Error - Using raw cell`, cellContent);
                        }
                    } else {
                        cellContent = String(cell);
                        debug(`No original formatter - Using raw cell`, cellContent);
                    }
                    
                    // Get tooltip content from the correct cell index
                    if (typeof tooltipColIndex !== 'undefined' && row.cells[tooltipColIndex]) {
                        const tooltipContent = row.cells[tooltipColIndex].data;
                        
                        debug(`Adding Tooltip`, {
                            tooltipContent: tooltipContent,
                            cellContent: cellContent
                        });
                        
                        // Escape HTML in tooltip content
                        const escapedTooltip = String(tooltipContent || '')
                            .replace(/&/g, '&amp;')
                            .replace(/</g, '&lt;')
                            .replace(/>/g, '&gt;')
                            .replace(/"/g, '&quot;')
                            .replace(/'/g, '&#039;');
                        
                        // Wrap with tooltip
                        const result = gridjs.html(`
                            <span data-bs-toggle="tooltip" 
                                  data-bs-placement="top" 
                                  title="${escapedTooltip}">
                                ${cellContent}
                            </span>
                        `);
                        
                        debug(`Final Tooltip Result`, result);
                        return result;
                    } else {
                        debug(`No tooltip - returning plain content`, cellContent);
                    }
                    
                    return gridjs.html(cellContent);
                };
            } else if (col.formatter && typeof col.formatter === 'string') {
                // Parse regular formatter functions
                try {
                    col.formatter = eval(`(${col.formatter})`);
                    debug(`Parsed Regular Formatter for ${col.name}`, 'SUCCESS');
                } catch(e) {
                    console.error('Error parsing formatter:', e);
                    debug(`Formatter Parse Error for ${col.name}`, e);
                    delete col.formatter;
                }
            }
            
            return col;
        });
        
        debug('Final Parsed Columns', parsedColumns);

        // Initialize Grid.js
        debug('Initializing Grid.js', {
            columnsCount: parsedColumns.length,
            dataCount: originalData.length,
            pagination: <?= $pagination ?>,
            search: false,
            sort: <?= $sort ? 'true' : 'false' ?>,
            customSearch: true,
        });

        const grid = new gridjs.Grid({
            columns: parsedColumns,
            data: originalData,
            pagination: {
                limit: <?= $pagination ?>,
                summary: true
            },
            search: false,
            sort: <?= $sort ? 'true' : 'false' ?>,
            resizable: true,
            className: {
                table: 'table table-bordered table-hover',
                thead: 'table-light',
                pagination: 'pagination justify-content-end'
            }
        }).render(document.getElementById("<?= $id ?>"));
        
        debug('Grid.js Initialized', grid);
        
        <?php if ($search): ?>
        // Custom search functionality
        const searchInput = document.getElementById('<?= $id ?>-search');
        if (searchInput) {
            let searchTimeout;
            searchInput.addEventListener('input', function(e) {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    const searchTerm = e.target.value.toLowerCase().trim();
                    debug('Custom Search', { searchTerm: searchTerm });
                    
                    if (searchTerm === '') {
                        // Show all data
                        grid.updateConfig({
                            data: originalData
                        }).forceRender();
                    } else {
                        // Filter data
                        const filteredData = originalData.filter(row => {
                            return row.some(cell => {
                                return String(cell).toLowerCase().includes(searchTerm);
                            });
                        });
                        
                        debug('Filtered Results', { 
                            originalCount: originalData.length,
                            filteredCount: filteredData.length 
                        });
                        
                        grid.updateConfig({
                            data: filteredData
                        }).forceRender();
                    }
                    
                    // Reinitialize tooltips after search
                    setTimeout(initTooltips, 100);
                }, 300); // Debounce search
            });
        }
        <?php endif; ?>
        
        // Initialize Bootstrap tooltips after grid renders
        grid.on('ready', function() {
            debug('Grid Ready Event Fired', 'Initializing tooltips...');
            initTooltips();
        });
        
        // Re-initialize tooltips on page change and search
        grid.on('pageChange', function() {
            debug('Page Change Event', 'Re-initializing tooltips...');
            setTimeout(initTooltips, 100);
        });
        
        // Re-initialize tooltips on search
        grid.on('search', function() {
            debug('Search Event', 'Re-initializing tooltips...');
            setTimeout(initTooltips, 100);
        });
        
        function initTooltips() {
            // Dispose old tooltips first
            const oldTooltips = document.querySelectorAll('#<?= $id ?> [data-bs-toggle="tooltip"]');
            debug('Disposing Old Tooltips', {
                count: oldTooltips.length,
                elements: Array.from(oldTooltips)
            });
            
            oldTooltips.forEach(el => {
                const tooltip = bootstrap.Tooltip.getInstance(el);
                if (tooltip) tooltip.dispose();
            });
            
            // Initialize new tooltips
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('#<?= $id ?> [data-bs-toggle="tooltip"]'));
            debug('Initializing New Tooltips', {
                count: tooltipTriggerList.length,
                elements: tooltipTriggerList
            });
            
            const tooltipInstances = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
            
            debug('Tooltips Initialized', {
                instancesCount: tooltipInstances.length,
                instances: tooltipInstances
            });
        }
        
        // Global debug access
        if (DEBUG) {
            window.datatableDebug = {
                grid: grid,
                columns: parsedColumns,
                tooltips: tooltips,
                data: originalData,
                reinitTooltips: initTooltips
            };
            console.log('🐛 Debug mode enabled! Access window.datatableDebug for debugging tools');
            console.log('📝 Add ?debug=1 to URL to enable debug mode');
        }
    })();
</script>
<?php endpush(); ?>