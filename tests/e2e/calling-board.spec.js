import { test, expect } from '@playwright/test';
import { mockApi, barber, ticket } from './support/mock-api.js';

const nowCalling = (page) => page.locator('section[aria-live]');
const upNext = (page) => page.locator('section', { has: page.getByRole('heading', { name: 'Up next' }) });
const recent = (page) => page.locator('section', { has: page.getByRole('heading', { name: 'Called recently' }) });

const board = (overrides = {}) => ({
    queue_active: true,
    current_call: { id: 3, queue_number: 13, status: 'in_queue', barber: 'Alex', call_version: 1 },
    recent_calls: [{ id: 2, queue_number: 12, barber: 'Sam' }, { id: 1, queue_number: 11, barber: null }],
    upcoming: [
        { id: 3, queue_number: 13, barber: 'Alex' },
        { id: 4, queue_number: 14, barber: null },
        { id: 5, queue_number: 15, barber: 'Sam' },
    ],
    waiting_count: 3,
    updated_at: new Date().toISOString(),
    ...overrides,
});

test.beforeEach(async ({ page }) => {
    // Record speech instead of talking, so announcements can be asserted.
    await page.addInitScript(() => {
        window.__spoken = [];
        window.speechSynthesis.speak = (utterance) => window.__spoken.push(utterance.text);
        window.speechSynthesis.cancel = () => {};
    });
});

