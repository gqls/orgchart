#!/bin/sh
set -e

# Check if node_modules exists
if [ ! -d "node_modules" ]; then
  echo "Node modules not found, installing dependencies..."
  npm install
fi

# Run the provided command or default to npm run watch
if [ "$1" != "" ]; then
  exec "$@"
else
  echo "Starting development server..."
  # Use exec to ensure the process receives signals properly
  # The || true ensures container stays alive even if npm exits with error
  exec npm run watch || true
fi

# Keep container running if all else fails
exec tail -f /dev/null