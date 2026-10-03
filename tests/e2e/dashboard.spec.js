import { test, expect } from '@playwright/test';
import { mockApi, barber, ticket, defaultState } from './support/mock-api.js';

const serverStatus = (page) => page.locator('[x-data]', { has: page.getByRole('heading', { name: 'Server Status', exact: true }) }).last();
const queueUsage = (page) => page.locator('div[x-data]', { has: page.getByRole('heading', { name: 'Queue Usage' }) }).last();

test.describe('Dashboard', () => {
    test('shows today\'s summary and the queueing-model cards', async ({ page }) => {
        await mockApi(page);
        await page.goto('/');

        for (const [label, value] of [
            ['Customers Today', '12'], ['Avg Wait Time', '14 min'], ['Avg Service Time', '22 min'], ['Completion Rate', '90%'],
            ['System Utilization (ρ)', '40%'], ['Predicted Wait (M/M/S)', '9 min'], ['Avg in Queue (Lq)', '1.2'], ['Avg in System (L)', '2.1'],
        ]) {
            const cardEl = page.locator('div.rounded-2xl', { has: page.getByText(label, { exact: true }) }).last();
            await expect(cardEl).toContainText(value);
        }
    });

    test('flags model cards that need watching', async ({ page }) => {
        await mockApi(page, { summary: { ...defaultState().summary, utilization_pct: 92, predicted_wait_minutes: 30 } });
        await page.goto('/');
        const util = page.locator('div.rounded-2xl', { has: page.getByText('System Utilization (ρ)') }).last();
        await expect(util).toContainText('Watch');
    });

    test.describe('Server Status', () => {
        test('lists barbers with what they are doing', async ({ page }) => {
            await mockApi(page, {
                tickets: [ticket(1, 4, 'Chair Guy', { status: 'serving', barberId: 1, served: 12 }), ticket(2, 5, 'Waiting Guy')],
            });
            await page.goto('/');
            const card = serverStatus(page);

            await expect(card).toContainText('2 of 3 barbers on duty');
            await expect(card.getByRole('listitem').filter({ hasText: 'Alex' })).toContainText('#004');
            await expect(card.getByRole('listitem').filter({ hasText: 'Alex' })).toContainText('12 min in chair');
            await expect(card.getByRole('listitem').filter({ hasText: 'Sam' })).toContainText('Free');
            await expect(card.getByRole('listitem').filter({ hasText: 'Marzuki' })).toContainText('Off duty');
            await expect(card.getByRole('listitem').first()).toContainText('Alex'); // on-duty barbers first
            await expect(card.getByRole('listitem').last()).toContainText('Marzuki');
            await expect(card.getByRole('link', { name: 'Manage' })).toHaveAttribute('href', '/queue#barbers');
        });

        test('warns when nobody is on duty', async ({ page }) => {
            await mockApi(page, { barbers: [barber(1, 'Alex', { active: false })] });
            await page.goto('/');
            await expect(serverStatus(page)).toContainText('No barbers are on duty, so the queue is paused.');
        });

        test('shows an empty state with no barbers', async ({ page }) => {
            await mockApi(page, { barbers: [] });
            await page.goto('/');
            await expect(serverStatus(page)).toContainText('No barbers added yet.');
        });
    });

    test.describe('Queue Usage', () => {
        const busyQueue = () => [ticket(1, 1, 'A'), ticket(2, 2, 'B'), ticket(3, 3, 'C')];

        for (const [pct, label, message] of [
            [40, 'Available', 'Queue has room to spare.'],
            [75, 'Busy', 'Queue is busy but manageable.'],
            [95, 'Near Full', 'Queue is nearly at capacity.'],
        ]) {
            test(`shows ${label} at ${pct}% utilization`, async ({ page }) => {
                const api = await mockApi(page, { tickets: busyQueue() });
                api.state.summary = { ...api.state.summary, utilization_pct: pct };
                await page.goto('/');
                const card = queueUsage(page);

                await expect(card).toContainText(`${pct}%`);
                await expect(card).toContainText(label);
                await expect(card).toContainText(message);
                await expect(card).toContainText('In Queue Now');
            });
        }

        test('reads 0% when the line is empty', async ({ page }) => {
            await mockApi(page, { tickets: [] });
            await page.goto('/');
            await expect(queueUsage(page)).toContainText('Available');
            await expect(queueUsage(page)).toContainText('0%');
        });

        test('shows Paused when no barber is on duty', async ({ page }) => {
            await mockApi(page, { barbers: [barber(1, 'Alex', { active: false })] });
            await page.goto('/');
            await expect(queueUsage(page)).toContainText('Queue usage is paused because no barbers are active.');
            await expect(queueUsage(page).getByRole('button', { name: 'Paused' })).toBeVisible();
        });

        test('live updates can be paused and resumed', async ({ page }) => {
            await mockApi(page, { tickets: busyQueue() });
            await page.goto('/');
            const toggle = queueUsage(page).getByRole('button', { name: 'Live' });

            await toggle.click();
            await expect(queueUsage(page).getByRole('button', { name: 'Paused' })).toHaveAttribute('aria-pressed', 'false');
            await queueUsage(page).getByRole('button', { name: 'Paused' }).click();
            await expect(queueUsage(page).getByRole('button', { name: 'Live' })).toHaveAttribute('aria-pressed', 'true');
        });
    });

    test('Queue Statistics chart renders and switches tabs', async ({ page }) => {
        const api = await mockApi(page);
        await page.goto('/');

        const chart = page.locator('[x-data]', { has: page.getByRole('heading', { name: 'Queue Statistics' }) }).last();
        await expect(chart.locator('svg.apexcharts-svg')).toBeVisible();
        for (const name of ['Peak Hours', 'Wait Times', 'Overview']) {
            await chart.getByRole('button', { name }).click();
        }
        expect(api.callsTo('GET', '/stats/hourly').length).toBeGreaterThan(0);
    });
});
