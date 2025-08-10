<?php
/**
 * This script scans all PHP files for __('...') and collects unique keys
 * into a translations template. Then it checks if fr.php and en.php
 * have the same number of keys.
 */
$directory = __DIR__;
$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));

$keys = [];

// Step 1: Extract all __('...') keys
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

// Step 2: Build array template
$output = "<?php\nreturn [\n";
foreach ($keys as $key => $val) {
    $output .= "    '$key' => '$val',\n";
}
$output .= "];";

// Save to file
$templatePath = __DIR__ . '/lang/template.php';
file_put_contents($templatePath, $output);
echo "Translation keys extracted to lang/template.php\n";

// Step 3: Compare fr.php and en.php counts
$langDir = __DIR__ . '/lang';
$frFile = $langDir . '/fr.php';
$enFile = $langDir . '/en.php';

if (file_exists($frFile) && file_exists($enFile)) {
    $fr = include $frFile;
    $en = include $enFile;

    $frCount = count($fr);
    $enCount = count($en);

    echo "fr.php has $frCount keys.\n";
    echo "en.php has $enCount keys.\n";

    if ($frCount === $enCount) {
        echo "✅ Both files have the same number of keys.\n";
    } else {
        echo "⚠ Mismatch detected! Difference: " . abs($frCount - $enCount) . " keys.\n";

        // Optional: find which keys are missing
        $missingInFr = array_diff_key($en, $fr);
        $missingInEn = array_diff_key($fr, $en);

        if (!empty($missingInFr)) {
            echo "Missing in fr.php:\n";
            print_r(array_keys($missingInFr));
        }

        if (!empty($missingInEn)) {
            echo "Missing in en.php:\n";
            print_r(array_keys($missingInEn));
        }
    }
} else {
    echo "⚠ fr.php or en.php not found in $langDir\n";
}
