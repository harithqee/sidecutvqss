import { test, expect } from '@playwright/test';
import { mockApi, template, smsLog, toast } from './support/mock-api.js';

const card = (page, name) => page.getByRole('listitem').filter({ has: page.getByRole('heading', { name, exact: true }) });
const editor = (page) => page.getByRole('dialog');

test.describe('SMS templates', () => {
    test('renders each template with trigger, preview, size and state', async ({ page }) => {
        await mockApi(page);
        await page.goto('/messages');

        const join = card(page, 'Customer Joins Queue');
        await expect(join).toContainText('Sent when: Customer joins the queue');
        await expect(join.locator('span', { hasText: 'customerName' })).toBeVisible(); // variable highlighted
        await expect(join).toContainText(/\d+ chars · 1 SMS/);
        await expect(page.getByLabel('Send Customer Joins Queue')).toBeChecked();

        await expect(card(page, 'Service Complete')).toContainText('Paused ·');
        await expect(page.getByLabel('Send Service Complete')).not.toBeChecked();
    });

    test('creates a template, with validation, variable insertion and live preview', async ({ page }) => {
        const api = await mockApi(page);
        await page.goto('/messages');

        await page.getByRole('button', { name: 'New template' }).click();
        await expect(editor(page)).toContainText('New template');
        await editor(page).getByRole('button', { name: 'Create template' }).click();
        await expect(toast(page, 'Fill in the name, when it is sent, and the message.')).toBeVisible();

        await editor(page).getByLabel('Name').fill('Running Late');
        await editor(page).getByLabel('Sent when').fill('Staff taps Send SMS');
        await editor(page).getByLabel('Message').fill('Hi ');
        await editor(page).getByRole('button', { name: 'customerName' }).click();
        await editor(page).getByLabel('Message').press('End');
        await editor(page).getByLabel('Message').pressSequentially(', we are running late.');

        await expect(editor(page).getByLabel('Message')).toHaveValue('Hi {{customerName}}, we are running late.');
        await expect(editor(page)).toContainText('Fits in one SMS.');
        await expect(editor(page)).toContainText('41 / 500');

        await editor(page).getByRole('button', { name: 'Create template' }).click();
        await expect(toast(page, 'Template created.')).toBeVisible();
        await expect(editor(page)).toBeHidden();
        await expect(card(page, 'Running Late')).toBeVisible();
        expect(api.callsTo('POST', '/message-templates')[0].body).toEqual({
            name: 'Running Late', trigger_event: 'Staff taps Send SMS', message_body: 'Hi {{customerName}}, we are running late.', is_active: true,
        });
    });

    test('warns when a message will split into several SMS', async ({ page }) => {
        await mockApi(page);
        await page.goto('/messages');

        await page.getByRole('button', { name: 'New template' }).click();
        await editor(page).getByLabel('Message').fill('x'.repeat(200));
        await expect(editor(page)).toContainText('Splits into 2 SMS parts.');
    });

    test('edits a template and keeps the original if cancelled', async ({ page }) => {
        const api = await mockApi(page);
        await page.goto('/messages');

        await card(page, 'Turn Approaching').getByRole('button', { name: 'Edit' }).click();
        await expect(editor(page)).toContainText('Edit template');
        await expect(editor(page).getByLabel('Name')).toHaveValue('Turn Approaching');
        await editor(page).getByLabel('Name').fill('Discarded');
        await editor(page).getByRole('button', { name: 'Cancel' }).click();
        await expect(card(page, 'Turn Approaching')).toBeVisible();

        await card(page, 'Turn Approaching').getByRole('button', { name: 'Edit' }).click();
        await editor(page).getByLabel('Name').fill('Almost Your Turn');
        await editor(page).getByRole('button', { name: 'Save changes' }).click();
        await expect(toast(page, 'Template saved.')).toBeVisible();
        await expect(card(page, 'Almost Your Turn')).toBeVisible();
        expect(api.callsTo('PUT', '/message-templates/2')[0].body.name).toBe('Almost Your Turn');
    });

    test('the editor closes with Escape and the close button', async ({ page }) => {
        await mockApi(page);
        await page.goto('/messages');

        await page.getByRole('button', { name: 'New template' }).click();
        await page.keyboard.press('Escape');
        await expect(editor(page)).toBeHidden();

        await page.getByRole('button', { name: 'New template' }).click();
        await editor(page).getByRole('button', { name: 'Close' }).click();
        await expect(editor(page)).toBeHidden();
    });

    test('pauses and resumes a template', async ({ page }) => {
        const api = await mockApi(page);
        await page.goto('/messages');

        await page.getByLabel('Send Customer Joins Queue').uncheck({ force: true });
        await expect(toast(page, "Customer Joins Queue is paused. Customers won't get it.")).toBeVisible();
        await page.getByLabel('Send Service Complete').check({ force: true });
        await expect(toast(page, 'Service Complete will be sent.')).toBeVisible();
        expect(api.callsTo('PATCH', /toggle-active$/).map((c) => c.path)).toEqual([
            '/message-templates/1/toggle-active', '/message-templates/3/toggle-active',
        ]);
    });

    test('warns before deleting a template the queue sends automatically', async ({ page }) => {
        const api = await mockApi(page);
        await page.goto('/messages');

        await card(page, 'Customer Joins Queue').getByRole('button', { name: 'Delete' }).click();
        const dialog = page.getByRole('alertdialog');
        await expect(dialog).toContainText('The queue sends this text automatically.');
        await dialog.getByRole('button', { name: 'Cancel' }).click();
        expect(api.callsTo('DELETE', /message-templates/)).toHaveLength(0);
    });

    test('deletes a custom template', async ({ page }) => {
        const api = await mockApi(page);
        api.state.templates.push(template(7, 'Holiday Notice', 'Manual', 'We are closed tomorrow.'));
        await page.goto('/messages');

        await card(page, 'Holiday Notice').getByRole('button', { name: 'Delete' }).click();
        await expect(page.getByRole('alertdialog')).toContainText('This template will be removed for good.');
        await page.getByRole('alertdialog').getByRole('button', { name: 'Delete template' }).click();
        await expect(toast(page, 'Template deleted.')).toBeVisible();
        await expect(card(page, 'Holiday Notice')).toHaveCount(0);
        expect(api.callsTo('DELETE', '/message-templates/7')).toHaveLength(1);
    });

    test('shows errors when saving fails', async ({ page }) => {
        const api = await mockApi(page);
        api.fail['POST /message-templates'] = { status: 422 };
        api.fail['PATCH /message-templates/\\d+/toggle-active'] = { status: 500 };
        await page.goto('/messages');

        await page.getByRole('button', { name: 'New template' }).click();
        await editor(page).getByLabel('Name').fill('X');
        await editor(page).getByLabel('Sent when').fill('Y');
        await editor(page).getByLabel('Message').fill('Z');
        await editor(page).getByRole('button', { name: 'Create template' }).click();
        await expect(toast(page, 'Could not create template.')).toBeVisible();
        await expect(editor(page)).toBeVisible();
        await page.keyboard.press('Escape');

        await page.getByLabel('Send Customer Joins Queue').dispatchEvent('click');
        await expect(toast(page, 'Could not update template.')).toBeVisible();
        await expect(card(page, 'Customer Joins Queue')).not.toContainText('Paused'); // stays on when the save fails
    });

    test('shows the empty state', async ({ page }) => {
        await mockApi(page, { templates: [] });
        await page.goto('/messages');
        await expect(page.getByText('No templates yet')).toBeVisible();
    });
});

