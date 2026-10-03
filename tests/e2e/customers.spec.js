import { test, expect } from '@playwright/test';

const barberData = [
    { id: 1, name: 'Alex', role: 'Barber', is_active: true },
    { id: 2, name: 'Sam', role: 'Barber', is_active: true },
];

function ticket(id, queueNumber, customerName, status = 'in_queue', barberId = 1) {
    const now = new Date().toISOString();
    return {
        id,
        queue_number: queueNumber,
        customer_name: customerName,
        customer_phone: '0123456789',
        status,
        is_calling: false,
        call_version: 0,
        barber_id: barberId,
        barber: barberData.find((barber) => barber.id === barberId),
        joined_at: now,
        served_at: status === 'serving' ? now : null,
        finished_at: null,
    };
}

async function mockQueue(page, initialTickets, options = {}) {
    let tickets = initialTickets.map((item) => ({ ...item }));
    const calls = [];

    await page.route('**/api/barbers', (route) => route.fulfill({ json: barberData }));
    await page.route('**/api/queue/**', async (route) => {
        const request = route.request();
        const url = new URL(request.url());
        const match = url.pathname.match(/\/api\/queue\/(\d+)\/(call|status|sms)$/);
        if (!match) return route.continue();

        const [, rawId, action] = match;
        const id = Number(rawId);
        calls.push({ id, action, method: request.method(), body: request.postDataJSON?.() });
        const current = tickets.find((item) => item.id === id);

        if (options.failAction === action) {
            return route.fulfill({ status: options.failStatus ?? 500, json: { message: 'Mock request failed.' } });
        }
        if (!current) return route.fulfill({ status: 404, json: { message: 'Ticket not found.' } });

        if (action === 'call') {
            current.is_calling = true;
            current.call_version += 1;
            return route.fulfill({ json: { ...current, called_at: new Date().toISOString() } });
        }
        if (action === 'sms') {
            return route.fulfill({ status: 201, json: { message: 'sent', text: 'Mock SMS sent.' } });
        }

        const { status } = request.postDataJSON();
        if (options.failStatusUpdate) {
            return route.fulfill({ status: options.failStatusUpdate, json: { message: 'Mock status update failed.' } });
        }
        current.status = status;
        if (status === 'serving') current.served_at ??= new Date().toISOString();
        else tickets = tickets.filter((item) => item.id !== id);
        return route.fulfill({ json: current });
    });
    await page.route('**/api/queue', (route) => route.fulfill({ json: tickets }));

    return calls;
}

async function openManageQueue(page) {
    await page.goto('/queue-control');
    await expect(page.getByRole('heading', { name: 'Queue Control', level: 1 })).toBeVisible();
    await expect(page.getByText('Live', { exact: true })).toBeVisible();
}

// Next-up and in-the-chair tickets render as articles headed by the customer's name.
function ticketCard(page, name) {
    return page.locator('article').filter({ has: page.getByRole('heading', { name }) });
}

function confirmDialog(page) {
    return page.getByRole('alertdialog');
}

async function clickCancel(card) {
    await card.getByRole('button', { name: 'Cancel ticket' }).click();
}

test('customers page loads and validates the queue form', async ({ page }) => {
    const response = await page.goto('/customers');

    expect(response?.status()).toBe(200);
    await expect(page).toHaveTitle('Join Queue — Sidecut');
    await expect(page.getByText('Join the queue in seconds')).toBeVisible();

    const joinButton = page.getByRole('button', { name: 'Join Queue' });
    await expect(joinButton).toBeVisible();

    await joinButton.click();
    await expect(page.getByText('Please enter your name.')).toBeVisible();
    await expect(page.getByText('Please enter your phone number.')).toBeVisible();

    await page.getByPlaceholder('e.g. John Tan').fill('Playwright Check');
    await page.getByPlaceholder('e.g. 012-345 6789').fill('abc');
    await joinButton.click();
    await expect(page.getByText('Please enter a valid phone number.')).toBeVisible();
});

