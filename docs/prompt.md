@docs/migration_plan.md
@docs/session_handoff.md

Working directory: ~/Desktop/PPPPP/FYP/
Execute Session 01 from the migration plan.

Rules:
- Read the full task checklist and done condition for this session from the plan before writing any code.
- Read the handoff file above to understand what the previous session left behind.
- Never touch existing Laravel files at root (app/, database/, routes/, resources/, composer.json, vendor/).
- All new code goes inside apps/ or packages/ only.
- When every item in the Done condition is verified, overwrite docs/session_handoff.md with this exact format:

---
# Session Handoff

## Last Completed Session
Session [N] — [Name] — [date]

## What Was Built
[bullet list of what was actually completed, ticked off against the plan checklist]

## Deviations from Plan
[anything that differed from the plan, or "None"]

## Current State
- [what is running and on which port]
- [any env vars that were set or changed]
- [any known issues or workarounds applied]

## Notes for Session [N+1]
[anything the next session must know before starting — decisions made, blockers resolved, gotchas found]
---