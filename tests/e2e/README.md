# Browser tests (Playwright)

Run with the Laravel app on `http://127.0.0.1:8000` and Vite running:

```bash
npm run test:e2e
```

Run one file or one test:

```bash
npx playwright test tests/e2e/queue-control.spec.js
npx playwright test -g "joins the queue"
```

## Mocked API

Every spec except two checks in `customers.spec.js` mocks `/api/*` through
`support/mock-api.js`. Tests never write to the database or send SMS through TextBee.

- `mockApi(page, overrides)` keeps an in-memory copy of barbers, tickets, history,
  templates, SMS logs and statistics. Pass `overrides` to start from a specific state.
- `api.state` can be changed mid-test to simulate what another device does (for
  example, a customer joining while Queue Control polls).
- `api.fail['METHOD /path-regex'] = { status, message }` makes an endpoint fail.
- `api.callsTo(method, path)` returns what the page sent, for asserting request bodies
  and query strings.

## What each file covers

| File | Screen |
| --- | --- |
| `join-page.spec.js` | Customer join page: closed shop, validation, joining, errors, status lookup |
| `customers.spec.js` | Queue Control core flows: call, start, finish, cancel, SMS, filters |
| `queue-control.spec.js` | Queue Control states: paused, empty, ordering, receipts, polling, reconnecting |
| `manage-queue.spec.js` | Manage Queue tabs, live table actions, barbers CRUD and duty, history paging and dates |
| `messages.spec.js` | SMS templates (create, edit, pause, delete, preview) and the delivery log |
| `dashboard.spec.js` | Summary cards, Server Status, Queue Usage levels and live toggle, chart |
| `statistics.spec.js` | Statistics charts and Barber Performance |
| `calling-board.spec.js` | Calling board display, paused state, announcements, reconnecting |
| `layout.spec.js` | Sidebar navigation, mobile drawer, theme, shop status widget |
