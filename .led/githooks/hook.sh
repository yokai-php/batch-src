#!/usr/bin/env sh
if [ -z "$githook_skip_init" ]; then
  readonly hook_name="$(basename -- "$0")"
  echo "starting $hook_name..."

  if [ "$GITHOOKS" = "0" ]; then
    echo "GITHOOKS env variable is set to 0, skipping hook"
    exit 0
  fi

  readonly githook_skip_init=1
  export githook_skip_init

  if [ "$(basename -- "$SHELL")" = "zsh" ]; then
    zsh --emulate sh -e "$0" "$@"
  else
    sh -e "$0" "$@"
  fi
  exitCode="$?"

  if [ $exitCode != 0 ]; then
    echo "$hook_name hook exited with code $exitCode (error)"
  fi

  if [ $exitCode = 127 ]; then
    echo "command not found in PATH=$PATH"
  fi

  exit $exitCode
fi
