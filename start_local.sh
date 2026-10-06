#!/usr/bin/env bash

set -Eeuo pipefail

project_root="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
readonly project_root
cd "$project_root"

for required_command in install npm npx php; do
    if ! command -v "$required_command" >/dev/null 2>&1; then
        printf 'Required command not found: %s\n' "$required_command" >&2
        exit 1
    fi
done

install -d -m 0755 \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

declare -a child_pids=()

cleanup() {
    local exit_code=$?

    trap - EXIT INT TERM

    if ((${#child_pids[@]} > 0)); then
        printf '\nStopping local development processes...\n'
        kill "${child_pids[@]}" 2>/dev/null || true
        wait "${child_pids[@]}" 2>/dev/null || true
    fi

    exit "$exit_code"
}

trap cleanup EXIT INT TERM

printf 'Starting Vite...\n'
npm run dev &
child_pids+=("$!")

printf 'Starting Tailwind CSS watcher...\n'
npx tailwindcss \
    -i ./resources/css/app.css \
    -o ./resources/css/tailwind.css \
    --watch=always &
child_pids+=("$!")

printf 'Starting Laravel development server...\n'
php artisan serve &
child_pids+=("$!")

printf '\nLocal development processes started. Press Ctrl+C to stop them.\n'

# End the group if any process exits, then let the EXIT trap stop the others.
wait -n "${child_pids[@]}"
