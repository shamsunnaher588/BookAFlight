<?php
require_once __DIR__ . '/db.php';
require_login();

$check = $pdo->prepare(
    'SELECT role FROM users WHERE id = ?'
);

$check->execute([
    (int)$_SESSION['user_id']
]);

if ($check->fetchColumn() !== 'admin') {
    http_response_code(403);
    exit('Admin access required.');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport"
      content="width=device-width, initial-scale=1">

<title>Support Inbox | BookAFlight</title>

<style>
* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #f3f7fc;
    color: #172b4d;
}

header {
    background: #082858;
    color: white;
    padding: 17px 5%;
    display: flex;
    justify-content: space-between;
    flex-wrap: wrap;
}

header a {
    color: white;
}

main {
    max-width: 1250px;
    margin: 25px auto;
    padding: 0 12px;
}

.layout {
    display: grid;
    grid-template-columns: 310px 1fr;
    gap: 15px;
}

.card {
    background: white;
    border-radius: 12px;
    padding: 15px;
    box-shadow: 0 4px 17px #0001;
    margin: 10px 0;
}

.thread {
    display: block;
    border: 0;
    border-radius: 8px;
    background: #edf4fe;
    padding: 10px;
    width: 100%;
    text-align: left;
    margin: 7px 0;
    cursor: pointer;
}

.thread.active {
    background: #c3e0ff;
}