test('manage queue page loads live queue controls', async ({ page }) => {
    const response = await page.goto('/queue-control');

    expect(response?.status()).toBe(200);
    await expect(page).toHaveTitle('Queue Control · Sidecut');
    await expect(page.getByRole('heading', { name: 'Queue Control', level: 1 })).toBeVisible();
    await expect(page.getByText('Call the next customer, start their cut and finish up.', { exact: false })).toBeVisible();
    await expect(page.getByText('Live', { exact: true })).toBeVisible();

    const barberFilter = page.getByLabel('Show customers for');
    await expect(barberFilter).toBeVisible();
    await expect(barberFilter).toHaveValue('all');
    await expect(barberFilter.locator('option').first()).toHaveText('All barbers');

    // The live queue may contain tickets or show its empty state; either means the page rendered.
    await expect(page.getByRole('heading', { name: 'Next up' })).toBeVisible();
    await expect(page.getByRole('heading', { name: /^Waiting/ })).toBeVisible();
});

test('manage queue can call and call again without changing ticket status', async ({ page }) => {
    const calls = await mockQueue(page, [ticket(11, 1, 'Waiting Customer')]);
    await openManageQueue(page);

    const card = ticketCard(page, 'Waiting Customer');
    await card.getByRole('button', { name: 'Call to chair' }).click();
    await expect(card.getByText('On the calling board now')).toBeVisible();
    await expect(card.getByRole('button', { name: 'Finish service' })).toHaveCount(0);
    await card.getByRole('button', { name: 'Call again' }).click();
    await expect.poll(() => calls.filter((call) => call.action === 'call').length).toBe(2);
});

test('manage queue starts service and completes the ticket', async ({ page }) => {
    const calls = await mockQueue(page, [ticket(12, 2, 'Service Customer')]);
    await openManageQueue(page);

    const card = ticketCard(page, 'Service Customer');
    await card.getByRole('button', { name: 'Start service' }).click();
    await expect(card.getByText('in chair', { exact: false })).toBeVisible();
    await expect(card.getByRole('button', { name: 'Finish service' })).toBeVisible();

    await card.getByRole('button', { name: 'Finish service' }).click();
    await expect(card).toHaveCount(0);
    await expect(confirmDialog(page)).toBeHidden();
    expect(calls.filter((call) => call.action === 'status').map((call) => call.body.status)).toEqual(['serving', 'completed']);
});

for (const status of ['in_queue', 'serving']) {
    test(`manage queue can cancel a ${status === 'serving' ? 'currently serving' : 'waiting'} ticket after confirmation`, async ({ page }) => {
        const calls = await mockQueue(page, [ticket(20, 3, 'Cancel Candidate', status)]);
        await openManageQueue(page);

        const card = ticketCard(page, 'Cancel Candidate');
        await clickCancel(card);
        const dialog = confirmDialog(page);
        await expect(dialog).toBeVisible();
        await expect(dialog).toContainText('Cancel #003?');
        await expect(dialog).toContainText('Cancel Candidate will be removed from the queue.');

        await dialog.getByRole('button', { name: 'Keep in queue' }).click();
        await expect(dialog).toBeHidden();
        await expect(card).toBeVisible();
        expect(calls.filter((call) => call.action === 'status')).toHaveLength(0);

        await clickCancel(card);
        await dialog.getByRole('button', { name: 'Cancel ticket' }).click();
        await expect(card).toHaveCount(0);
        await expect(page.getByRole('status').getByText('#003 was cancelled.')).toBeVisible();
        expect(calls.filter((call) => call.action === 'status').map((call) => call.body.status)).toEqual(['canceled']);
    });
}

