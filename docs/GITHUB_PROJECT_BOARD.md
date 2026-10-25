# Suggested GitHub Project Board

Create a board-style GitHub Project and use these columns/status values:

- **Backlog** — planned but not started
- **Ready** — clear and ready to work on
- **In Progress** — currently being implemented
- **In Review** — awaiting testing or review
- **Done** — verified and completed

## Suggested fields

- **Status:** Backlog, Ready, In Progress, In Review, Done
- **Priority:** P0 Critical, P1 High, P2 Normal, P3 Low
- **Area:** Security, Database, Members, Contributions, Payouts, Member Portal, UX, Documentation, Deployment

## Starter tasks

Create these as GitHub Issues, then add each issue to your Project. Set the current status based on what is actually completed—do not mark a task Done merely because a page exists.

1. **[P0] Fix the incomplete `hands/contributions.php` file** — Quality
2. **[P0] Remove hard-coded administrator password bypass** — Security
3. **[P0] Implement secure first-admin setup and password reset** — Security
4. **[P0] Review authorization for all admin and member routes** — Security
5. **[P1] Replace user-input SQL strings with prepared statements** — Database
6. **[P1] Add CSRF protection to state-changing forms** — Security
7. **[P1] Review upload validation and access restrictions** — Security
8. **[P1] Validate database setup from a clean installation** — Database
9. **[P1] Test member registration and member management workflows** — Members
10. **[P1] Test contribution recording and totals** — Contributions
11. **[P1] Test payout-cycle creation, order, and completion** — Payouts
12. **[P2] Test member portal login, profile, and payout schedule** — Member Portal
13. **[P2] Add demo screenshots using fictional data** — Documentation
14. **[P2] Document local setup and deployment configuration** — Documentation / Deployment
15. **[P2] Add a repeatable manual or automated regression test checklist** — Quality
16. **[P3] Review responsive layout and accessibility** — UX

## How to connect the board to the repository

1. Open the GitHub Project.
2. Add a **Status** field if it does not already exist.
3. Use **Add item** to add existing repository issues, or create new issues from the project.
4. In the repository, open **Issues** and create each starter task above.
5. Add each issue to the Project and set Status, Priority, and Area.
6. If desired, configure project workflows to set new items to Backlog automatically.

GitHub Projects tracks issues and pull requests; it does not automatically infer a reliable task list from every source file. Keep tasks honest and update statuses as work is verified.
