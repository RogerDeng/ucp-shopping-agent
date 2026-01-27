#!/bin/bash

# Loop through all files starting with class-ucp-
find . -name "class-ucp-*.php" -print0 | while IFS= read -r -d '' file; do
    dir=$(dirname "$file")
    filename=$(basename "$file")
    new_filename=${filename/class-ucp-/class-shopping-agent-ucp-}
    
    echo "Renaming $file to $dir/$new_filename"
    
    # Rename the file
    mv "$file" "$dir/$new_filename"
    
    # Update references in all PHP files
    # We strip the ./ prefix for grep/sed to match strings typically used in require/include
    old_code_ref="${filename}"
    new_code_ref="${new_filename}"
    
    # Also handle cases where path might be relative or full (grep will find the string)
    # Using find | xargs sed is safer for bulk updates
    find . -type f -name "*.php" -print0 | xargs -0 sed -i '' "s/$old_code_ref/$new_code_ref/g"
done

# Also rename the main directory assets/css/ucp-shopping-admin.css etc if they exist?
# No, checking css/js files naming.
