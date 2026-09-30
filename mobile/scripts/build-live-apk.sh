#!/usr/bin/env bash
# Fast cached release APK with live API + latest JS bundle
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

export ANDROID_HOME="${ANDROID_HOME:-$HOME/Android/Sdk}"
export EXPO_PUBLIC_API_URL="${EXPO_PUBLIC_API_URL:-http://94.103.163.218/unique-solution/api/v1}"

WRAPPER="$ROOT/android/gradle/wrapper/gradle-wrapper.properties"
WRAPPER_BAK="$WRAPPER.bak-apk-build"
LOCAL_ZIP="$ROOT/.gradle-dist/gradle-8.14.3-bin.zip"

restore_wrapper() {
  if [[ -f "$WRAPPER_BAK" ]]; then
    mv -f "$WRAPPER_BAK" "$WRAPPER"
  fi
}
trap restore_wrapper EXIT

# Prefer offline local Gradle zip (fast, no download) when present
if [[ -f "$LOCAL_ZIP" ]]; then
  cp -f "$WRAPPER" "$WRAPPER_BAK"
  printf '%s\n' \
    'distributionBase=GRADLE_USER_HOME' \
    'distributionPath=wrapper/dists' \
    "distributionUrl=file\://${LOCAL_ZIP}" \
    'networkTimeout=10000' \
    'validateDistributionUrl=false' \
    'zipStoreBase=GRADLE_USER_HOME' \
    'zipStorePath=wrapper/dists' \
    > "$WRAPPER"
  echo "==> Using cached local Gradle zip"
fi

echo "==> API: $EXPO_PUBLIC_API_URL"
echo "==> Building release APK (Gradle build-cache ON, incremental)…"
./android/gradlew -p android assembleRelease --build-cache

OUT="$ROOT/android/app/build/outputs/apk/release/app-release.apk"
STAMP="$(date +%Y%m%d-%H%M)"
DEST_DESKTOP="$HOME/Desktop/UniqueSolution-live-${STAMP}.apk"
DEST_PROJECT="/home/k-k-digital-solution/Desktop/2026/unique-solution/UniqueSolution-v1.0.2-LIVE-${STAMP}.apk"
cp -f "$OUT" "$DEST_DESKTOP"
cp -f "$OUT" "$DEST_PROJECT"

echo
echo "==> Done (live API + Gradle cache)"
ls -lh "$OUT" "$DEST_DESKTOP" "$DEST_PROJECT"
stat -c 'Built: %y' "$OUT" || true
echo
echo "Install: adb install -r \"$DEST_PROJECT\""
