<?php

if (!function_exists('dd')) {
    /**
     * Dump and Die function for debugging
     * 
     * Usage: dd($var1, $var2, ...);
     */
    function dd(...$args)
    {
        // Clear output buffer to remove any previous HTML
        while (ob_get_level()) {
            ob_end_clean();
        }

        // Get backtrace to show where dd() was called
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0];
        $file = isset($backtrace['file']) ? $backtrace['file'] : 'unknown';
        $line = isset($backtrace['line']) ? $backtrace['line'] : 'unknown';

        echo '<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Debug</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { 
                font-family: "Consolas", "Monaco", monospace; 
                background: #000;
                color: #ddd;
                padding: 20px;
                line-height: 1.6;
            }
            .dd-header {
                color: #888;
                font-size: 12px;
                margin-bottom: 15px;
                padding-bottom: 10px;
                border-bottom: 1px solid #333;
            }
            .dd-toggle { 
                cursor: pointer; 
                user-select: none;
            }
            .dd-toggle:hover { opacity: 0.7; }
            .dd-arrow {
                display: inline-block;
                transition: transform 0.2s;
                margin-right: 5px;
            }
            .dd-arrow.expanded { transform: rotate(90deg); }
            .dd-hidden { 
                display: none; 
                margin-left: 20px;
                margin-top: 3px;
            }
            .dd-depth-0 { color: #ff6b6b; }
            .dd-depth-1 { color: #feca57; }
            .dd-depth-2 { color: #48dbfb; }
            .dd-depth-3 { color: #1dd1a1; }
            .dd-depth-4 { color: #ff9ff3; }
            .dd-depth-5 { color: #f368e0; }
            .dd-depth-6 { color: #00d2d3; }
            .dd-value { color: #aaa; }
            .dd-key { color: #54a0ff; }
        </style>
    </head>
    <body>
        <div class="dd-header">' . htmlspecialchars($file) . ':' . htmlspecialchars($line) . '</div>';

        foreach ($args as $index => $arg) {
            echo renderDump($arg, "root_" . $index, 0);
        }

        echo '<script>
            function ddToggle(id) {
                var el = document.getElementById(id);
                var arrow = document.getElementById("arrow_" + id);
                if (el.style.display === "none" || el.style.display === "") {
                    el.style.display = "block";
                    if (arrow) arrow.classList.add("expanded");
                } else {
                    el.style.display = "none";
                    if (arrow) arrow.classList.remove("expanded");
                }
            }
        </script>
    </body>
    </html>';

        die();
    }
}

if (!function_exists('renderDump')) {
    /**
     * Recursive function to render variable dump
     */
    function renderDump($var, $id, $depth = 0)
    {
        $depthClass = 'dd-depth-' . ($depth % 7);

        if (is_array($var)) {
            $count = count($var);
            $html = "<div>";
            $html .= "<span class='dd-toggle $depthClass' onclick=\"ddToggle('$id')\">";
            $html .= "<span class='dd-arrow' id='arrow_$id'>▶</span>";
            $html .= "Array($count)";
            $html .= "</span>";
            $html .= "<div id='$id' class='dd-hidden'>";

            foreach ($var as $k => $v) {
                $childId = $id . "_" . preg_replace('/[^a-zA-Z0-9]/', '_', $k);
                $html .= "<div>";
                $html .= "<span class='dd-key'>[" . htmlspecialchars($k) . "]</span> => ";
                $html .= renderDump($v, $childId, $depth + 1);
                $html .= "</div>";
            }

            $html .= "</div></div>";
            return $html;
        } elseif (is_object($var)) {
            $className = get_class($var);
            $arr = (array)$var;
            $count = count($arr);

            $html = "<div>";
            $html .= "<span class='dd-toggle $depthClass' onclick=\"ddToggle('$id')\">";
            $html .= "<span class='dd-arrow' id='arrow_$id'>▶</span>";
            $html .= htmlspecialchars($className) . "($count)";
            $html .= "</span>";
            $html .= "<div id='$id' class='dd-hidden'>";

            foreach ($arr as $k => $v) {
                $childId = $id . "_" . preg_replace('/[^a-zA-Z0-9]/', '_', $k);
                $html .= "<div>";
                $html .= "<span class='dd-key'>[" . htmlspecialchars($k) . "]</span> => ";
                $html .= renderDump($v, $childId, $depth + 1);
                $html .= "</div>";
            }

            $html .= "</div></div>";
            return $html;
        } elseif (is_string($var)) {
            return "<span class='dd-value'>\"" . htmlspecialchars($var) . "\"</span>";
        } elseif (is_bool($var)) {
            return "<span class='dd-value'>" . ($var ? 'true' : 'false') . "</span>";
        } elseif (is_null($var)) {
            return "<span class='dd-value'>null</span>";
        } else {
            return "<span class='dd-value'>" . htmlspecialchars(var_export($var, true)) . "</span>";
        }
    }
}