test.describe('Delivery log', () => {
    const logs = () => [
        smsLog(1, 'sent'),
        smsLog(2, 'failed', { message: '' }),
        smsLog(3, 'skipped', { name: null }),
        smsLog(4, 'pending', { message: 'A long message that is cut off in the table until you click it to read everything.' }),
        ...Array.from({ length: 10 }, (_, i) => smsLog(10 + i, 'sent')),
    ];
    const rows = (page) => page.locator('table tbody tr');

    test('shows each status as a badge', async ({ page }) => {
        await mockApi(page, { smsLogs: logs() });
        await page.goto('/messages');

        await expect(rows(page).nth(0)).toContainText('Sent');
        await expect(rows(page).nth(1)).toContainText('Failed');
        await expect(rows(page).nth(1)).toContainText('No message body');
        await expect(rows(page).nth(2)).toContainText('Skipped (Inactive)');
        await expect(rows(page).nth(2)).toContainText('Unknown');
        await expect(rows(page).nth(3)).toContainText('Pending');
        await expect(rows(page).nth(0).locator('p.rounded-full')).toHaveClass(/bg-green-50/);
        await expect(rows(page).nth(1).locator('p.rounded-full')).toHaveClass(/bg-red-50/);
    });

    test('filters by status', async ({ page }) => {
        const api = await mockApi(page, { smsLogs: logs() });
        await page.goto('/messages');

        await page.getByRole('button', { name: 'Failed', exact: true }).click();
        await expect(page.getByRole('button', { name: 'Failed', exact: true })).toHaveAttribute('aria-pressed', 'true');
        await expect(rows(page)).toHaveCount(1);
        expect(api.callsTo('GET', '/sms-logs').at(-1).query.status).toBe('failed');

        await page.getByRole('button', { name: 'Skipped', exact: true }).click();
        await expect(rows(page)).toHaveCount(1);
        await page.getByRole('button', { name: 'All', exact: true }).click();
        await expect(rows(page)).toHaveCount(10);
        expect(api.callsTo('GET', '/sms-logs').at(-1).query.status).toBeUndefined();
    });

    test('expands a long message', async ({ page }) => {
        await mockApi(page, { smsLogs: logs() });
        await page.goto('/messages');

        const toggle = rows(page).nth(3).locator('button[aria-expanded]');
        await expect(toggle).toHaveAttribute('aria-expanded', 'false');
        await toggle.click();
        await expect(toggle).toHaveAttribute('aria-expanded', 'true');
        await toggle.click();
        await expect(toggle).toHaveAttribute('aria-expanded', 'false');
    });

    test('paginates', async ({ page }) => {
        await mockApi(page, { smsLogs: logs() });
        await page.goto('/messages');

        await expect(page.getByText('1–10 of 14')).toBeVisible();
        await page.getByRole('button', { name: 'Next page' }).click();
        await expect(page.getByText('11–14 of 14')).toBeVisible();
        await expect(rows(page)).toHaveCount(4);
    });

    test('filters by date and clears the filter', async ({ page }) => {
        const api = await mockApi(page, { smsLogs: logs() });
        await page.goto('/messages');

        await page.getByLabel('Date range', { exact: true }).click();
        const days = page.locator('.flatpickr-calendar.open .flatpickr-day:not(.flatpickr-disabled):not(.prevMonthDay):not(.nextMonthDay)');
        await days.nth(0).click();
        await days.nth(1).click();
        await expect.poll(() => api.callsTo('GET', '/sms-logs').at(-1).query.from).toBeTruthy();

        await page.getByRole('button', { name: 'Clear date range' }).click();
        await expect.poll(() => api.callsTo('GET', '/sms-logs').at(-1).query.from).toBeUndefined();
    });

    test('shows the empty state', async ({ page }) => {
        await mockApi(page);
        await page.goto('/messages');
        await expect(page.getByText('No messages match')).toBeVisible();
    });
});
