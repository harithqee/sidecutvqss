import { test, expect } from '@playwright/test';
import { mockApi } from './support/mock-api.js';

const component = (page, heading) => page.locator('[x-data]', { has: page.getByRole('heading', { name: heading, exact: true }) }).last();

test.describe('Statistics', () => {
    test('renders the summary, trend chart and monthly report', async ({ page }) => {
        const api = await mockApi(page);
        await page.goto('/statistics');

        await expect(page.getByText('Customers Today')).toBeVisible();
        await expect(component(page, 'Queue Statistics').locator('svg.apexcharts-svg')).toBeVisible();
        await expect(component(page, 'Monthly Report').locator('svg.apexcharts-svg')).toBeVisible();
        expect(api.callsTo('GET', '/stats/monthly-report')).toHaveLength(1);
        expect(api.callsTo('GET', '/stats/hourly')).toHaveLength(1);
    });

    test.describe('Barber Performance', () => {
        test('ranks barbers with counts, shares and a Top tag', async ({ page }) => {
            await mockApi(page);
            await page.goto('/statistics');
            const card = component(page, 'Barber Performance');
            const rows = card.getByRole('listitem');

            await expect(card).toContainText('8 customers served today');
            await expect(rows).toHaveCount(3);
            await expect(rows.nth(0)).toContainText('Alex');
            await expect(rows.nth(0).getByText('Top', { exact: true })).toBeVisible();
            await expect(rows.nth(0)).toContainText('75%');
            await expect(rows.nth(1)).toContainText('Sam');
            await expect(rows.nth(1)).toContainText('25%');
            await expect(rows.nth(1).getByText('Top', { exact: true })).toBeHidden();
            await expect(rows.nth(2)).toContainText('0%');
        });

        test('filters by date range and resets to today', async ({ page }) => {
            const api = await mockApi(page);
            await page.goto('/statistics');
            const card = component(page, 'Barber Performance');

            await card.getByLabel('Date range', { exact: true }).click();
            const days = page.locator('.flatpickr-calendar.open .flatpickr-day:not(.flatpickr-disabled):not(.prevMonthDay):not(.nextMonthDay)');
            await days.nth(0).click();
            await days.nth(1).click();

            await expect(card).toContainText('in the selected range');
            const last = api.callsTo('GET', '/stats/barber-performance').at(-1).query;
            expect(last.from).toMatch(/^\d{4}-\d{2}-\d{2}$/);
            expect(last.to).toMatch(/^\d{4}-\d{2}-\d{2}$/);

            await card.getByRole('button', { name: 'Clear date range' }).click();
            await expect(card).toContainText('customers served today');
            expect(api.callsTo('GET', '/stats/barber-performance').at(-1).query.from).toBeUndefined();
        });

        test('explains when nobody has finished a haircut yet', async ({ page }) => {
            await mockApi(page, { performance: [{ name: 'Alex', completed: 0 }] });
            await page.goto('/statistics');
            const card = component(page, 'Barber Performance');
            await expect(card).toContainText('Nobody has finished a haircut in this range yet.');
            await expect(card.getByText('Top', { exact: true })).toBeHidden();
        });

        test('shows the empty state with no barbers', async ({ page }) => {
            await mockApi(page, { performance: [] });
            await page.goto('/statistics');
            await expect(component(page, 'Barber Performance')).toContainText('No barbers yet.');
        });
    });
});
