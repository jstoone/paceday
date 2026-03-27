# ISSUES

Issues JSON is provided at start of context. These are **unblocked** issues only — the runner has already filtered out issues whose dependencies haven't been closed yet. Parse the JSON to get their numbers, titles, bodies, and comments.

You've also been passed the last 10 RALPH commits (SHA, date, full message). Review these to understand what work has been done.

# TASK SELECTION

Pick ONE issue from the unblocked set. Prefer the lowest-numbered issue (it was created first and is likely the next in the dependency chain).

If all tasks are complete, output <promise>COMPLETE</promise>.

# ORIENTATION

Before writing code, orient yourself:

1. Read the CLAUDE.md files (root + relevant subdirectory: `backbone/CLAUDE.md` for backend, `pim/CLAUDE.md` for frontend)
2. Read the parent PRD issue for full architectural context
3. Read the specific issue's acceptance criteria carefully
4. Look at existing code in the area you're modifying — follow existing patterns
5. If the issue references other issues or PRD sections, read those too

# EXPLORATION

Explore the repo and fill your context window with relevant code. Specifically:

- Read existing events, states, and tests that you'll be modifying
- Look at sibling files for conventions (naming, structure, patterns)
- Check existing test files to understand the test style

# EXECUTION

Complete the task. Follow these rules:

- Start with a failing test, then make it pass (inside-out: domain first)
- Small, incremental changes — don't rewrite everything at once
- Run tests after each significant change: `cd backbone && php artisan test --compact --filter=<relevant>`
- Run `backbone/vendor/bin/pint --dirty` before committing to fix formatting
- Do NOT create documentation files unless explicitly asked
- Do NOT add comments, docstrings, or type annotations to code you didn't change

# COMMIT

Make a git commit. The commit message must:

1. Start with `RALPH:` prefix
2. Reference the issue number: `closes #NNN` or `progress on #NNN`
3. Summarize what was done in 1-2 sentences
4. List key decisions if any were made
5. Note blockers for next iteration if the task isn't complete

Example:
```
RALPH: Instant context system — facade, singletons, metadata hook (closes #198)

Built Instant facade with ActiveOrganization/ActiveCatalog scoped singletons,
InstantEvent base class, InstantJob with SetInstantContext middleware.

Decisions: Used $middleware property (not method) on InstantJob for safe merging.
Files: app/Domain/Framework/Instant.php, ActiveOrganization.php, ActiveCatalog.php,
InstantEvent.php, InstantJob.php, InstantCommand.php, SetInstantContext.php
```

# THE ISSUE

If the task is complete and all acceptance criteria are met, close the GitHub issue with `gh issue close <number>`.

If the task is not fully complete, leave a comment on the issue describing:
- What was done
- What remains
- Any blockers discovered

# FINAL RULES

- ONLY WORK ON A SINGLE ISSUE PER ITERATION
- Do NOT modify or close the parent PRD issue
- Do NOT work on issues that aren't in the provided set
- If you're stuck on an issue, leave a comment and move on — don't spin
