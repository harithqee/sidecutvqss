// In-memory stand-in for the Sidecut JSON API, so browser tests never touch the
// real database or send real SMS through TextBee. Every /api/* request is
// answered here; `api.calls` records what the page sent, and `api.fail` lets a
// test force an endpoint to error.

const minutesAgo = (minutes) => new Date(Date.now() - minutes * 60000).toISOString();
const today = () => new Date().toISOString().slice(0, 10);

export function barber(id, name, { role = 'Barber', active = true, image = null } = {}) {
    return { id, name, role, image, is_active: active };
}

export function ticket(id, queueNumber, customerName, { status = 'in_queue', barberId = null, joined = 5, served = null, calling = false } = {}) {
    return {
        id,
        queue_number: queueNumber,
        customer_name: customerName,
        customer_phone: '01' + String(20000000 + id),
        status,
        barber_id: barberId,
        joined_at: minutesAgo(joined),
        served_at: served === null ? (status === 'serving' ? minutesAgo(1) : null) : minutesAgo(served),
        finished_at: null,
        is_calling: calling,
        called_at: calling ? minutesAgo(0) : null,
        call_version: calling ? 1 : 0,
    };
}

export function historyEntry(id, queueNumber, customerName, { status = 'completed', barberName = 'Alex', wait = 12 } = {}) {
    return {
        id,
        queue_number: queueNumber,
        customer_name: customerName,
        customer_phone: '01' + String(30000000 + id),
        status,
        barber: barberName ? { name: barberName } : null,
        joined_at: minutesAgo(90),
        served_at: status === 'completed' ? minutesAgo(90 - wait) : null,
        finished_at: minutesAgo(40),
        waiting_time: status === 'completed' ? wait + ' min' : null,
        session: { session_date: today() },
    };
}

export function template(id, name, trigger, message, active = true) {
    return { id, name, trigger_event: trigger, message_body: message, is_active: active, updated_at: minutesAgo(60) };
}

export function smsLog(id, status, { name = 'Daniel', templateName = 'Customer Joins Queue', message = 'Hi Daniel, you are #1.' } = {}) {
    return {
        id,
        phone: '0123456' + String(id).padStart(3, '0'),
        status,
        ticket: name ? { customer_name: name } : null,
        template: templateName ? { name: templateName } : null,
        message_body: message,
        sent_at: status === 'sent' ? minutesAgo(id) : null,
        created_at: minutesAgo(id),
    };
}

export function defaultState() {
    return {
        barbers: [barber(1, 'Alex'), barber(2, 'Sam'), barber(3, 'Marzuki', { role: 'Intern', active: false })],
        tickets: [],
        history: [],
        templates: [
            template(1, 'Customer Joins Queue', 'Customer joins the queue', 'Hi {{customerName}}, you are #{{queueNumber}}. Wait: {{waitTime}} min.'),
            template(2, 'Turn Approaching', 'Staff taps Send SMS', 'Hi {{customerName}}, you are number {{position}} in line.'),
            template(3, 'Service Complete', 'Service is finished', 'Thanks {{customerName}}, see you again!', false),
        ],
        smsLogs: [],
        summary: {
            customers_today: 12, avg_wait_minutes: 14, avg_service_minutes: 22, completion_rate: 90,
            utilization_pct: 40, servers_active: 2, predicted_wait_minutes: 9, avg_in_queue: 1.2, avg_in_system: 2.1, queue_model_stable: true,
        },
        hourly: {
            categories: ['11AM', '12PM', '1PM', '2PM'],
            overview: [3, 5, 2, 4], peakHours: [3, 5, 2, 4], waitTimes: [10, 18, 6, 12],
        },
        monthly: [{ month: 9, total: 120 }, { month: 10, total: 30 }],
        performance: [{ name: 'Alex', completed: 6 }, { name: 'Sam', completed: 2 }, { name: 'Marzuki', completed: 0 }],
        callingBoard: null, // null = derive from tickets
        joinResponse: { queueNumber: 21, wait: 15 },
        perPage: 10,
    };
}