.chat {
    height: 420px;
    overflow-y: auto;
    background: #fafcff;
    padding: 12px;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.msg {
    border-radius: 9px;
    background: #e7effb;
    padding: 10px;
    max-width: 85%;
    white-space: pre-wrap;
    overflow-wrap: anywhere;
}

.msg.admin {
    background: #d7ecff;
    align-self: flex-end;
}

.msg small {
    display: block;
    color: #617187;
}

button {
    cursor: pointer;
}

input,
textarea,
select {
    font: inherit;
    padding: 9px;
    border: 1px solid #c9d6e6;
    border-radius: 7px;
    max-width: 100%;
}

input[type=text],
textarea {
    width: 100%;
}

.btn {
    background: #ffbc43;
    border: 0;
    border-radius: 8px;
    padding: 10px 14px;
    font-weight: bold;
}

form {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    align-items: center;
}

.grow {
    flex: 1;
}

.filters {
    display: grid;
    gap: 8px;
}

.status {
    font-size: 13px;
    color: #617187;
}

.faq {
    border-bottom: 1px solid #eee;
    padding: 10px 0;
}

.faq button {
    margin-right: 6px;
}

.row {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    align-items: center;
}

h1,
h2,
h3 {
    margin-top: 10px;
}

a {
    color: #0674bb;
}

@media(max-width:760px) {
    .layout {
        grid-template-columns: 1fr;
    }

    .chat {
        height: 330px;
    }
}
</style>
</head>

<body>

<header>
    <strong>✈ BookAFlight — Support Admin</strong>
    <a href="index.php">← Back to Website</a>
</header>

<main>

<div class="row">

    <h1>Customer Support Inbox</h1>

    <label>
        Available
        <input type="checkbox" id="availability">
    </label>

    <span id="online" class="status"></span>

</div>

<p class="status">
    Your availability appears online while this page
    is open and heartbeat messages are received.
    Chat messages refresh every 3 seconds.
</p>

<div class="layout">

    <aside class="card">

        <h2>Conversations</h2>

        <div class="filters">

            <input id="find"
                   type="text"
                   placeholder="Search name, subject, message">

            <select id="filter">
                <option value="">All statuses</option>
                <option value="pending">Pending</option>
                <option value="active">Active</option>
                <option value="closed">Closed</option>
            </select>

            <input type="date" id="date">

        </div>

        <div id="threads">Loading...</div>

    </aside>

    <section class="card">

        <h2 id="title">Select a conversation</h2>

        <div class="row">

            <button class="btn" type="button"
                    id="close" disabled>
                End Chat
            </button>

            <button class="btn" type="button"
                    id="download" disabled>
                Download Transcript
            </button>

            <span id="rating" class="status"></span>

        </div>

        <div id="chat" class="chat">
            Choose a customer to view messages.
        </div>

        <form id="send-form">

            <input id="message"
                   type="text"
                   class="grow"
                   maxlength="2000"
                   placeholder="Reply to customer">

            <input id="file"
                   type="file"
                   accept=".png,.jpg,.jpeg,.webp,.pdf"
                   title="2 MB maximum">

            <button class="btn" type="submit">
                Send Reply
            </button>

        </form>

        <p id="status" class="status">
            Do not request passwords, OTPs or full card details.
        </p>

    </section>

</div>

<section class="card">

    <h2>🤖 Chatbot FAQ Manager</h2>

    <p class="status">
        Separate keywords with commas.
        Higher priority FAQs are checked first.
    </p>

    <form id="faq-form">

        <input type="hidden" id="faq-id">

        <input id="faq-category"
               type="text"
               placeholder="Category"
               required>

        <input id="faq-keywords"
               type="text"
               class="grow"
               placeholder="booking,flight,ফ্লাইট"
               required>

        <input id="faq-priority"
               type="number"
               value="10"
               min="0"
               max="999"
               style="width:75px">

        <textarea id="faq-answer"
                  rows="2"
                  class="grow"
                  placeholder="Automatic reply"
                  required></textarea>

        <button class="btn" type="submit">
            Save FAQ
        </button>

        <button type="button" id="faq-reset">
            Clear
        </button>

    </form>

    <div id="faqs"></div>

</section>

</main>

<script>
(() => {
    'use strict';

    const $ = id => document.getElementById(id);

    let csrf = '';
    let active = 0;
    let faqs = [];
    let last = '';
    let online = false;

    async function get(action, params = {}) {

        const query = new URLSearchParams({
            action,
            ...params
        });

        const response = await fetch(
            'support-api.php?' + query,
            { credentials: 'same-origin' }
        );

        const data = await response.json();

        if (!response.ok) {
            throw Error(data.error || 'Request failed');
        }

        return data;
    }

    async function post(action, data = {}) {

        const body = data instanceof FormData
            ? data
            : new FormData();

        if (!(data instanceof FormData)) {
            Object.entries(data).forEach(([key, value]) => {
                body.append(key, value);
            });
        }

        body.set('action', action);
        body.set('csrf_token', csrf);

        const response = await fetch(
            'support-api.php',
            {
                method: 'POST',
                credentials: 'same-origin',
                body
            }
        );

        const result = await response.json();

        if (!response.ok) {
            throw Error(result.error || 'Request failed');
        }

        return result;
    }

    function notify(text) {
        $('status').textContent = text;
    }

    async function boot() {

        try {
            const data = await get('bootstrap');

            csrf = data.csrf;
            online = !!data.agent_online;

            $('availability').checked = online;

            $('online').textContent =
                'Agents Online: ' + data.agents_online;

            await Promise.all([
                loadThreads(),
                loadFaqs()
            ]);

        } catch (error) {
            notify(error.message);
        }
    }

    async function loadThreads() {

        try {
            const data = await get('threads', {
                q: $('find').value,
                status: $('filter').value,
                date: $('date').value
            });

            const container = $('threads');
            container.replaceChildren();

            $('online').textContent =
                'Agents Online: ' + data.agents_online;

            if (!data.threads.length) {
                container.textContent =
                    'No conversations found.';
            }

            data.threads.forEach(thread => {

                const button = document.createElement('button');

                button.type = 'button';

                button.className = 'thread' +
                    (
                        Number(thread.conv_id) === active
                            ? ' active'
                            : ''
                    );

                button.textContent =
                    '#' + thread.conv_id + ' · ' +
                    thread.full_name + ' (' +
                    thread.status + ')' +
                    (
                        Number(thread.unread)
                            ? ' · ' + thread.unread + ' new'
                            : ''
                    );

                button.onclick = () => {

                    active = Number(thread.conv_id);
                    last = '';

                    $('title').textContent =
                        'Conversation #' +
                        thread.conv_id +
                        ' — ' +
                        thread.full_name;

                    $('close').disabled =
                        thread.status === 'closed';

                    $('download').disabled = false;

                    $('rating').textContent = thread.rating
                        ? 'Rating: ' + thread.rating + '/5'
                        : '';

                    loadThreads();
                    loadMessages();
                };

                container.appendChild(button);
            });

        } catch (error) {
            notify(error.message);
        }
    }

    async function loadMessages() {

        if (!active) return;

        try {
            const data = await get('messages', {
                conv_id: active
            });

            const signature = data.messages.map(
                m => m.msg_id
            ).join(',');

            if (signature !== last) {

                const container = $('chat');
                container.replaceChildren();

                data.messages.forEach(message => {

                    const div = document.createElement('div');
                    div.className =
                        'msg ' + message.sender_type;

                    const label = document.createElement('small');

                    label.textContent =
                        message.sent_at + ' · ' +
                        message.sender_type;

                    const text = document.createElement('span');
                    text.textContent = message.message_text;

                    div.append(label, text);

                    if (message.attachment_name) {

                        const link = document.createElement('a');

                        link.href =
                            'support-api.php?action=file&id=' +
                            encodeURIComponent(message.msg_id);

                        link.textContent =
                            '📎 ' + message.attachment_name;

                        div.appendChild(link);
                    }

                    container.appendChild(div);
                });

                container.scrollTop = container.scrollHeight;

                last = signature;
            }

            $('close').disabled =
                data.conversation.status === 'closed';

        } catch (error) {
            notify(error.message);
        }
    }

    $('send-form').onsubmit = async event => {

        event.preventDefault();

        if (!active) {
            notify('Select a conversation first.');
            return;
        }

        const body = new FormData();

        body.append('conv_id', active);
        body.append('message', $('message').value.trim());

        if ($('file').files[0]) {
            body.append('attachment', $('file').files[0]);
        }

        try {
            await post('send', body);

            $('message').value = '';
            $('file').value = '';
            last = '';

            await loadMessages();
            await loadThreads();

            notify('Reply sent.');

        } catch (error) {
            notify(error.message);
        }
    };

    $('close').onclick = async () => {

        if (!active || !confirm('Close conversation?')) {
            return;
        }

        try {
            await post('end', {
                conv_id: active
            });

            await loadThreads();
            await loadMessages();

            notify('Conversation closed.');

        } catch (error) {
            notify(error.message);
        }
    };

    $('download').onclick = () => {

        if (active) {
            location.href =
                'support-api.php?action=transcript&conv_id=' +
                active;
        }
    };

    $('availability').onchange = async () => {

        try {
            const data = await post('presence', {
                mode: $('availability').checked
                    ? 'on'
                    : 'off'
            });

            online = $('availability').checked;

            $('online').textContent =
                'Agents Online: ' + data.agents_online;

        } catch (error) {
            notify(error.message);
        }
    };

    for (const id of ['find', 'filter', 'date']) {
        $(id).addEventListener('change', loadThreads);
    }

    let searching;

    $('find').oninput = () => {
        clearTimeout(searching);

        searching = setTimeout(loadThreads, 350);
    };

    async function loadFaqs() {

        try {
            const data = await get('faqs');

            faqs = data.faqs;

            const container = $('faqs');
            container.replaceChildren();

            faqs.forEach(faq => {

                const div = document.createElement('div');
                div.className = 'faq';

                const strong = document.createElement('strong');

                strong.textContent =
                    faq.category +
                    ' · Priority ' +
                    faq.priority;

                const description = document.createElement('p');

                description.textContent =
                    'Keywords: ' + faq.question_keywords +
                    ' | Reply: ' + faq.answer_text;

                const edit = document.createElement('button');
                edit.textContent = 'Edit';

                edit.onclick = () => {

                    $('faq-id').value = faq.faq_id;
                    $('faq-category').value = faq.category;
                    $('faq-keywords').value = faq.question_keywords;
                    $('faq-priority').value = faq.priority;
                    $('faq-answer').value = faq.answer_text;

                    $('faq-form').scrollIntoView({
                        behavior: 'smooth'
                    });
                };

                const toggle = document.createElement('button');

                toggle.textContent =
                    Number(faq.is_active)
                        ? 'Disable'
                        : 'Enable';

                toggle.onclick = async () => {

                    try {
                        await post('faq_toggle', {
                            faq_id: faq.faq_id
                        });

                        loadFaqs();

                    } catch (error) {
                        notify(error.message);
                    }
                };

                const del = document.createElement('button');
                del.textContent = 'Delete';

                del.onclick = async () => {

                    if (!confirm('Delete this FAQ?')) return;

                    try {
                        await post('faq_delete', {
                            faq_id: faq.faq_id
                        });

                        loadFaqs();

                    } catch (error) {
                        notify(error.message);
                    }
                };

                div.append(
                    strong,
                    description,
                    edit,
                    toggle,
                    del
                );

                container.appendChild(div);
            });

        } catch (error) {
            notify(error.message);
        }
    }

    function resetFaq() {
        $('faq-form').reset();
        $('faq-id').value = '';
    }

    $('faq-reset').onclick = resetFaq;

    $('faq-form').onsubmit = async event => {

        event.preventDefault();

        try {
            await post('faq_save', {
                faq_id: $('faq-id').value,
                category: $('faq-category').value,
                question_keywords: $('faq-keywords').value,
                priority: $('faq-priority').value,
                answer_text: $('faq-answer').value
            });

            resetFaq();
            loadFaqs();

            notify('FAQ saved.');

        } catch (error) {
            notify(error.message);
        }
    };

    boot();

    setInterval(() => {

        if (document.hidden) return;

        loadThreads();
        loadMessages();

        if (online) {
            post('presence', {
                mode: 'heartbeat'
            }).catch(error => notify(error.message));
        }

    }, 3000);

})();
</script>
<?php include_once __DIR__ . '/theme-toggle.php'; ?>
<?php include_once __DIR__ . '/responsive-layout.php'; ?>
</body>
</html>