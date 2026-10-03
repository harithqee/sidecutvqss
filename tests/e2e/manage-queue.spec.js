import { test, expect } from '@playwright/test';
import { mockApi, barber, ticket, historyEntry, toast } from './support/mock-api.js';

const tab = (page, name) => page.getByRole('tab', { name });
const liveRow = (page, name) => page.locator('table tbody tr', { hasText: name });

async function pickRange(page) {
    // Choose the 1st and 2nd selectable days in the visible month.
    await page.getByLabel('Date range', { exact: true }).click();
    const days = page.locator('.flatpickr-calendar.open .flatpickr-day:not(.flatpickr-disabled):not(.prevMonthDay):not(.nextMonthDay)');
    await days.nth(0).click();
    await days.nth(1).click();
}

test.describe('Manage Queue tabs', () => {
    test('opens on Live queue and remembers the tab in the URL hash', async ({ page }) => {
        await mockApi(page);
        await page.goto('/queue');

        await expect(tab(page, 'Live queue')).toHaveAttribute('aria-selected', 'true');
        await tab(page, 'Barbers').click();
        await expect(page).toHaveURL(/#barbers$/);
        await expect(page.getByRole('button', { name: 'Add barber' })).toBeVisible();

        await tab(page, 'History').click();
        await expect(page).toHaveURL(/#history$/);
    });

    test('deep links straight to a tab', async ({ page }) => {
        await mockApi(page);
        await page.goto('/queue#history');
        await expect(tab(page, 'History')).toHaveAttribute('aria-selected', 'true');
        await expect(page.getByLabel('Date range', { exact: true })).toBeVisible();
    });
});

test.describe('Live queue table', () => {
    const tickets = () => [
        ticket(1, 4, 'Chair Guy', { status: 'serving', barberId: 1 }),
        ticket(2, 5, 'Called Guy', { barberId: 2, calling: true }),
        ticket(3, 6, 'Waiting Guy', { barberId: null }),
    ];

    test('shows each ticket with its status and the right buttons', async ({ page }) => {
        await mockApi(page, { tickets: tickets() });
        await page.goto('/queue');

        await expect(page.getByText('2 waiting, 1 in the chair')).toBeVisible();
        await expect(liveRow(page, 'Chair Guy')).toContainText('In chair');
        await expect(liveRow(page, 'Chair Guy').getByRole('button', { name: 'Complete' })).toBeVisible();
        await expect(liveRow(page, 'Chair Guy').getByRole('button', { name: 'Call to counter' })).toBeDisabled();

        await expect(liveRow(page, 'Called Guy')).toContainText('Called');
        await expect(liveRow(page, 'Called Guy').getByRole('button', { name: 'Call again' })).toBeEnabled();

        await expect(liveRow(page, 'Waiting Guy')).toContainText('Waiting');
        await expect(liveRow(page, 'Waiting Guy')).toContainText('Any barber');
        await expect(liveRow(page, 'Waiting Guy').getByRole('button', { name: 'Start service' })).toBeVisible();
    });

    test('filters by barber, including customers with no preference', async ({ page }) => {
        await mockApi(page, { tickets: tickets() });
        await page.goto('/queue');
        const filter = page.getByLabel('Filter by barber');

        await filter.selectOption('1');
        await expect(liveRow(page, 'Chair Guy')).toBeVisible();
        await expect(liveRow(page, 'Called Guy')).toHaveCount(0);

        await filter.selectOption('unassigned');
        await expect(liveRow(page, 'Waiting Guy')).toBeVisible();
        await expect(liveRow(page, 'Chair Guy')).toHaveCount(0);

        await filter.selectOption('3');
        await expect(page.getByText('Nobody waiting for this barber')).toBeVisible();
    });

    test('call, start, complete, SMS and cancel each hit the API', async ({ page }) => {
        const api = await mockApi(page, { tickets: tickets() });
        await page.goto('/queue');

        await liveRow(page, 'Waiting Guy').getByRole('button', { name: 'Call to counter' }).click();
        await expect(toast(page, '#006 is on the calling board.')).toBeVisible();
        await expect(liveRow(page, 'Waiting Guy')).toContainText('Called');

        await liveRow(page, 'Waiting Guy').getByRole('button', { name: 'Start service' }).click();
        await expect(toast(page, 'Waiting Guy is in the chair.')).toBeVisible();
        await expect(liveRow(page, 'Waiting Guy').getByRole('button', { name: 'Complete' })).toBeVisible();

        await liveRow(page, 'Chair Guy').getByRole('button', { name: 'Complete' }).click();
        await expect(liveRow(page, 'Chair Guy')).toHaveCount(0);

        await liveRow(page, 'Called Guy').getByRole('button', { name: 'Send SMS to Called Guy' }).click();
        await expect(toast(page, 'SMS sent to Called Guy.')).toBeVisible();

        await liveRow(page, 'Called Guy').getByRole('button', { name: 'Cancel' }).click();
        await page.getByRole('alertdialog').getByRole('button', { name: 'Cancel ticket' }).click();
        await expect(liveRow(page, 'Called Guy')).toHaveCount(0);

        expect(api.callsTo('PATCH', /^\/queue\/\d+\/status$/).map((c) => [c.path, c.body.status])).toEqual([
            ['/queue/3/status', 'serving'],
            ['/queue/1/status', 'completed'],
            ['/queue/2/status', 'canceled'],
        ]);
    });

    test('keeping a ticket in the cancel dialog sends nothing', async ({ page }) => {
        const api = await mockApi(page, { tickets: tickets() });
        await page.goto('/queue');

        await liveRow(page, 'Waiting Guy').getByRole('button', { name: 'Cancel' }).click();
        await page.getByRole('alertdialog').getByRole('button', { name: 'Keep in queue' }).click();
        await expect(liveRow(page, 'Waiting Guy')).toBeVisible();
        expect(api.callsTo('PATCH', /status$/)).toHaveLength(0);
    });

    test('shows errors from failed actions', async ({ page }) => {
        const api = await mockApi(page, { tickets: tickets() });
        api.fail['PATCH /queue/\\d+/status'] = { status: 409 };
        api.fail['POST /queue/\\d+/sms'] = { status: 502 };
        api.fail['POST /queue/\\d+/call'] = { status: 422, message: 'Only today’s waiting tickets can be called.' };
        await page.goto('/queue');

        await liveRow(page, 'Waiting Guy').getByRole('button', { name: 'Start service' }).click();
        await expect(toast(page, 'Could not update status.')).toBeVisible();
        await liveRow(page, 'Waiting Guy').getByRole('button', { name: 'Send SMS to Waiting Guy' }).click();
        await expect(toast(page, 'Could not send SMS.')).toBeVisible();
        await liveRow(page, 'Waiting Guy').getByRole('button', { name: 'Call to counter' }).click();
        await expect(toast(page, 'Only today’s waiting tickets can be called.')).toBeVisible();
        await expect(liveRow(page, 'Waiting Guy')).toContainText('Waiting');
    });

    test('shows the empty state and picks up new tickets by polling', async ({ page }) => {
        const api = await mockApi(page);
        await page.goto('/queue');
        await expect(page.getByText('The line is empty')).toBeVisible();

        api.state.tickets.push(ticket(9, 30, 'Late Arrival'));
        await expect(liveRow(page, 'Late Arrival')).toBeVisible({ timeout: 8000 });
    });
});

test.describe('Barbers', () => {
    test('shows duty counts and badges', async ({ page }) => {
        await mockApi(page);
        await page.goto('/queue#barbers');

        await expect(page.getByText('of 3 on duty', { exact: false })).toContainText('2 of 3 on duty');
        await expect(page.getByRole('listitem').filter({ hasText: 'Alex' })).toContainText('On duty');
        await expect(page.getByRole('listitem').filter({ hasText: 'Marzuki' })).toContainText('Off duty');
    });

    test('adds a barber, with validation', async ({ page }) => {
        const api = await mockApi(page);
        await page.goto('/queue#barbers');

        await page.getByRole('button', { name: 'Add barber' }).click();
        const form = page.locator('form').filter({ has: page.getByPlaceholder('e.g. Marcus') });
        await form.getByRole('button', { name: 'Add barber' }).click();
        await expect(toast(page, 'Enter a name and a role.')).toBeVisible();
        expect(api.callsTo('POST', '/barbers')).toHaveLength(0);

        await page.getByPlaceholder('e.g. Marcus').fill('Marcus');
        await page.getByPlaceholder('e.g. Senior Barber').fill('Senior Barber');
        await form.getByRole('button', { name: 'Add barber' }).click();
        await expect(toast(page, 'Marcus added and on duty.')).toBeVisible();
        await expect(page.getByRole('listitem').filter({ hasText: 'Marcus' })).toContainText('Senior Barber');
        expect(api.callsTo('POST', '/barbers')[0].body).toEqual({ name: 'Marcus', role: 'Senior Barber' });
    });

    test('cancelling the add form discards it', async ({ page }) => {
        await mockApi(page);
        await page.goto('/queue#barbers');

        await page.getByRole('button', { name: 'Add barber' }).click();
        await page.getByPlaceholder('e.g. Marcus').fill('Temp');
        await page.getByRole('button', { name: 'Cancel', exact: true }).click();
        await expect(page.getByPlaceholder('e.g. Marcus')).toBeHidden();
    });

    test('edits a barber and can cancel an edit', async ({ page }) => {
        const api = await mockApi(page);
        await page.goto('/queue#barbers');
        const card = page.getByRole('listitem').filter({ hasText: 'Sam' });

        await card.getByRole('button', { name: 'Edit' }).click();
        await card.getByLabel('Role').fill('Should not save');
        await card.getByRole('button', { name: 'Cancel' }).click();
        await expect(card).toContainText('Barber');
        expect(api.callsTo('PUT', '/barbers/2')).toHaveLength(0);

        await card.getByRole('button', { name: 'Edit' }).click();
        await card.getByLabel('Name').fill('Samuel');
        await card.getByLabel('Role').fill('Head Barber');
        await card.getByRole('button', { name: 'Save' }).click();
        await expect(toast(page, 'Changes saved.')).toBeVisible();
        await expect(page.getByRole('listitem').filter({ hasText: 'Samuel' })).toContainText('Head Barber');
        expect(api.callsTo('PUT', '/barbers/2')[0].body).toEqual({ name: 'Samuel', role: 'Head Barber' });
    });

    test('toggles duty on and off', async ({ page }) => {
        const api = await mockApi(page);
        await page.goto('/queue#barbers');

        await page.getByLabel('On duty: Marzuki').check({ force: true });
        await expect(toast(page, 'Marzuki is on duty.')).toBeVisible();
        await expect(page.getByRole('listitem').filter({ hasText: 'Marzuki' })).toContainText('On duty');

        await page.getByLabel('On duty: Alex').uncheck({ force: true });
        await expect(toast(page, 'Alex is off duty.')).toBeVisible();
        expect(api.callsTo('PATCH', /toggle-active$/)).toHaveLength(2);
    });

    test('removes a barber after confirmation', async ({ page }) => {
        const api = await mockApi(page);
        await page.goto('/queue#barbers');
        const card = page.getByRole('listitem').filter({ hasText: 'Marzuki' });

        await card.getByRole('button', { name: 'Remove' }).click();
        await page.getByRole('alertdialog').getByRole('button', { name: 'Cancel' }).click();
        await expect(card).toBeVisible();

        await card.getByRole('button', { name: 'Remove' }).click();
        await page.getByRole('alertdialog').getByRole('button', { name: 'Remove barber' }).click();
        await expect(toast(page, 'Marzuki removed.')).toBeVisible();
        await expect(card).toHaveCount(0);
        expect(api.callsTo('DELETE', '/barbers/3')).toHaveLength(1);
    });

    test('explains why a barber cannot be removed', async ({ page }) => {
        const api = await mockApi(page);
        api.fail['DELETE /barbers/\\d+'] = { status: 422, message: 'This barber still has customers waiting.' };
        await page.goto('/queue#barbers');

        await page.getByRole('listitem').filter({ hasText: 'Alex' }).getByRole('button', { name: 'Remove' }).click();
        await page.getByRole('alertdialog').getByRole('button', { name: 'Remove barber' }).click();
        await expect(toast(page, 'This barber still has customers waiting.')).toBeVisible();
        await expect(page.getByRole('listitem').filter({ hasText: 'Alex' })).toBeVisible();
    });

    test('shows the empty state with no barbers', async ({ page }) => {
        await mockApi(page, { barbers: [] });
        await page.goto('/queue#barbers');
        await expect(page.getByText('No barbers yet')).toBeVisible();
    });
});

test.describe('History', () => {
    const history = () => Array.from({ length: 23 }, (_, i) =>
        historyEntry(i + 1, 100 + i, 'Customer ' + (i + 1), { status: i === 1 ? 'canceled' : 'completed', wait: [8, 0, 25, 35][i % 4] }));

    test('lists finished tickets with wait colours and outcomes', async ({ page }) => {
        await mockApi(page, { history: history() });
        await page.goto('/queue#history');

        const rows = page.locator('table tbody tr');
        await expect(rows).toHaveCount(10);
        await expect(rows.nth(0)).toContainText('#100');
        await expect(rows.nth(0)).toContainText('Served ·');
        await expect(rows.nth(1)).toContainText('Cancelled');
        await expect(rows.nth(2).locator('td').nth(4)).toHaveClass(/text-warning-600/); // 25 min
        await expect(rows.nth(3).locator('td').nth(4)).toHaveClass(/text-error-600/);   // 35 min
        await expect(page.getByText('1–10 of 23')).toBeVisible();
    });

    test('paginates', async ({ page }) => {
        const api = await mockApi(page, { history: history() });
        await page.goto('/queue#history');

        await expect(page.getByRole('button', { name: 'Previous page' })).toBeDisabled();
        await page.getByRole('button', { name: 'Next page' }).click();
        await expect(page.getByText('11–20 of 23')).toBeVisible();
        await page.getByRole('button', { name: '3', exact: true }).click();
        await expect(page.getByText('21–23 of 23')).toBeVisible();
        await expect(page.getByRole('button', { name: 'Next page' })).toBeDisabled();
        expect(api.callsTo('GET', '/queue/history').map((c) => c.query.page)).toEqual(['1', '2', '3']);
    });

    test('filters by date range and clears it', async ({ page }) => {
        const api = await mockApi(page, { history: history() });
        await page.goto('/queue#history');

        await pickRange(page);
        await expect.poll(() => api.callsTo('GET', '/queue/history').at(-1).query.from).toBeTruthy();
        const filtered = api.callsTo('GET', '/queue/history').at(-1).query;
        expect(filtered.to).toBeTruthy();
        expect(filtered.page).toBe('1');

        await page.getByRole('button', { name: 'Clear date range' }).click();
        await expect.poll(() => api.callsTo('GET', '/queue/history').at(-1).query.from).toBeUndefined();
    });

    test('shows the empty state', async ({ page }) => {
        await mockApi(page);
        await page.goto('/queue#history');
        await expect(page.getByText('Nothing here for those dates')).toBeVisible();
    });
});
