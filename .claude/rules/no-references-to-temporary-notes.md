# No references to temporary note files

Top-level note and todo files such as `todo.md`, `TODO-NEXT.md`, `analysis.md`, `missing-docs.md` or similar scratch
files are temporary working notes. They get merged, renamed and deleted at any time.

- Never reference them from code comments, docblocks, tests, commit-ready docs, config files, scripts, CI files or
  error messages (no "see todo.md", "tracked in analysis.md", "documented in TODO-NEXT.md" and similar).
- Comments must explain the reason for the code on their own. If follow-up work is worth tracking, write it into the
  note file itself, or into a GitHub issue, and reference the issue number in the code.
- References between the note files themselves are fine.
- When touching a file, remove such references if they already exist.
