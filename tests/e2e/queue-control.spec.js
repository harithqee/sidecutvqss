import { test, expect } from '@playwright/test';
import { mockApi, barber, ticket, toast } from './support/mock-api.js';

// Core call / start / finish / cancel / SMS flows live in customers.spec.js.
// This file covers the remaining states of /queue-control.

const nextUp = (page) => page.locator('section[aria-labelledby="next-heading"]');
const inChair = (page) => page.locator('section[aria-labelledby="serving-heading"]');
const waitingList = (page) => page.locator('section[aria-labelledby="waiting-heading"]');

test.describe('Queue Control', () => {
    test('shows the paused banner and closed empty state when nobody is on duty', async ({ page }) => {
        await mockApi(page, { barbers: [barber(1, 'Alex', { active: false })] });
        await page.goto('/queue-control');

        await expect(page.getByText('The queue is paused')).toBeVisible();
        await expect(page.getByRole('main').getByRole('link', { name: 'Manage barbers' })).toHaveAttribute('href', '/queue#barbers');
        await expect(nextUp(page).getByText('The shop is closed')).toBeVisible();
    });

    test('shows empty states when the shop is open but nobody is waiting', async ({ page }) => {
        await mockApi(page);
        await page.goto('/queue-control');

        await expect(page.getByText('The queue is paused')).toBeHidden();
        await expect(nextUp(page).getByText('Nobody is waiting')).toBeVisible();
        await expect(inChair(page).getByText('Nobody in the chair.')).toBeVisible();
        await expect(waitingList(page).getByText('The line is empty.')).toBeVisible();
    });

    test('orders the line by ticket number and splits next up from the rest', async ({ page }) => {
        await mockApi(page, {
            tickets: [
                ticket(3, 12, 'Third', { joined: 2 }),
                ticket(1, 10, 'First', { joined: 30 }),
                ticket(2, 11, 'Second', { joined: 20 }),
                ticket(4, 9, 'In Chair', { status: 'serving', barberId: 1, joined: 40, served: 15 }),
            ],
        });
        await page.goto('/queue-control');

        await expect(nextUp(page).getByRole('heading', { name: 'First' })).toBeVisible();
        await expect(nextUp(page)).toContainText('#010');
        await expect(nextUp(page)).toContainText('Waited 30 min');
        await expect(inChair(page).getByRole('heading', { name: 'In Chair' })).toBeVisible();
        await expect(inChair(page)).toContainText('15 min in chair');

        const rows = waitingList(page).locator('li');
        await expect(rows).toHaveCount(2);
        await expect(rows.nth(0)).toContainText('Second');
        await expect(rows.nth(1)).toContainText('Third');
        await expect(waitingList(page).getByRole('heading', { name: /Waiting/ })).toContainText('3');
        await expect(waitingList(page)).toContainText('Longest wait 30 min');
    });

    test('waiting rows have SMS and Cancel buttons, and Call/Start in the menu', async ({ page }) => {
        const api = await mockApi(page, { tickets: [ticket(1, 10, 'First'), ticket(2, 11, 'Second')] });
        await page.goto('/queue-control');
        const row = waitingList(page).locator('li', { hasText: 'Second' });

        await row.getByRole('button', { name: 'Send SMS update to Second' }).click();
        await expect(toast(page, 'SMS sent to Second.')).toBeVisible();

        await row.getByRole('button', { name: 'More actions for Second' }).click();
        const callItem = row.getByRole('button', { name: 'Call to chair' });
        await expect(callItem).toBeVisible();
        await callItem.click();
        await expect(row.getByText('Called')).toBeVisible();
        await expect(callItem).toBeHidden(); // let the menu finish closing before reopening it
        expect(api.callsTo('POST', '/queue/2/call')).toHaveLength(1);

        await row.getByRole('button', { name: 'More actions for Second' }).click();
        const startItem = row.getByRole('button', { name: 'Start service' });
        await expect(startItem).toBeVisible();
        await startItem.click();
        await expect(inChair(page).getByRole('heading', { name: 'Second' })).toBeVisible();

        await nextUp(page).getByRole('button', { name: 'Cancel ticket' }).click();
        await page.getByRole('alertdialog').getByRole('button', { name: 'Cancel ticket' }).click();
        await expect(toast(page, '#010 was cancelled.')).toBeVisible();
    });

    test('cancelling from a waiting row asks for confirmation', async ({ page }) => {
        const api = await mockApi(page, { tickets: [ticket(1, 10, 'First'), ticket(2, 11, 'Second')] });
        await page.goto('/queue-control');

        await waitingList(page).getByRole('button', { name: 'Cancel ticket for Second' }).click();
        const dialog = page.getByRole('alertdialog');
        await expect(dialog).toContainText('Cancel #011?');
        await dialog.getByRole('button', { name: 'Keep in queue' }).click();
        expect(api.callsTo('PATCH', '/queue/2/status')).toHaveLength(0);

        await waitingList(page).getByRole('button', { name: 'Cancel ticket for Second' }).click();
        await dialog.getByRole('button', { name: 'Cancel ticket' }).click();
        await expect(waitingList(page).getByText('Second')).toHaveCount(0);
        expect(api.callsTo('PATCH', '/queue/2/status')[0].body).toEqual({ status: 'canceled' });
    });

    for (const [smsStatus, message] of [
        ['sent', 'Service finished and receipt sent.'],
        ['failed', 'Service finished, but the SMS receipt failed to send.'],
        ['skipped', 'Service finished. No receipt sent because that SMS template is turned off.'],
    ]) {
        test(`finishing a service reports a ${smsStatus} receipt`, async ({ page }) => {
            await mockApi(page, { smsStatus, tickets: [ticket(1, 10, 'Chair Guy', { status: 'serving', barberId: 1 })] });
            await page.goto('/queue-control');

            await inChair(page).getByRole('button', { name: 'Finish service' }).click();
            await expect(toast(page, message)).toBeVisible();
            await expect(inChair(page).getByText('Nobody in the chair.')).toBeVisible();
        });
    }

    test('in-chair cards have SMS and Cancel buttons', async ({ page }) => {
        const api = await mockApi(page, { tickets: [ticket(1, 10, 'Chair Guy', { status: 'serving', barberId: 1 })] });
        await page.goto('/queue-control');
        const card = inChair(page).locator('article');

        await card.getByRole('button', { name: 'Send SMS update' }).click();
        await expect(toast(page, 'SMS sent to Chair Guy.')).toBeVisible();
        expect(api.callsTo('POST', '/queue/1/sms')).toHaveLength(1);

        await card.getByRole('button', { name: 'Cancel ticket' }).click();
        await page.getByRole('alertdialog').getByRole('button', { name: 'Cancel ticket' }).click();
        await expect(card).toHaveCount(0);
    });

    test('barber filter shows that barber plus no-preference customers and is remembered', async ({ page }) => {
        await mockApi(page, {
            tickets: [
                ticket(1, 10, 'For Alex', { barberId: 1 }),
                ticket(2, 11, 'For Sam', { barberId: 2 }),
                ticket(3, 12, 'Anyone', { barberId: null }),
            ],
        });
        await page.goto('/queue-control');
        const filter = page.getByLabel('Show customers for');

        await expect(filter.locator('option')).toHaveText(['All barbers', 'Alex', 'Sam', 'Marzuki (off duty)']);
        await filter.selectOption('2');
        await expect(nextUp(page).getByRole('heading', { name: 'For Sam' })).toBeVisible();
        await expect(waitingList(page).getByText('Anyone')).toBeVisible();
        await expect(page.getByText('For Alex')).toHaveCount(0);

        await page.reload();
        await expect(page.getByLabel('Show customers for')).toHaveValue('2');
        await expect(page.getByText('For Alex')).toHaveCount(0);
    });

    test('shows a toast when a new customer joins while the page is open', async ({ page }) => {
        const api = await mockApi(page, { tickets: [ticket(1, 10, 'First')] });
        await page.goto('/queue-control');
        await expect(nextUp(page).getByRole('heading', { name: 'First' })).toBeVisible();

        api.state.tickets.push(ticket(2, 11, 'Walk In'));
        await expect(toast(page, 'Walk In · #011')).toBeVisible({ timeout: 8000 });
        await expect(waitingList(page).getByText('Walk In')).toBeVisible();
    });

    test('join alerts setting is remembered across reloads', async ({ page }) => {
        await mockApi(page);
        await page.goto('/queue-control');

        await page.getByRole('button', { name: 'Alerts off' }).click();
        await expect(page.getByRole('button', { name: 'Alerts on' })).toHaveAttribute('aria-pressed', 'true');
        await page.reload();
        await expect(page.getByRole('button', { name: 'Alerts on' })).toBeVisible();

        await page.getByRole('button', { name: 'Alerts on' }).click();
        await expect(toast(page, 'New customers still appear in the list.')).toBeVisible();
    });

    test('shows Reconnecting when the queue cannot be reached, and recovers', async ({ page }) => {
        const api = await mockApi(page);
        await page.goto('/queue-control');
        await expect(page.getByText('Live', { exact: true })).toBeVisible();

        api.fail['GET /queue'] = { status: 500 };
        await expect(page.getByText('Reconnecting…')).toBeVisible({ timeout: 8000 });

        delete api.fail['GET /queue'];
        await expect(page.getByText('Live', { exact: true })).toBeVisible({ timeout: 8000 });
    });

    test('Call again keeps the ticket waiting and re-calls it', async ({ page }) => {
        const api = await mockApi(page, { tickets: [ticket(1, 10, 'First', { calling: true })] });
        await page.goto('/queue-control');

        await expect(nextUp(page).getByText('On the calling board now')).toBeVisible();
        await nextUp(page).getByRole('button', { name: 'Call again' }).click();
        await expect(toast(page, '#010 is on the calling board.')).toBeVisible();
        expect(api.callsTo('POST', '/queue/1/call')).toHaveLength(1);
        expect(api.callsTo('PATCH', '/queue/1/status')).toHaveLength(0);
    });
});
