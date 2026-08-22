#!/usr/bin/env bash
# WP-MCP Release Build Script
# Creates a clean, distribution-ready wpmcp.zip plugin archive.

set -e

PLUGIN_SLUG="wpmcp"
VERSION="1.0.0"
BUILD_DIR="./build"
DIST_DIR="./dist"
ZIP_NAME="${PLUGIN_SLUG}-v${VERSION}.zip"

echo "==> Building WP-MCP release package v${VERSION}..."

# Clean previous builds
rm -rf "$BUILD_DIR" "$DIST_DIR"
mkdir -p "$BUILD_DIR/$PLUGIN_SLUG" "$DIST_DIR"

# Copy essential production files
echo "==> Copying plugin files..."
cp wpmcp.php "$BUILD_DIR/$PLUGIN_SLUG/"
cp readme.txt "$BUILD_DIR/$PLUGIN_SLUG/"
cp README.md "$BUILD_DIR/$PLUGIN_SLUG/"
cp CHANGELOG.md "$BUILD_DIR/$PLUGIN_SLUG/"

cp -r includes "$BUILD_DIR/$PLUGIN_SLUG/"
cp -r admin "$BUILD_DIR/$PLUGIN_SLUG/"
cp -r bin "$BUILD_DIR/$PLUGIN_SLUG/"

# Clean any OS artifacts
find "$BUILD_DIR" -name ".DS_Store" -delete

# Create zip archive
echo "==> Creating zip archive in $DIST_DIR/$ZIP_NAME..."
(cd "$BUILD_DIR" && zip -r -q "../$DIST_DIR/$ZIP_NAME" "$PLUGIN_SLUG")
(cd "$BUILD_DIR" && zip -r -q "../$DIST_DIR/${PLUGIN_SLUG}.zip" "$PLUGIN_SLUG")

# Cleanup build directory
rm -rf "$BUILD_DIR"

echo "==> Release package built successfully:"
echo "    - $DIST_DIR/$ZIP_NAME"
echo "    - $DIST_DIR/${PLUGIN_SLUG}.zip"