test.describe('Calling board', () => {
    test('shows the number being called, who to see, and the line', async ({ page }) => {
        await mockApi(page, { callingBoard: board() });
        await page.goto('/queue-calling');

        await expect(nowCalling(page)).toContainText('Now calling');
        await expect(nowCalling(page)).toContainText('#013');
        await expect(nowCalling(page)).toContainText('Please go to Alex');

        // The person being called is not repeated under "Up next".
        await expect(upNext(page).getByRole('listitem')).toHaveCount(2);
        await expect(upNext(page).getByRole('listitem').nth(0)).toContainText('#014');
        await expect(upNext(page).getByRole('listitem').nth(0)).toContainText('Any barber');
        await expect(upNext(page).getByRole('listitem').nth(1)).toContainText('Sam');
        await expect(upNext(page)).toContainText('3 waiting');

        await expect(recent(page).getByRole('listitem')).toHaveCount(3);
        await expect(recent(page).getByRole('listitem').nth(0)).toContainText('#012');
        await expect(recent(page).getByRole('listitem').nth(1)).toContainText('Counter');
        await expect(recent(page).getByRole('listitem').nth(2)).toContainText('—');
    });

    test('says "the counter" when the called customer has no barber', async ({ page }) => {
        await mockApi(page, { callingBoard: board({ current_call: { id: 9, queue_number: 9, barber: null, call_version: 1 } }) });
        await page.goto('/queue-calling');
        await expect(nowCalling(page)).toContainText('Please go to the counter');
    });

    test('shows a dash while nobody has been called', async ({ page }) => {
        await mockApi(page, { callingBoard: board({ current_call: null }) });
        await page.goto('/queue-calling');
        await expect(nowCalling(page)).toContainText('Waiting for the next call');
    });

    test('says how many more are waiting beyond the first five', async ({ page }) => {
        const upcoming = Array.from({ length: 8 }, (_, i) => ({ id: 20 + i, queue_number: 20 + i, barber: null }));
        await mockApi(page, { callingBoard: board({ current_call: null, upcoming, waiting_count: 9 }) });
        await page.goto('/queue-calling');

        await expect(upNext(page).getByRole('listitem')).toHaveCount(5);
        await expect(upNext(page)).toContainText('+ 4 more');
    });

    test('shows the paused screen when no barber is on duty', async ({ page }) => {
        await mockApi(page, { barbers: [barber(1, 'Alex', { active: false })] });
        await page.goto('/queue-calling');

        await expect(nowCalling(page)).toContainText('Queue paused');
        await expect(nowCalling(page)).toContainText("We're not taking customers right now");
        await expect(recent(page)).toBeHidden();
        await expect(page.getByText('Paused', { exact: true })).toBeVisible();
    });

    test('shows the join address and the time', async ({ page }) => {
        await mockApi(page, { callingBoard: board() });
        await page.goto('/queue-calling');

        await expect(page.getByText('Join the queue from your phone:')).toContainText('/customers');
        await expect(page.locator('header p.tabular-nums')).toHaveText(/\d{1,2}:\d{2}/);
        await expect(page.getByText(/^Updated /)).toBeVisible();
    });

    test('highlights and announces a new call when sound is on', async ({ page }) => {
        const api = await mockApi(page, { tickets: [ticket(1, 13, 'First', { barberId: 1 }), ticket(2, 14, 'Second', { barberId: 2 })] });
        await page.goto('/queue-calling');
        await expect(nowCalling(page)).toContainText('Waiting for the next call');

        await page.getByRole('button', { name: 'Turn spoken announcements on' }).click();
        await expect(page.getByRole('button', { name: 'Turn spoken announcements off' })).toHaveAttribute('aria-pressed', 'true');

        // Staff call #014 from Queue Control; the board picks it up on its next poll.
        const second = api.state.tickets.find((t) => t.id === 2);
        second.is_calling = true;
        second.call_version = 1;

        await expect(nowCalling(page)).toContainText('#014', { timeout: 8000 });
        await expect(nowCalling(page)).toHaveClass(/bg-brand-500/);
        await expect.poll(() => page.evaluate(() => window.__spoken)).toEqual(['Number 14, please go to Sam.']);
        await expect(nowCalling(page)).not.toHaveClass(/bg-brand-500/, { timeout: 10000 });
    });

    test('a repeat call (Call again) is announced again', async ({ page }) => {
        const api = await mockApi(page, { tickets: [ticket(1, 13, 'First', { barberId: 1, calling: true })] });
        await page.goto('/queue-calling');
        await page.getByRole('button', { name: 'Turn spoken announcements on' }).click();
        await expect(nowCalling(page)).toContainText('#013');

        api.state.tickets[0].call_version += 1;
        await expect.poll(() => page.evaluate(() => window.__spoken), { timeout: 8000 }).toEqual(['Number 13, please go to Alex.']);
    });

    test('stays quiet when sound is off', async ({ page }) => {
        const api = await mockApi(page, { tickets: [ticket(1, 13, 'First', { barberId: 1 })] });
        await page.goto('/queue-calling');

        api.state.tickets[0].is_calling = true;
        api.state.tickets[0].call_version = 1;
        await expect(nowCalling(page)).toContainText('#013', { timeout: 8000 });
        await page.waitForTimeout(500);
        expect(await page.evaluate(() => window.__spoken)).toEqual([]);
    });

    test('remembers the sound setting', async ({ page }) => {
        await mockApi(page, { callingBoard: board() });
        await page.goto('/queue-calling');

        await page.getByRole('button', { name: 'Turn spoken announcements on' }).click();
        await page.reload();
        await expect(page.getByRole('button', { name: 'Turn spoken announcements off' })).toBeVisible();
    });

    test('shows Reconnecting when the board cannot load, then recovers', async ({ page }) => {
        const api = await mockApi(page, { callingBoard: board() });
        await page.goto('/queue-calling');
        await expect(page.getByText('Live', { exact: true })).toBeVisible();

        api.fail['GET /queue/calling-board'] = { status: 500 };
        await expect(page.getByText('Reconnecting', { exact: true })).toBeVisible({ timeout: 8000 });
        delete api.fail['GET /queue/calling-board'];
        await expect(page.getByText('Live', { exact: true })).toBeVisible({ timeout: 8000 });
    });

    test('has a full-screen button', async ({ page }) => {
        await mockApi(page, { callingBoard: board() });
        await page.goto('/queue-calling');
        await expect(page.getByRole('button', { name: 'Toggle full screen' })).toBeVisible();
    });
});
