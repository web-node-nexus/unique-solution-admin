#!/usr/bin/env bash
# Detached live-API release APK for client sharing (no full clean — avoids CMake/codegen wipe)
set -euo pipefail

export ANDROID_HOME="${ANDROID_HOME:-$HOME/Android/Sdk}"
export JAVA_HOME="${JAVA_HOME:-/usr/lib/jvm/java-17-openjdk-amd64}"
export EXPO_PUBLIC_API_URL='http://94.103.163.218/unique-solution/api/v1'
export PATH="$JAVA_HOME/bin:$ANDROID_HOME/platform-tools:$PATH"

MOBILE="$(cd "$(dirname "$0")/.." && pwd)"
PROJ="$(cd "$MOBILE/../.." && pwd)"
LOG="$PROJ/apk-build.log"
OUT="$MOBILE/android/app/build/outputs/apk/release/app-release.apk"
STAMP="$(date +%Y%m%d-%H%M)"
CLIENT="$PROJ/UniqueSolution-v1.0.2-LIVE-$STAMP.apk"

cd "$MOBILE"
exec >"$LOG" 2>&1

echo "START $(date -Iseconds)"
echo "API=$EXPO_PUBLIC_API_URL"
echo "MOBILE=$MOBILE"

WRAPPER="$MOBILE/android/gradle/wrapper/gradle-wrapper.properties"
WRAPPER_BAK="$WRAPPER.bak-apk-build"
LOCAL_ZIP="$MOBILE/.gradle-dist/gradle-8.14.3-bin.zip"
restore_wrapper() { [[ -f "$WRAPPER_BAK" ]] && mv -f "$WRAPPER_BAK" "$WRAPPER" || true; }
trap restore_wrapper EXIT

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
  echo "Using local Gradle zip"
fi

# Only wipe app artifacts so JS/native app rebundles, keep library codegen
echo "Cleaning app build outputs only…"
rm -rf android/app/build/outputs/apk/release \
  android/app/build/generated/assets \
  android/app/build/intermediates/assets \
  android/app/build/intermediates/compressed_assets \
  android/app/build/intermediates/sourcemaps || true

echo "Generating codegen (libraries)…"
./android/gradlew -p android generateCodegenArtifactsFromSchema --build-cache || true

echo "Assembling release APK…"
./android/gradlew -p android :app:assembleRelease --build-cache --rerun-tasks

test -f "$OUT"
cp -f "$OUT" "$CLIENT"
cp -f "$OUT" "$HOME/Desktop/UniqueSolution-live-$STAMP.apk"
echo "CLIENT_APK=$CLIENT"
ls -lh "$OUT" "$CLIENT"
echo "END $(date -Iseconds)"
echo "OK" > "$PROJ/apk-build.done"
