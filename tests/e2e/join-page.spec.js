import { test, expect } from '@playwright/test';
import { mockApi, barber, ticket } from './support/mock-api.js';

const nameField = (page) => page.getByPlaceholder('e.g. John Tan');
const phoneField = (page) => page.getByPlaceholder('e.g. 012-345 6789');

test.describe('Join page (/customers)', () => {
    test('shows the closed state when no barber is on duty', async ({ page }) => {
        await mockApi(page, { barbers: [barber(1, 'Alex', { active: false })] });
        await page.goto('/customers');

        await expect(page.getByText("Sorry, we're closed")).toBeVisible();
        await expect(page.getByRole('button', { name: 'Join Queue' })).toHaveCount(0);
    });

    test('lists only on-duty barbers as preferences', async ({ page }) => {
        await mockApi(page);
        await page.goto('/customers');

        const options = page.locator('select option');
        await expect(options).toHaveText(['No preference', 'Alex — Barber', 'Sam — Barber']);
    });

    test('validates name and phone before sending anything', async ({ page }) => {
        const api = await mockApi(page);
        await page.goto('/customers');
        const join = page.getByRole('button', { name: 'Join Queue' });

        await join.click();
        await expect(page.getByText('Please enter your name.')).toBeVisible();
        await expect(page.getByText('Please enter your phone number.')).toBeVisible();

        await nameField(page).fill('Ahmad');
        await phoneField(page).fill('abc');
        await join.click();
        await expect(page.getByText('Please enter a valid phone number.')).toBeVisible();
        expect(api.callsTo('POST', '/queue')).toHaveLength(0);
    });

    test('joins the queue with a preferred barber and shows the ticket', async ({ page }) => {
        const api = await mockApi(page, { joinResponse: { queueNumber: 21, wait: 15 } });
        await page.goto('/customers');

        await nameField(page).fill('Ahmad');
        await phoneField(page).fill('012-345 6789');
        await page.locator('select').selectOption('2');
        await page.getByRole('button', { name: 'Join Queue' }).click();

        await expect(page.getByText('You are in the queue!')).toBeVisible();
        await expect(page.getByText('#21')).toBeVisible();
        await expect(page.getByText('15 min')).toBeVisible();
        await expect(page.getByText('Sam', { exact: true })).toBeVisible();
        expect(api.callsTo('POST', '/queue')[0].body).toEqual({ customer_name: 'Ahmad', customer_phone: '012-345 6789', barber_id: '2' });
    });

    test('joins with no preference and can start again', async ({ page }) => {
        await mockApi(page);
        await page.goto('/customers');

        await nameField(page).fill('Ahmad');
        await phoneField(page).fill('0123456789');
        await page.getByRole('button', { name: 'Join Queue' }).click();
        await expect(page.getByText('Any available')).toBeVisible();

        await page.getByRole('button', { name: 'Join Another Queue' }).click();
        await expect(nameField(page)).toHaveValue('');
        await expect(page.getByRole('button', { name: 'Join Queue' })).toBeVisible();
    });

    test('shows the server message when joining fails', async ({ page }) => {
        const api = await mockApi(page);
        api.fail['POST /queue'] = { status: 409, message: 'The queue is closed because no barbers are active. Please try again later.' };
        await page.goto('/customers');

        await nameField(page).fill('Ahmad');
        await phoneField(page).fill('0123456789');
        await page.getByRole('button', { name: 'Join Queue' }).click();

        await expect(page.getByText('The queue is closed because no barbers are active.', { exact: false })).toBeVisible();
        await expect(page.getByText('You are in the queue!')).toHaveCount(0);
    });

    test.describe('status lookup', () => {
        test.beforeEach(async ({ page }) => {
            await mockApi(page, {
                tickets: [
                    ticket(1, 7, 'First', { barberId: 1 }),
                    ticket(2, 8, 'Ahmad', { barberId: 1 }),
                    ticket(3, 9, 'In Chair', { status: 'serving', barberId: 2 }),
                ],
            });
            await page.goto('/customers');
            await page.getByRole('button', { name: 'Already in queue? Check status' }).click();
        });

        test('requires a queue number', async ({ page }) => {
            await page.getByRole('button', { name: 'Check Status' }).click();
            await expect(page.getByText('Please enter your queue number.')).toBeVisible();
        });

        test('shows position and wait for a waiting ticket', async ({ page }) => {
            await page.getByPlaceholder('e.g. 128').fill('8');
            await page.getByRole('button', { name: 'Check Status' }).click();

            await expect(page.getByText('#8', { exact: true })).toBeVisible();
            await expect(page.getByText('Ahmad')).toBeVisible();
            await expect(page.getByText('In Queue', { exact: true })).toBeVisible();
            await expect(page.getByText('#2 in line')).toBeVisible();
            await expect(page.getByText('20 min')).toBeVisible();
        });

        test('shows "Being Served" for a ticket in the chair', async ({ page }) => {
            await page.getByPlaceholder('e.g. 128').fill('9');
            await page.keyboard.press('Enter');
            await expect(page.getByText('Being Served')).toBeVisible();
        });

        test('reports an unknown queue number', async ({ page }) => {
            await page.getByPlaceholder('e.g. 128').fill('999');
            await page.getByRole('button', { name: 'Check Status' }).click();
            await expect(page.getByText('No ticket found with that queue number today.')).toBeVisible();
        });

        test('goes back to the join form', async ({ page }) => {
            await page.getByRole('button', { name: 'Back' }).click();
            await expect(page.getByRole('button', { name: 'Join Queue' })).toBeVisible();
        });
    });
});
