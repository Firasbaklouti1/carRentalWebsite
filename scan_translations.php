<?php
/**
 * This script scans all PHP files for __('...') and collects unique keys
 * into a translations template.
 */
$directory = __DIR__;
$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));

$keys = [];

foreach ($rii as $file) {
    if ($file->isDir()) continue;
    if (pathinfo($file->getPathname(), PATHINFO_EXTENSION) !== 'php') continue;

    $content = file_get_contents($file->getPathname());
    preg_match_all("/__\(['\"](.+?)['\"]\)/", $content, $matches);
    if (!empty($matches[1])) {
        foreach ($matches[1] as $match) {
            $keys[$match] = $match;
        }
    }
}

// Build array template
$output = "<?php\nreturn [\n";
foreach ($keys as $key => $val) {
    $output .= "    '$key' => '$val',\n";
}
$output .= "];";

// Save to file
file_put_contents(__DIR__ . '/lang/template.php', $output);

echo "Translation keys extracted to lang/template.php\n";
