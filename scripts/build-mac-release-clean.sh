#!/usr/bin/env bash
set -euo pipefail

SOURCE_DIR=$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)
BUILD_DIR=${WMW_MAC_BUILD_DIR:-/private/tmp/ra_walks_notifier_mac_build}

rm -rf "$BUILD_DIR"
mkdir -p "$BUILD_DIR"

rsync -a --delete \
  --exclude .git \
  --exclude dist \
  --exclude node_modules \
  --exclude .env \
  --exclude config.json \
  --exclude data \
  --exclude logs \
  --exclude sessions \
  "$SOURCE_DIR/" "$BUILD_DIR/"

cd "$BUILD_DIR"
npm ci
npm run build:mac:signed
npm run notary:submit

mkdir -p "$SOURCE_DIR/dist"
cp -R "$BUILD_DIR/dist/"* "$SOURCE_DIR/dist/"

echo "Mac release artifacts copied to $SOURCE_DIR/dist"
