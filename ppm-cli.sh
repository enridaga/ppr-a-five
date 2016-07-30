#!/bin/bash

SCRIPT="$(readlink "$0")"
SCRIPTPATH="$(dirname "$SCRIPT")"

php $SCRIPTPATH/public/index.php "$@"
