<?php

foreach (glob(__DIR__ . '/*Test.php') as $file) {
    if (basename($file) === 'run_tests.php' || basename($file) === 'TestRunner.php') {
        continue;
    }

    echo "\n=== Running " . basename($file) . " ===\n";
    require_once $file;
}

echo "\nAll tests executed.\n";
