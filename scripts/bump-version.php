<?php
/**
 * Script to auto-bump version in plugin files.
 * Usage: php scripts/bump-version.php [major|minor|patch]
 */

// Define file paths
$plugin_file = dirname(__DIR__) . '/ucp-shopping-agent.php';
$readme_file = dirname(__DIR__) . '/readme.txt';

if (!file_exists($plugin_file) || !file_exists($readme_file)) {
    die("Error: Plugin files not found.\n");
}

// Get current version
$content = file_get_contents($plugin_file);
if (!preg_match('/Version:\s*(\d+\.\d+\.\d+)/', $content, $matches)) {
    die("Error: Could not find version in plugin file.\n");
}

$current_version = $matches[1];
echo "Current version: $current_version\n";

// Determine bump type
$type = isset($argv[1]) ? $argv[1] : 'patch';
$parts = explode('.', $current_version);

switch ($type) {
    case 'major':
        $parts[0]++;
        $parts[1] = 0;
        $parts[2] = 0;
        break;
    case 'minor':
        $parts[1]++;
        $parts[2] = 0;
        break;
    case 'patch':
    default:
        $parts[2]++;
        break;
}

$new_version = implode('.', $parts);
echo "Bumping to: $new_version\n";

// Update ucp-shopping-agent.php
$content = preg_replace(
    '/Version:\s*\d+\.\d+\.\d+/',
    "Version:     $new_version",
    $content
);
$content = preg_replace(
    "/define\('WC_UCP_VERSION', '\d+\.\d+\.\d+'\);/",
    "define('WC_UCP_VERSION', '$new_version');",
    $content
);
file_put_contents($plugin_file, $content);

// Update readme.txt
$readme = file_get_contents($readme_file);
$readme = preg_replace(
    '/Stable tag:\s*\d+\.\d+\.\d+/',
    "Stable tag: $new_version",
    $readme
);
file_put_contents($readme_file, $readme);

echo "Updated files successfully.\n";
