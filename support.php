<?php
// BookAFlight - Complete Support Interface
// Requires db.php, support-api.php and chat tables.

require_once __DIR__ . '/db.php';

$bafStandalone = basename(
    $_SERVER['SCRIPT_NAME'] ?? ''
) === 'support.php';
?>

<?php if ($bafStandalone): ?>

<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1">

<title>Customer Support | BookAFlight</title>

<style>
body {
    margin: 0;
    font-family: Arial, sans-serif;
    color: #172b4d;
    background: #f3f7fc;
}

.baf-page-header {
    background: #082858;
    color: white;
    padding: 20px 7%;
}

.baf-page-header a {
    color: white;
    text-decoration: none;
}

.baf-page-main {
    max-width: 850px;
    width: 90%;
    margin: 40px auto;
    padding: 25px;
    background: white;
    border-radius: 14px;
    line-height: 1.7;
    box-sizing: border-box;
}
</style>

</head>

<body>

<header class="baf-page-header">
    <a href="index.php">
        ✈ BookAFlight · Back to Home
    </a>
</header>

<main class="baf-page-main">

    <h1>💬 Customer Support</h1>

    <p>
        Need help with your flight plan?
        Click the Need Help button in the
        bottom-right corner.
    </p>

    <p>
        Our FAQ chatbot is available while
        the website is running.
        Human agents reply when available.
    </p>

    <p>
        Never share passwords, OTPs,
        PINs or full payment card details.
    </p>

</main>

<?php endif; ?>

<style>
#baf-support,
#baf-support * {
    box-sizing: border-box;
}

#baf-support {
    position: fixed;
    right: 18px;
    bottom: 18px;
    z-index: 99999;
    font: 14px Arial, sans-serif;
    color: #172b4d;
}

#baf-support button,
#baf-support input {
    font: inherit;
}

#baf-support button {
    cursor: pointer;
}

#baf-support .baf-hidden {
    display: none !important;
}

#baf-support-toggle {
    border: 0;
    background: #087cc6;
    color: white;
    font-weight: bold;
    padding: 15px 19px;
    border-radius: 32px;
    box-shadow: 0 6px 22px #0003;
}

#baf-support-dot {
    display: inline-block;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: #d84b48;
    margin-right: 7px;
}

#baf-support-dot.baf-online {
    background: #25c36b;
}

#baf-support-panel {
    display: flex;
    flex-direction: column;
    width: min(385px, calc(100vw - 20px));
    height: min(600px, calc(100dvh - 98px));
    margin-bottom: 12px;
    overflow: hidden;
    background: white;
    border: 1px solid #d6e4f1;
    border-radius: 16px;
    box-shadow: 0 8px 40px #0003;
}

#baf-support-header {
    background: #082858;
    color: white;
    padding: 13px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
}

#baf-support-header button {
    color: white;
    background: transparent;
    border: 0;
    font-size: 22px;
}

#baf-support-presence {
    background: #f3f7fc;
    padding: 10px 12px;
    font-size: 12px;
}

#baf-support-messages {
    display: flex;
    flex: 1;
    flex-direction: column;
    overflow-y: auto;
    gap: 10px;
    padding: 13px;
    background: #f9fbff;
}

#baf-support .baf-msg {
    max-width: 89%;
    border-radius: 12px;
    padding: 11px;
    background: #e6effb;
    white-space: pre-wrap;
    overflow-wrap: anywhere;
    line-height: 1.45;
}

#baf-support .baf-msg.baf-user {
    align-self: flex-end;
    color: white;
    background: #087cc6;
}

#baf-support .baf-msg small {
    display: block;
    margin-bottom: 5px;
    opacity: .75;
    font-size: 11px;
}

#baf-support .baf-msg a {
    color: inherit;
    text-decoration: underline;
    display: block;
    margin-top: 7px;
}

#baf-support-quick,
#baf-support-actions {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
    padding: 8px;
}

#baf-support-quick {
    max-height: 90px;
    overflow: auto;
}

#baf-support-quick button,
#baf-support-actions button {
    border: 1px solid #c9dbf0;
    border-radius: 16px;
    background: white;
    padding: 7px 9px;
    color: #075c9c;
    font-size: 12px;
}

#baf-support-form {
    display: flex;
    gap: 7px;
    align-items: center;
    border-top: 1px solid #e2eaf4;
    padding: 9px;
}

#baf-support-text {
    flex: 1;
    min-width: 0;
    border: 1px solid #cbd7e6;
    border-radius: 8px;
    padding: 10px;
}

#baf-support-file {
    width: 62px;
    font-size: 11px;
}

#baf-support-send {
    background: #ffbc43;
    border: 0;
    border-radius: 8px;
    padding: 10px 12px;
}

#baf-support-status {
    min-height: 28px;
    padding: 5px 10px;
    color: #66788c;
    font-size: 12px;
    line-height: 1.4;
}

