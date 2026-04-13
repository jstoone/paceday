#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
REPO_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
CONFIG_FILE="$SCRIPT_DIR/config.json"
PROMPT_FILE="$SCRIPT_DIR/prompt.md"
LOG_DIR="$SCRIPT_DIR/logs"

# Defaults
MAX_ITERATIONS=$(jq -r '.defaultIterations // 100' "$CONFIG_FILE")
DRY_RUN=false
MODEL=""
MAX_BUDGET=""
BRANCH=""

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
Usage: ralph.sh [options]

Picks the next unblocked GitHub issue, spawns Claude to work on it, loops.

Options:
  --max <n>             Max iterations (default: $MAX_ITERATIONS)
  --model <model>       Claude model to use (e.g., opus, sonnet)
  --budget <usd>        Max budget per iteration in USD
  --branch <name>       Git branch to work on (default: current branch)
  --dry-run             Show what would be done without running Claude
  -h, --help            Show this help

Examples:
  ./ralph.sh                                 # Run autonomously
  ./ralph.sh --max 5 --dry-run               # Preview 5 iterations
  ./ralph.sh --model sonnet --budget 1       # Use Sonnet, cap at \$1/iteration
USAGE
    exit 0
}

# Parse args
while [[ $# -gt 0 ]]; do
    case "$1" in
        --max)       MAX_ITERATIONS="$2"; shift 2 ;;
        --model)     MODEL="$2"; shift 2 ;;
        --budget)    MAX_BUDGET="$2"; shift 2 ;;
        --branch)    BRANCH="$2"; shift 2 ;;
        --dry-run)   DRY_RUN=true; shift ;;
        -h|--help)   usage ;;
        *)           err "Unknown option: $1"; usage ;;
    esac
done

# ---------------------------------------------------------------------------
# GitHub helpers
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

    # An issue is unblocked if:
    # - It has "None — can start immediately" (or no "Blocked by" section), OR
    # - Every "Blocked by #NNN" reference is in the closed set
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
# Build claude flags
# ---------------------------------------------------------------------------

build_claude_flags() {
    local flags=()

    flags+=("--print")
    flags+=("--dangerously-skip-permissions")

    if [[ -n "$MODEL" ]]; then
        flags+=("--model" "$MODEL")
    fi

    if [[ -n "$MAX_BUDGET" ]]; then
        flags+=("--max-budget-usd" "$MAX_BUDGET")
    fi

    echo "${flags[@]}"
}

# ---------------------------------------------------------------------------
# Main loop
# ---------------------------------------------------------------------------

main() {
    cd "$REPO_DIR"

    # Switch branch if requested
    if [[ -n "$BRANCH" ]]; then
        log "Switching to branch: $BRANCH"
        git checkout "$BRANCH"
    fi

    mkdir -p "$LOG_DIR"

    log "Starting Ralph loop"
    log "  Branch:     $(git branch --show-current)"
    log "  Max iter:   $MAX_ITERATIONS"
    [[ -n "$MODEL" ]]      && log "  Model:      $MODEL"
    [[ -n "$MAX_BUDGET" ]] && log "  Budget/iter: \$$MAX_BUDGET"
    echo ""

    local last_issue=""
    local stuck_count=0
    local MAX_STUCK=3

    for ((i=1; i<=MAX_ITERATIONS; i++)); do
        log "━━━ Iteration $i/$MAX_ITERATIONS ━━━"

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
            warn "Check issue dependencies — some blockers may need closing."
            exit 1
        fi

        # Stuck detection — if the same issue is picked 3 times, bail
        local first_issue
        first_issue=$(echo "$unblocked" | jq -r '.[0].number')

        if [[ "$first_issue" == "$last_issue" ]]; then
            stuck_count=$((stuck_count + 1))
            if [[ "$stuck_count" -ge "$MAX_STUCK" ]]; then
                err "Stuck on #$first_issue for $MAX_STUCK iterations. Bailing."
                err "Check the log: $LOG_DIR/"
                exit 1
            fi
            warn "Same issue #$first_issue again (attempt $((stuck_count + 1))/$MAX_STUCK)"
        else
            stuck_count=0
            last_issue="$first_issue"
        fi

        log "Open: $total_open | Unblocked: $unblocked_count"

        # Show unblocked issues
        echo "$unblocked" | jq -r '.[] | "  → #\(.number): \(.title)"'
        echo ""

        prompt=$(build_prompt "$unblocked" "$commits")

        if [[ "$DRY_RUN" == true ]]; then
            dim "Dry run — would invoke Claude with ${#prompt} char prompt"
            dim "Skipping..."
            echo ""
            continue
        fi

        # Run Claude
        local logfile="$LOG_DIR/iteration-${i}-$(date +%Y%m%d-%H%M%S).log"
        local claude_flags
        claude_flags=$(build_claude_flags)

        local session_id
        session_id=$(uuidgen | tr '[:upper:]' '[:lower:]')
        log "Spawning Claude... (session: $session_id)"
        dim "  log: $logfile"

        local output
        # shellcheck disable=SC2086
        if output=$(echo "$prompt" | claude $claude_flags --session-id "$session_id" 2>&1 | tee "$logfile"); then
            log "Claude exited successfully"
        else
            warn "Claude exited with non-zero status"
        fi

        # Check for completion signal
        if echo "$output" | grep -q '<promise>COMPLETE</promise>'; then
            log "All tasks complete!"
            exit 0
        fi

        log "Iteration $i done."
        echo ""
    done

    warn "Reached max iterations ($MAX_ITERATIONS)"
    exit 1
}

main "$@"
