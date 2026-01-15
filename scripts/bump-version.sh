#!/bin/bash

# Define files
PLUGIN_FILE="ucp-shopping-agent.php"
README_FILE="readme.txt"

# Check if files exist
if [ ! -f "$PLUGIN_FILE" ] || [ ! -f "$README_FILE" ]; then
    echo "Error: Plugin files not found. Please run this script from the plugin root directory."
    exit 1
fi

# Extract current version
# Looks for "Version: x.x.x" in the plugin header
CURRENT_VERSION=$(grep -m 1 "Version:" "$PLUGIN_FILE" | awk '{print $NF}')

if [ -z "$CURRENT_VERSION" ]; then
    echo "Error: Could not find version in $PLUGIN_FILE"
    exit 1
fi

echo "Current version: $CURRENT_VERSION"

# Split version into parts
IFS='.' read -r -a VERSION_PARTS <<< "$CURRENT_VERSION"
MAJOR="${VERSION_PARTS[0]}"
MINOR="${VERSION_PARTS[1]}"
PATCH="${VERSION_PARTS[2]}"

# Increment patch version (default behavior)
# You can extend this script to handle arguments for major/minor bumps if needed
PATCH=$((PATCH + 1))
NEW_VERSION="$MAJOR.$MINOR.$PATCH"

echo "Bumping to: $NEW_VERSION"

# Update ucp-shopping-agent.php
# 1. Update Header Version
sed -i '' "s/Version:     $CURRENT_VERSION/Version:     $NEW_VERSION/" "$PLUGIN_FILE"

# 2. Update Constant Define
# Matches define('WC_UCP_VERSION', 'x.x.x');
sed -i '' "s/define('WC_UCP_VERSION', '$CURRENT_VERSION');/define('WC_UCP_VERSION', '$NEW_VERSION');/" "$PLUGIN_FILE"

# Update readme.txt
# Update Stable tag
sed -i '' "s/Stable tag: $CURRENT_VERSION/Stable tag: $NEW_VERSION/" "$README_FILE"

echo "Version updated successfully to $NEW_VERSION"
