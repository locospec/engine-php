#!/bin/bash

# Function to get the current version from composer.json
get_current_version() {
    php -r "echo json_decode(file_get_contents('composer.json'))->version;"
}

# Function to increment version
# Parameters: version_part (patch|minor|major|alpha|beta)
increment_version() {
    current_version=$(get_current_version)

    # Split version into parts
    IFS='.' read -r -a version_parts <<< "${current_version%-*}"
    major="${version_parts[0]}"
    minor="${version_parts[1]}"
    patch="${version_parts[2]}"

    case "$1" in
        "patch")
            patch=$((patch + 1))
            new_version="$major.$minor.$patch"
            ;;
        "minor")
            minor=$((minor + 1))
            patch=0
            new_version="$major.$minor.$patch"
            ;;
        "major")
            major=$((major + 1))
            minor=0
            patch=0
            new_version="$major.$minor.$patch"
            ;;
        "alpha")
            # If current version is already an alpha
            if [[ $current_version == *"-alpha."* ]]; then
                alpha_num="${current_version##*-alpha.}"
                alpha_num=$((alpha_num + 1))
                new_version="$major.$minor.$patch-alpha.$alpha_num"
            else
                new_version="$major.$minor.$patch-alpha.1"
            fi
            ;;
        "beta")
            # If current version is already a beta
            if [[ $current_version == *"-beta."* ]]; then
                beta_num="${current_version##*-beta.}"
                beta_num=$((beta_num + 1))
                new_version="$major.$minor.$patch-beta.$beta_num"
            else
                # If moving from alpha to beta, reset counter
                if [[ $current_version == *"-alpha."* ]]; then
                    new_version="$major.$minor.$patch-beta.1"
                else
                    new_version="$major.$minor.$patch-beta.1"
                fi
            fi
            ;;
        *)
            echo "Invalid version part specified"
            exit 1
            ;;
    esac

    # Update composer.json with new version
    temp_file=$(mktemp)
    jq ".version = \"$new_version\"" composer.json > "$temp_file" && mv "$temp_file" composer.json

    echo "$new_version"
}

# Main script
version_type=${1:-"patch"}  # Default to patch if no argument provided
new_version=$(increment_version "$version_type")

# Create git tag and push
git add composer.json
git commit -m "Bump version to $new_version"
git tag -a "v$new_version" -m "Version $new_version"
git push origin main --tags

# Determine if this is a prerelease (alpha or beta)
is_prerelease=false
if [[ $new_version == *"-alpha."* ]] || [[ $new_version == *"-beta."* ]]; then
    is_prerelease=true
fi

# Create GitHub release
if [ "$is_prerelease" = true ]; then
    gh release create "v$new_version" \
        --generate-notes \
        --prerelease \
        --title "v$new_version"
else
    gh release create "v$new_version" \
        --generate-notes \
        --title "v$new_version"
fi

echo "Released version $new_version"
