import { test, expect } from '@playwright/test';
import { mockApi, barber, ticket } from './support/mock-api.js';

const sidebar = (page) => page.locator('#sidebar');
const shopStatus = (page) => sidebar(page).locator('[x-data]', { hasText: 'Shop status' });

test.describe('Sidebar navigation', () => {
    const pages = [
        ['Dashboard', '/', 'Sidecut VQS · Sidecut'],
        ['Statistics', '/statistics', 'Statistics · Sidecut'],
        ['Queue Control', '/queue-control', 'Queue Control · Sidecut'],
        ['Manage Queue', '/queue', 'Queue · Sidecut'],
        ['Messages', '/messages', 'Messages · Sidecut'],
    ];

    for (const [name, path, title] of pages) {
        test(`${name} link opens ${path} and is marked current`, async ({ page }) => {
            await mockApi(page);
            await page.goto('/queue-control');
            await sidebar(page).getByRole('link', { name, exact: true }).click();

            await expect(page).toHaveURL(new RegExp(path.replace('/', '\\/') + '$'));
            await expect(page).toHaveTitle(title);
            await expect(sidebar(page).getByRole('link', { name, exact: true })).toHaveAttribute('aria-current', 'page');
            await expect(sidebar(page).locator('[aria-current="page"]')).toHaveCount(1);
        });
    }

    test('public screens open in a new tab', async ({ page }) => {
        await mockApi(page);
        await page.goto('/');

        for (const [name, href] of [['Calling Board', '/queue-calling'], ['Join Page', '/customers']]) {
            const link = sidebar(page).getByRole('link', { name: new RegExp('^' + name) });
            await expect(link).toHaveAttribute('href', href);
            await expect(link).toHaveAttribute('target', '_blank');
        }
        await expect(page.getByRole('banner').getByRole('link', { name: 'Calling board' })).toHaveAttribute('target', '_blank');
    });

    test('the desktop toggle collapses and expands the sidebar', async ({ page }) => {
        await page.setViewportSize({ width: 1440, height: 900 });
        await mockApi(page);
        await page.goto('/');

        await expect(sidebar(page)).toHaveClass(/w-\[264px\]/);
        await page.getByRole('button', { name: 'Toggle navigation' }).click();
        await expect(sidebar(page)).toHaveClass(/w-\[84px\]/);
        await expect(sidebar(page).getByText('Shop status')).toBeHidden();
        await page.getByRole('button', { name: 'Toggle navigation' }).click();
        await expect(sidebar(page)).toHaveClass(/w-\[264px\]/);
    });

    test('on a phone the menu opens as a drawer and closes from the overlay', async ({ page }) => {
        await page.setViewportSize({ width: 390, height: 844 });
        await mockApi(page);
        await page.goto('/');

        await expect(sidebar(page)).toHaveClass(/-translate-x-full/);
        await page.getByRole('button', { name: 'Toggle navigation' }).click();
        await expect(sidebar(page)).toHaveClass(/translate-x-0/);
        const overlay = page.locator('div.fixed.inset-0.bg-gray-900\\/50'); // the dimmed overlay beside the drawer
        await expect(overlay).toBeVisible();
        await overlay.click({ position: { x: 360, y: 400 } });
        await expect(sidebar(page)).toHaveClass(/-translate-x-full/);
    });
});

test.describe('Theme', () => {
    test('defaults to dark and remembers the choice', async ({ page }) => {
        await mockApi(page);
        await page.goto('/');
        await expect(page.locator('html')).toHaveClass(/dark/);

        await page.getByRole('button', { name: 'Switch colour theme' }).click();
        await expect(page.locator('html')).not.toHaveClass(/dark/);
        expect(await page.evaluate(() => localStorage.getItem('theme'))).toBe('light');

        await page.goto('/statistics');
        await expect(page.locator('html')).not.toHaveClass(/dark/);
        await page.goto('/queue-calling');
        await expect(page.locator('html')).not.toHaveClass(/dark/);
    });

    test('the calling board defaults to dark too', async ({ page }) => {
        await mockApi(page);
        await page.goto('/queue-calling');
        await expect(page.locator('html')).toHaveClass(/dark/);
    });
});

test.describe('Shop status widget', () => {
    test('shows Open with live counts', async ({ page }) => {
        await mockApi(page, {
            tickets: [ticket(1, 1, 'A'), ticket(2, 2, 'B'), ticket(3, 3, 'C', { status: 'serving', barberId: 1 })],
        });
        await page.goto('/');

        await expect(shopStatus(page)).toContainText('Open');
        await expect(shopStatus(page).locator('dd').nth(0)).toHaveText('2');
        await expect(shopStatus(page).locator('dd').nth(1)).toHaveText('1');
        await expect(shopStatus(page).locator('dd').nth(2)).toContainText('2/3');
    });

    test('shows Closed when nobody is on duty', async ({ page }) => {
        await mockApi(page, { barbers: [barber(1, 'Alex', { active: false })] });
        await page.goto('/');
        await expect(shopStatus(page)).toContainText('Closed');
    });

    test('shows Offline when the API cannot be reached', async ({ page }) => {
        const api = await mockApi(page);
        api.fail['GET /barbers'] = { status: 500 };
        await page.goto('/messages');
        await expect(shopStatus(page)).toContainText('Offline');
    });

    test('updates straight away when a barber goes on duty', async ({ page }) => {
        await mockApi(page, { barbers: [barber(1, 'Alex', { active: false })] });
        await page.goto('/queue#barbers');
        await expect(shopStatus(page)).toContainText('Closed');

        await page.getByLabel('On duty: Alex').check({ force: true });
        await expect(shopStatus(page)).toContainText('Open');
    });

    test('links to the barbers tab', async ({ page }) => {
        await mockApi(page);
        await page.goto('/');
        await shopStatus(page).getByRole('link', { name: 'Manage barbers' }).click();
        await expect(page).toHaveURL(/\/queue#barbers$/);
        await expect(page.getByRole('tab', { name: 'Barbers' })).toHaveAttribute('aria-selected', 'true');
    });
});