#baf-support-rating {
    padding: 9px;
    background: #fff6d8;
    text-align: center;
}

#baf-support-rating button {
    border: 0;
    background: transparent;
    font-size: 25px;
    color: #c78b04;
}

@media(max-width:500px) {
    #baf-support {
        right: 8px;
        bottom: 8px;
    }

    #baf-support-panel {
        height: min(
            560px,
            calc(100dvh - 80px)
        );
    }
}
</style>

<div id="baf-support">

    <section
        id="baf-support-panel"
        class="baf-hidden"
        aria-label="Customer support chat"
    >

        <div id="baf-support-header">

            <strong>
                💬 BookAFlight Support
            </strong>

            <div>
                <button
                    type="button"
                    id="baf-support-min"
                    aria-label="Minimize chat"
                >−</button>

                <button
                    type="button"
                    id="baf-support-close"
                    aria-label="Close chat"
                >×</button>
            </div>

        </div>

        <div id="baf-support-presence">

            🤖 FAQ Bot ·

            <span id="baf-support-agents">
                Checking agent status...
            </span>

        </div>

        <div
            id="baf-support-messages"
            role="log"
            aria-live="polite"
        ></div>

        <div
            id="baf-support-quick"
            aria-label="Quick questions"
        ></div>

        <div id="baf-support-actions">

            <button
                type="button"
                id="baf-support-connect"
            >
                👤 Speak to Agent
            </button>

            <button
                type="button"
                id="baf-support-end"
                class="baf-hidden"
            >
                End Chat
            </button>

            <button
                type="button"
                id="baf-support-download"
                class="baf-hidden"
            >
                Download Transcript
            </button>

            <button
                type="button"
                id="baf-support-email"
                class="baf-hidden"
            >
                Email Transcript
            </button>

        </div>

        <div
            id="baf-support-rating"
            class="baf-hidden"
        >

            Rate your chat:

            <span id="baf-support-stars"></span>

        </div>

        <form
            id="baf-support-form"
            enctype="multipart/form-data"
        >

            <input
                id="baf-support-text"
                type="text"
                maxlength="2000"
                placeholder="Type your message..."
                aria-label="Message"
            >

            <input
                id="baf-support-file"
                type="file"
                accept=".png,.jpg,.jpeg,.webp,.pdf"
                title="Maximum 2 MB"
                aria-label="Attachment"
            >

            <button
                type="submit"
                id="baf-support-send"
                aria-label="Send"
            >
                ➤
            </button>

        </form>

        <div id="baf-support-status">
            Do not send passwords, OTPs,
            PINs or payment card numbers.
        </div>

    </section>

    <button
        id="baf-support-toggle"
        type="button"
        aria-expanded="false"
    >

        <span id="baf-support-dot"></span>

        💬 Need Help?

    </button>

</div>