test('manage queue can dismiss cancellation with Escape', async ({ page }) => {
    const calls = await mockQueue(page, [ticket(21, 4, 'Escape Candidate', 'serving')]);
    await openManageQueue(page);
    const card = ticketCard(page, 'Escape Candidate');

    await clickCancel(card);
    await expect(confirmDialog(page)).toBeVisible();
    await page.keyboard.press('Escape');
    await expect(confirmDialog(page)).toBeHidden();
    await expect(card).toBeVisible();
    expect(calls.filter((call) => call.action === 'status')).toHaveLength(0);
});

test('manage queue keeps the ticket and shows an error when a status update fails', async ({ page }) => {
    const calls = await mockQueue(page, [ticket(22, 5, 'Retry Candidate')], { failStatusUpdate: 409 });
    await openManageQueue(page);
    const card = ticketCard(page, 'Retry Candidate');

    await card.getByRole('button', { name: 'Start service' }).click();
    await expect(page.getByRole('status').getByText('Could not update status. Please try again.')).toBeVisible();
    await expect(card).toBeVisible();
    await expect(card.getByRole('button', { name: 'Start service' })).toBeVisible();
    expect(calls.filter((call) => call.action === 'status')).toHaveLength(1);
});

test('manage queue handles successful and failed manual SMS actions', async ({ page }) => {
    const calls = await mockQueue(page, [ticket(23, 6, 'SMS Customer')], { failAction: 'sms' });
    await openManageQueue(page);
    const card = ticketCard(page, 'SMS Customer');

    await card.getByRole('button', { name: 'Send SMS update' }).click();
    await expect.poll(() => calls.filter((call) => call.action === 'sms').length).toBe(1);
    await expect(page.getByRole('status').getByText('Mock request failed.')).toBeVisible();
    await expect(card).toBeVisible();
});

test('manage queue calls SMS endpoint successfully without removing the ticket', async ({ page }) => {
    const calls = await mockQueue(page, [ticket(26, 9, 'SMS Success Customer')]);
    await openManageQueue(page);
    const card = ticketCard(page, 'SMS Success Customer');

    await card.getByRole('button', { name: 'Send SMS update' }).click();
    await expect.poll(() => calls.filter((call) => call.action === 'sms').length).toBe(1);
    await expect(page.getByRole('status').getByText('SMS sent to SMS Success Customer.')).toBeVisible();
    await expect(card).toBeVisible();
});

test('manage queue keeps a waiting ticket and resets the call button when calling fails', async ({ page }) => {
    const calls = await mockQueue(page, [ticket(27, 10, 'Call Failure Customer')], { failAction: 'call', failStatus: 503 });
    await openManageQueue(page);
    const card = ticketCard(page, 'Call Failure Customer');

    await card.getByRole('button', { name: 'Call to chair' }).click();
    await expect.poll(() => calls.filter((call) => call.action === 'call').length).toBe(1);
    await expect(card).toBeVisible();
    await expect(card.getByRole('button', { name: 'Call to chair' })).toBeVisible();
    await expect(card.getByText('On the calling board now')).toBeHidden();
});

test('manage queue filters tickets by barber and toggles join alerts', async ({ page }) => {
    await mockQueue(page, [ticket(24, 7, 'Alex Customer', 'in_queue', 1), ticket(25, 8, 'Sam Customer', 'in_queue', 2)]);
    await openManageQueue(page);

    await expect(page.getByText('Sam Customer')).toBeVisible();
    const barberFilter = page.getByLabel('Show customers for');
    await barberFilter.selectOption('1');
    await expect(ticketCard(page, 'Alex Customer')).toBeVisible();
    await expect(page.getByText('Sam Customer')).toHaveCount(0);
    await expect(barberFilter).toHaveValue('1');

    await page.getByRole('button', { name: 'Alerts off' }).click();
    await expect(page.getByRole('button', { name: 'Alerts on' })).toBeVisible();
    await expect(page.getByRole('status').getByText('You will be notified when a customer joins.')).toBeVisible();
});