const json = (route, body, status = 200) => route.fulfill({ status, contentType: 'application/json', body: JSON.stringify(body) });

function paginate(items, page, perPage) {
    const total = items.length;
    const lastPage = Math.max(1, Math.ceil(total / perPage));
    const start = (page - 1) * perPage;
    const data = items.slice(start, start + perPage);
    return {
        data, total, per_page: perPage, current_page: page, last_page: lastPage,
        from: total ? start + 1 : null, to: total ? start + data.length : null,
    };
}

export async function mockApi(page, overrides = {}) {
    const state = { ...defaultState(), ...overrides };
    const api = { state, calls: [], fail: {} };
    const barberById = (id) => state.barbers.find((b) => b.id === id) || null;
    const withBarber = (t) => ({ ...t, barber: t.barber_id ? barberById(t.barber_id) : null });
    const anyActive = () => state.barbers.some((b) => b.is_active);

    await page.route('**/api/**', async (route) => {
        const request = route.request();
        const url = new URL(request.url());
        const path = url.pathname.replace(/^\/api/, '');
        const method = request.method();
        let body = null;
        try { body = request.postDataJSON(); } catch { body = null; }
        api.calls.push({ method, path, query: Object.fromEntries(url.searchParams), body });

        const failKey = Object.keys(api.fail).find((key) => {
            const [m, p] = key.split(' ');
            return m === method && new RegExp('^' + p + '$').test(path);
        });
        if (failKey) {
            const failure = api.fail[failKey];
            return json(route, { message: failure.message ?? 'Mock request failed.' }, failure.status ?? 500);
        }

        let m;
        // ---- Barbers ----
        if (path === '/barbers' && method === 'GET') return json(route, state.barbers);
        if (path === '/barbers' && method === 'POST') {
            const created = barber(Math.max(0, ...state.barbers.map((b) => b.id)) + 1, body.name, { role: body.role });
            state.barbers.push(created);
            return json(route, created, 201);
        }
        if ((m = path.match(/^\/barbers\/(\d+)\/toggle-active$/)) && method === 'PATCH') {
            const b = barberById(Number(m[1]));
            b.is_active = !b.is_active;
            return json(route, b);
        }
        if ((m = path.match(/^\/barbers\/(\d+)$/))) {
            const b = barberById(Number(m[1]));
            if (method === 'PUT') { Object.assign(b, body); return json(route, b); }
            if (method === 'DELETE') {
                state.barbers = state.barbers.filter((x) => x.id !== b.id);
                return route.fulfill({ status: 204, body: '' });
            }
        }

        // ---- Queue ----
        if (path === '/queue' && method === 'GET') {
            if (!anyActive()) return json(route, []);
            return json(route, state.tickets.filter((t) => ['in_queue', 'serving'].includes(t.status)).map(withBarber));
        }
        if (path === '/queue' && method === 'POST') {
            if (!anyActive()) return json(route, { message: 'The queue is closed because no barbers are active. Please try again later.' }, 409);
            const created = ticket(Math.max(100, ...state.tickets.map((t) => t.id)) + 1, state.joinResponse.queueNumber, body.customer_name, { barberId: body.barber_id, joined: 0 });
            state.tickets.push(created);
            return json(route, { ticket: withBarber(created), estimated_wait_minutes: state.joinResponse.wait }, 201);
        }
        if (path === '/queue/lookup' && method === 'GET') {
            const found = state.tickets.find((t) => String(t.queue_number) === url.searchParams.get('queue_number'));
            if (!found) return json(route, { message: 'No ticket found with that queue number today.' }, 404);
            const waiting = state.tickets.filter((t) => t.status === 'in_queue' && t.queue_number <= found.queue_number);
            const position = found.status === 'in_queue' ? waiting.length : null;
            return json(route, { ticket: withBarber(found), position, ahead: position ? position - 1 : null, estimated_wait_minutes: position ? position * 10 : null });
        }
        if (path === '/queue/history' && method === 'GET') {
            return json(route, paginate(state.history, Number(url.searchParams.get('page') || 1), state.perPage));
        }
        if (path === '/queue/calling-board' && method === 'GET') {
            if (state.callingBoard) return json(route, state.callingBoard);
            if (!anyActive()) return json(route, { queue_active: false, current_call: null, recent_calls: [], upcoming: [], waiting_count: 0, updated_at: new Date().toISOString() });
            const map = (t) => ({ id: t.id, queue_number: t.queue_number, status: t.status, barber: barberById(t.barber_id)?.name ?? null, call_version: t.call_version });
            const active = state.tickets.filter((t) => ['in_queue', 'serving'].includes(t.status)).sort((a, b) => a.queue_number - b.queue_number);
            const current = active.find((t) => t.is_calling);
            return json(route, {
                queue_active: true,
                current_call: current ? map(current) : null,
                recent_calls: [],
                upcoming: active.filter((t) => t.status === 'in_queue').slice(0, 8).map(map),
                waiting_count: active.filter((t) => t.status === 'in_queue').length,
                updated_at: new Date().toISOString(),
            });
        }
        if ((m = path.match(/^\/queue\/(\d+)\/(call|status|sms)$/))) {
            const t = state.tickets.find((x) => x.id === Number(m[1]));
            if (!t) return json(route, { message: 'Ticket not found.' }, 404);
            if (m[2] === 'call') {
                state.tickets.forEach((x) => { x.is_calling = false; });
                t.is_calling = true;
                t.call_version += 1;
                t.called_at = new Date().toISOString();
                return json(route, withBarber(t));
            }
            if (m[2] === 'sms') return json(route, { message: 'sent', text: 'Mock SMS' }, 201);
            t.status = body.status;
            t.is_calling = false;
            if (body.status === 'serving') t.served_at ??= new Date().toISOString();
            const response = { ...withBarber(t) };
            if (state.smsStatus) response.sms_status = state.smsStatus;
            return json(route, response);
        }

        // ---- Message templates ----
        if (path === '/message-templates' && method === 'GET') return json(route, state.templates);
        if (path === '/message-templates' && method === 'POST') {
            const created = template(Math.max(0, ...state.templates.map((t) => t.id)) + 1, body.name, body.trigger_event, body.message_body, true);
            state.templates.push(created);
            return json(route, created, 201);
        }
        if ((m = path.match(/^\/message-templates\/(\d+)\/toggle-active$/)) && method === 'PATCH') {
            const t = state.templates.find((x) => x.id === Number(m[1]));
            t.is_active = !t.is_active;
            return json(route, t);
        }
        if ((m = path.match(/^\/message-templates\/(\d+)$/))) {
            const t = state.templates.find((x) => x.id === Number(m[1]));
            if (method === 'PUT') { Object.assign(t, body, { updated_at: new Date().toISOString() }); return json(route, t); }
            if (method === 'DELETE') {
                state.templates = state.templates.filter((x) => x.id !== t.id);
                return route.fulfill({ status: 204, body: '' });
            }
        }

        // ---- SMS logs ----
        if (path === '/sms-logs' && method === 'GET') {
            const status = url.searchParams.get('status');
            const logs = status ? state.smsLogs.filter((l) => l.status === status) : state.smsLogs;
            return json(route, paginate(logs, Number(url.searchParams.get('page') || 1), state.perPage));
        }

        // ---- Statistics ----
        if (path === '/stats/summary') return json(route, state.summary);
        if (path === '/stats/hourly') return json(route, state.hourly);
        if (path === '/stats/monthly-report') return json(route, state.monthly);
        if (path === '/stats/barber-performance') return json(route, state.performance);

        return json(route, { message: `No mock for ${method} ${path}` }, 501);
    });

    api.callsTo = (method, pattern) => api.calls.filter((c) => c.method === method && (pattern instanceof RegExp ? pattern.test(c.path) : c.path === pattern));
    return api;
}

// Toasts render inside the page's single role="status" region.
export function toast(page, text) {
    return page.getByRole('status').getByText(text);
}