<script>
(() => {
    'use strict';

    if (window.__bafUnifiedChatStarted) {
        return;
    }

    window.__bafUnifiedChatStarted = true;

    const el = name =>
        document.getElementById(
            'baf-support-' + name
        );

    const api = 'support-api.php';

    let state = {
        logged_in: false,
        is_admin: false,
        csrf: '',
        agents_online: 0,
        conversation: null,
        quick_replies: []
    };

    let lastMessageIds = '';
    let lastSeenId = 0;
    let messageLoaded = false;
    let busy = false;
    let loading = false;
    let closedConversation = null;

    function showStatus(text) {
        el('status').textContent = text;
    }

    function bubble(
        sender,
        text,
        attachmentId = 0,
        attachmentName = ''
    ) {

        const item = document.createElement('div');

        item.className = 'baf-msg' +
            (
                sender === 'user'
                    ? ' baf-user'
                    : ''
            );

        const label = document.createElement('small');

        label.textContent =
            sender === 'admin'
                ? '👤 Agent'
                : sender === 'bot'
                    ? '🤖 Bot'
                    : 'You';

        const content = document.createElement('span');
        content.textContent = text || '';

        item.append(label, content);

        if (attachmentId && attachmentName) {

            const link = document.createElement('a');

            link.href =
                api + '?action=file&id=' +
                encodeURIComponent(attachmentId);

            link.textContent =
                '📎 ' + attachmentName;

            item.append(link);
        }

        el('messages').append(item);

        el('messages').scrollTop =
            el('messages').scrollHeight;
    }

    function welcome() {

        if (!el('messages').children.length) {

            bubble(
                'bot',
                'Hi there! 👋 How can we help you today? ' +
                'Choose a topic or type a question.'
            );
        }
    }

    async function get(action) {

        const response = await fetch(
            api + '?action=' + encodeURIComponent(action),
            { credentials: 'same-origin' }
        );

        let data;

        try {
            data = await response.json();
        } catch {
            throw new Error(
                'Support API is unavailable.'
            );
        }

        if (!response.ok) {
            throw new Error(
                data.error || 'Request failed.'
            );
        }

        return data;
    }

    async function post(action, data) {

        const form = data instanceof FormData
            ? data
            : new FormData();

        if (!(data instanceof FormData)) {

            Object.entries(data || {})
                .forEach(([key, value]) => {

                    form.append(key, value);
                });
        }

        form.set('action', action);
        form.set('csrf_token', state.csrf);

        const response = await fetch(api, {
            method: 'POST',
            body: form,
            credentials: 'same-origin'
        });

        let result;

        try {
            result = await response.json();
        } catch {
            throw new Error(
                'Support API returned an invalid response.'
            );
        }

        if (!response.ok) {
            throw new Error(
                result.error || 'Request failed.'
            );
        }

        return result;
    }

    function beep() {

        try {

            const AudioCtx =
                window.AudioContext ||
                window.webkitAudioContext;

            if (!AudioCtx) return;

            const ctx = new AudioCtx();
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();

            osc.frequency.value = 720;
            gain.gain.value = .02;

            osc.connect(gain);
            gain.connect(ctx.destination);

            osc.start();

            osc.stop(
                ctx.currentTime + .09
            );

            osc.onended = () => ctx.close();

        } catch (_) {
            // Sound may require user interaction.
        }
    }

    function updateButtons() {

        const conv =
            closedConversation ||
            state.conversation;

        const ownsConv = !!(
            state.logged_in &&
            !state.is_admin &&
            conv
        );

        el('connect').classList.toggle(
            'baf-hidden',
            state.is_admin ||
            !!conv?.needs_agent ||
            conv?.status === 'closed'
        );

        el('end').classList.toggle(
            'baf-hidden',
            !ownsConv ||
            conv.status === 'closed'
        );

        el('download').classList.toggle(
            'baf-hidden',
            !ownsConv
        );

        el('email').classList.toggle(
            'baf-hidden',
            !ownsConv
        );

        el('rating').classList.toggle(
            'baf-hidden',
            !(
                ownsConv &&
                conv.status === 'closed' &&
                !conv.rating
            )
        );

        el('form').classList.toggle(
            'baf-hidden',
            !!state.is_admin
        );
    }

    async function bootstrap() {

        const data = await get('bootstrap');

        state = data;

        el('dot').classList.toggle(
            'baf-online',
            data.agents_online > 0
        );

        el('agents').textContent =
            data.agents_online > 0
                ? 'Agents Online: ' + data.agents_online
                : 'Agents Offline · Leave a message';

        el('quick').replaceChildren();

        const queries = {
            'Booking':
                'How do I book a flight?',

            'Cancellation':
                'How can I cancel or reschedule?',

            'Refund':
                'What is the refund policy?',

            'Baggage':
                'What is the baggage allowance?',

            'Payment':
                'I need payment help',

            'PNR and Ticket':
                'How do I download my ticket?',

            'Account':
                'I need login help'
        };

        (data.quick_replies || [])
            .forEach(topic => {

                const btn =
                    document.createElement('button');

                btn.type = 'button';
                btn.textContent = topic;

                btn.onclick = () => {
                    send(
                        queries[topic] || topic
                    );
                };

                el('quick').append(btn);
            });

        if (data.is_admin) {

            if (!messageLoaded) {

                el('messages').replaceChildren();

                bubble(
                    'bot',
                    'You are signed in as Admin. ' +
                    'Use Support Inbox to reply.'
                );

                const link =
                    document.createElement('a');

                link.href = 'support-admin.php';

                link.textContent =
                    'Open Admin Support Inbox →';

                link.style.margin = '12px';

                el('messages').append(link);
            }

            messageLoaded = true;

        } else if (data.logged_in) {

            await loadMessages();

        } else {

            welcome();
        }

        updateButtons();
    }

    async function loadMessages() {

        if (
            !state.logged_in ||
            state.is_admin ||
            loading ||
            closedConversation
        ) {
            return;
        }

        loading = true;

        try {

            const data = await get('messages');

            state.conversation = data.conversation;

            const messages = data.messages || [];

            const signature = messages
                .map(x => x.msg_id)
                .join(',');

            if (
                !messageLoaded ||
                signature !== lastMessageIds
            ) {

                if (
                    messageLoaded &&
                    messages.some(m =>
                        m.sender_type === 'admin' &&
                        Number(m.msg_id) > lastSeenId
                    )
                ) {
                    beep();
                }

                el('messages').replaceChildren();

                if (!messages.length) {
                    welcome();
                }

                messages.forEach(m => {

                    bubble(
                        m.sender_type,
                        m.message_text,
                        Number(m.msg_id),
                        m.attachment_name || ''
                    );
                });

                lastMessageIds = signature;

                lastSeenId = Math.max(
                    lastSeenId,
                    0,
                    ...messages.map(m =>
                        Number(m.msg_id)
                    )
                );

                messageLoaded = true;
            }

            updateButtons();

        } finally {

            loading = false;
        }
    }

    async function send(quickText) {

        if (busy) return;

        const message =
            typeof quickText === 'string'
                ? quickText
                : el('text').value.trim();

        const file = el('file').files[0];

        if (!message && !file) return;

        if (
            file &&
            file.size > 2 * 1024 * 1024
        ) {
            showStatus(
                'Attachment must be 2 MB or less.'
            );
            return;
        }

        busy = true;
        el('send').disabled = true;

        try {

            if (state.is_admin) {

                window.location.href =
                    'support-admin.php';

                return;
            }

            if (state.logged_in) {

                const data = new FormData();

                data.set('message', message);

                if (file) {
                    data.set('attachment', file);
                }

                await post('send', data);

                closedConversation = null;

                el('text').value = '';
                el('file').value = '';

                await loadMessages();

            } else {

                if (file) {

                    showStatus(
                        'Log in before sending attachments.'
                    );

                    return;
                }

                const answer = await post(
                    'ask',
                    { message }
                );

                bubble('user', message);
                bubble('bot', answer.answer);

                el('text').value = '';
            }

            showStatus('');

        } catch (e) {

            showStatus(e.message);

        } finally {

            busy = false;
            el('send').disabled = false;
        }
    }

    el('form').addEventListener(
        'submit',
        e => {

            e.preventDefault();
            send();
        }
    );

    function setPanel(open) {

        el('panel').classList.toggle(
            'baf-hidden',
            !open
        );

        el('toggle').setAttribute(
            'aria-expanded',
            String(open)
        );

        if (open) {

            bootstrap().catch(e =>
                showStatus(e.message)
            );

            el('text').focus();
        }
    }

    el('toggle').onclick = () => {

        setPanel(
            el('panel').classList.contains(
                'baf-hidden'
            )
        );
    };

    el('min').onclick = () => {
        setPanel(false);
    };

    el('close').onclick = () => {
        setPanel(false);
    };

    el('connect').onclick = async () => {

        if (!state.logged_in) {

            showStatus(
                'Log in to contact an agent.'
            );

            const link =
                document.createElement('a');

            link.href = 'login.php';
            link.textContent = ' Log in →';

            el('status').append(link);

            return;
        }

        try {

            await post('connect');

            await bootstrap();

            showStatus(
                'Request sent to support.'
            );

        } catch (e) {

            showStatus(e.message);
        }
    };

    el('end').onclick = async () => {

        if (!confirm('End this chat?')) {
            return;
        }

        try {

            const old =
                state.conversation;

            await post('end');

            closedConversation = {
                ...old,
                status: 'closed'
            };

            updateButtons();

            showStatus(
                'Chat ended. You can rate or download your transcript.'
            );

        } catch (e) {

            showStatus(e.message);
        }
    };

    el('download').onclick = () => {

        const conv =
            closedConversation ||
            state.conversation;

        if (conv) {

            window.location.href =
                api +
                '?action=transcript&conv_id=' +
                encodeURIComponent(
                    conv.conv_id
                );
        }
    };

    el('email').onclick = async () => {

        const conv =
            closedConversation ||
            state.conversation;

        if (!conv) return;

        try {

            const result = await post(
                'email',
                {
                    conv_id: conv.conv_id
                }
            );

            showStatus(
                result.message ||
                'Mail request submitted.'
            );

        } catch (e) {

            showStatus(e.message);
        }
    };

    for (let star = 1; star <= 5; star++) {

        const btn =
            document.createElement('button');

        btn.type = 'button';
        btn.textContent = '★';
        btn.title = star + ' stars';

        btn.onclick = async () => {

            const conv =
                closedConversation ||
                state.conversation;

            if (!conv) return;

            try {

                await post('rate', {
                    conv_id: conv.conv_id,
                    rating: star
                });

                conv.rating = star;

                updateButtons();

                showStatus(
                    'Thank you for rating us ' +
                    star +
                    '/5!'
                );

            } catch (e) {

                showStatus(e.message);
            }
        };

        el('stars').append(btn);
    }

    bootstrap().catch(e =>
        showStatus(e.message)
    );

    // Refresh online status.
    setInterval(() => {

        if (document.hidden) return;

        bootstrap().catch(e =>
            showStatus(e.message)
        );

    }, 15000);

    // Check new messages every 3 seconds.
    setInterval(() => {

        if (document.hidden) return;

        loadMessages().catch(e =>
            showStatus(e.message)
        );

    }, 3000);

})();
</script>

<?php if ($bafStandalone): ?>

</body>
</html>

<?php endif; ?>