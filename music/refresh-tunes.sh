#!/bin/bash

# NOTE you need coreutils for this - `brew install coreutils`

MUSIC_DIR="/Volumes/Shared/iTunes/iTunes Media/Music"
COPY_TO_DIR="/Volumes/LABEOUF/MUSIC"

echo "this will erase everything in '$COPY_TO_DIR'."
while true; do
  read -p "ok? [y/N] " yn
  case $yn in
    [Yy]* ) break;;
    [Nn]* ) echo "abort." ; exit 1;;
    * ) echo "please enter 'y' or 'n'.";;
  esac
done

cd "$COPY_TO_DIR"
find . -mindepth 1 -delete

find "$MUSIC_DIR" | gshuf | while read TUNE; do
  if [[ "$(file "$TUNE")" == *directory ]]; then
    continue
  fi

  if [[ "$TUNE" != *.mp3 ]]; then
    if [[ "$TUNE" != *.m4a ]]; then
      if [[ "$TUNE" != *.wav ]]; then
        if [[ "$TUNE" != *.aif ]]; then
          if [[ "$TUNE" != *.aiff ]]; then
            continue
          fi
        fi
      fi
    fi
  fi

  COPY_NAME="$(shasum -b "$TUNE" | awk '{ print $1 }')"
  COPY_EXT="${TUNE##*.}"
  COPY_PATH="$COPY_TO_DIR/$COPY_NAME.$COPY_EXT"
  echo "$TUNE"

  if [ ! -e "$COPY_PATH" ]; then
    cp "$TUNE" "$COPY_PATH" || break
  fi
done
