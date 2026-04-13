#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
REPO_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
CONFIG_FILE="$SCRIPT_DIR/config.json"
PROMPT_FILE="$SCRIPT_DIR/prompt.md"

# Defaults
MODEL=""
MAX_BUDGET=""
BRANCH=""
ISSUE_NUMBER=""

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
DIM='\033[2m'
NC='\033[0m'

log()  { echo -e "${GREEN}[RALPH]${NC} $1"; }
warn() { echo -e "${YELLOW}[RALPH]${NC} $1"; }
err()  { echo -e "${RED}[RALPH]${NC} $1"; }
dim()  { echo -e "${DIM}[RALPH]${NC} $1"; }

usage() {
    cat <<USAGE
Usage: ralph-interactive.sh [options]

Picks the next unblocked GitHub issue and opens Claude interactively.
Same issue selection as ralph.sh, but you get the full Claude UI.

Options:
  --issue <n>           Work on a specific issue number (skip selection)
  --model <model>       Claude model to use (e.g., opus, sonnet)
  --budget <usd>        Max budget in USD
  --branch <name>       Git branch to work on (default: current branch)
  -h, --help            Show this help

Examples:
  ./ralph-interactive.sh                      # Pick next issue, open Claude UI
  ./ralph-interactive.sh --issue 350          # Work on specific issue
  ./ralph-interactive.sh --model sonnet       # Use Sonnet model
USAGE
    exit 0
}

# Parse args
while [[ $# -gt 0 ]]; do
    case "$1" in
        --issue)     ISSUE_NUMBER="$2"; shift 2 ;;
        --model)     MODEL="$2"; shift 2 ;;
        --budget)    MAX_BUDGET="$2"; shift 2 ;;
        --branch)    BRANCH="$2"; shift 2 ;;
        -h|--help)   usage ;;
        *)           err "Unknown option: $1"; usage ;;
    esac
done

# ---------------------------------------------------------------------------
# GitHub helpers (same as ralph.sh)
# ---------------------------------------------------------------------------

REPO_NAME=""
repo_name() {
    if [[ -z "$REPO_NAME" ]]; then
        REPO_NAME=$(gh repo view --json nameWithOwner -q .nameWithOwner)
    fi
    echo "$REPO_NAME"
}

fetch_open_issues() {
    gh issue list \
        --repo "$(repo_name)" \
        --state open \
        --label "ralph" \
        --json number,title,body,labels,comments \
        --limit 100
}

closed_issue_numbers() {
    gh issue list \
        --repo "$(repo_name)" \
        --state closed \
        --json number \
        --limit 200 \
    | jq '[.[].number]'
}

filter_unblocked() {
    local issues="$1"
    local closed="$2"

    echo "$issues" | jq --argjson closed "$closed" '
        [.[] | select(
            (.body | test("Blocked by") | not) or
            (.body | test("None.*can start immediately")) or
            (
                [.body | scan("Blocked by #([0-9]+)") | .[0] | tonumber] |
                all(. as $n | $closed | index($n) != null)
            )
        )] | sort_by(.number)
    '
}

fetch_ralph_commits() {
    git -C "$REPO_DIR" log --all --grep="^RALPH:" \
        --format="%H|%ai|%s%n%b---" -10 2>/dev/null || echo "(none)"
}

# ---------------------------------------------------------------------------
# Prompt assembly
# ---------------------------------------------------------------------------

build_prompt() {
    local issues="$1"
    local commits="$2"
    local base_prompt
    base_prompt=$(cat "$PROMPT_FILE")

    cat <<PROMPT
<issues>
$issues
</issues>

<recent-ralph-commits>
$commits
</recent-ralph-commits>

$base_prompt
PROMPT
}

# ---------------------------------------------------------------------------
# Main
# ---------------------------------------------------------------------------

main() {
    cd "$REPO_DIR"

    # Switch branch if requested
    if [[ -n "$BRANCH" ]]; then
        log "Switching to branch: $BRANCH"
        git checkout "$BRANCH"
    fi

    log "Ralph Interactive"
    log "  Branch: $(git branch --show-current)"
    [[ -n "$MODEL" ]]      && log "  Model:  $MODEL"
    [[ -n "$MAX_BUDGET" ]] && log "  Budget: \$$MAX_BUDGET"
    echo ""

    # Gather context
    local all_issues closed unblocked commits prompt
    all_issues=$(fetch_open_issues)
    closed=$(closed_issue_numbers)
    unblocked=$(filter_unblocked "$all_issues" "$closed")
    commits=$(fetch_ralph_commits)

    local total_open unblocked_count
    total_open=$(echo "$all_issues" | jq 'length')
    unblocked_count=$(echo "$unblocked" | jq 'length')

    if [[ "$total_open" -eq 0 ]]; then
        log "No open issues. All done!"
        exit 0
    fi

    if [[ "$unblocked_count" -eq 0 ]]; then
        warn "All $total_open open issues are blocked. Nothing to work on."
        exit 1
    fi

    # If a specific issue was requested, filter to just that one
    if [[ -n "$ISSUE_NUMBER" ]]; then
        local filtered
        filtered=$(echo "$unblocked" | jq --argjson num "$ISSUE_NUMBER" '[.[] | select(.number == $num)]')
        local found
        found=$(echo "$filtered" | jq 'length')

        if [[ "$found" -eq 0 ]]; then
            err "Issue #$ISSUE_NUMBER not found in unblocked issues."
            warn "Available unblocked issues:"
            echo "$unblocked" | jq -r '.[] | "  → #\(.number): \(.title)"'
            exit 1
        fi

        unblocked="$filtered"
    fi

    log "Unblocked issues ($unblocked_count):"
    echo "$unblocked" | jq -r '.[] | "  → #\(.number): \(.title)"'
    echo ""

    prompt=$(build_prompt "$unblocked" "$commits")

    # Build claude flags — interactive, no --print
    local flags=()

    flags+=("--dangerously-skip-permissions")

    if [[ -n "$MODEL" ]]; then
        flags+=("--model" "$MODEL")
    fi

    if [[ -n "$MAX_BUDGET" ]]; then
        flags+=("--max-budget-usd" "$MAX_BUDGET")
    fi

    log "Opening Claude UI..."
    echo ""

    local session_id
    session_id=$(uuidgen | tr '[:upper:]' '[:lower:]')

    # Launch Claude interactively — pipe prompt via stdin (same as ralph.sh, but without --print)
    # shellcheck disable=SC2086
    echo "$prompt" | claude ${flags[@]+"${flags[@]}"} --session-id "$session_id"
}

main "$@"
