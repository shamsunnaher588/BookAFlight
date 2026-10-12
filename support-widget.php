<?php
require_once __DIR__ . '/db.php';
?>

<style>
#baf-help {
    position: fixed;
    right: 18px;
    bottom: 18px;
    z-index: 99999;
    font-family: Arial, sans-serif;
    color: #172b4d;
    font-size: 14px;
}

#baf-help * {
    box-sizing: border-box;
}

#baf-help button,
#baf-help input {
    font: inherit;
}

#baf-help button {
    cursor: pointer;
}

#baf-help .hidden {
    display: none !important;
}

#baf-toggle {
    border: 0;
    background: #087cc6;
    color: white;
    padding: 15px 18px;
    border-radius: 50px;
    box-shadow: 0 5px 22px #0003;
    font-weight: bold;
}

#baf-dot {
    display: inline-block;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    margin-right: 6px;
    background: #d94b43;
    border: 1px solid white;
}

#baf-dot.online {
    background: #2dc66e;
}

#baf-panel {
    width: min(370px, calc(100vw - 24px));
    height: min(590px, calc(100dvh - 100px));
    background: white;
    border-radius: 16px;
    box-shadow: 0 8px 40px #0004;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    margin-bottom: 12px;
    border: 1px solid #dde6f3;
}

#baf-head {
    background: #082858;
    color: white;
    padding: 12px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

#baf-head button {
    border: 0;
    background: transparent;
    color: white;
    font-size: 23px;
    padding: 0 5px;
}

#baf-info {
    padding: 8px 12px;
    background: #f3f7fc;
    font-size: 12px;
}

#baf-chat {
    flex: 1;
    overflow: auto;
    display: flex;
    flex-direction: column;
    gap: 9px;
    padding: 12px;
    background: #f9fbff;
}

.baf-bubble {
    max-width: 88%;
    padding: 10px;
    border-radius: 12px;
    background: #e8f0fb;
    line-height: 1.4;
    white-space: pre-wrap;
    overflow-wrap: anywhere;
}

.baf-bubble.mine {
    background: #087cc6;
    color: white;
    align-self: flex-end;
}

.baf-bubble small {
    display: block;
    opacity: .75;
    font-size: 10px;
    margin-bottom: 4px;
}

.baf-bubble a {
    display: block;
    margin-top: 6px;
    color: inherit;
    text-decoration: underline;
}

#baf-quick {
    padding: 8px;
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
    max-height: 92px;
    overflow: auto;
}

#baf-quick button,
#baf-actions button {
    border: 1px solid #c8ddf4;
    background: white;
    border-radius: 15px;
    padding: 7px 9px;
    font-size: 12px;
    color: #075c9d;
}

#baf-actions {
    padding: 7px;
    display: flex;
    gap: 5px;
    flex-wrap: wrap;
}

#baf-form {
    display: flex;
    gap: 5px;
    border-top: 1px solid #e5e9f2;
    padding: 9px;
    align-items: center;
}

#baf-message {
    min-width: 0;
    flex: 1;
    padding: 10px;
    border: 1px solid #c7d6e8;
    border-radius: 9px;
}

#baf-file {
    width: 65px;
    font-size: 11px;
}

#baf-send {
    border: 0;
    background: #ffbc43;
    border-radius: 8px;
    padding: 11px;
    font-weight: bold;
}

#baf-status {
    min-height: 20px;
    font-size: 12px;
    padding: 2px 10px 8px;
    color: #5f718a;
}

#baf-rate {
    padding: 8px;
    background: #fff7dc;
    text-align: center;
}

#baf-rate button {
    border: 0;
    background: transparent;
    font-size: 24px;
    color: #d38b00;
}

@media(max-width:500px) {
    #baf-help {
        right: 8px;
        bottom: 8px;
    }

    #baf-panel {
        height: min(570px, calc(100dvh - 75px));
    }
}
</style>

<div id="baf-help">

    <section id="baf-panel" class="hidden"
             aria-label="BookAFlight customer support">

        <div id="baf-head">
            <strong>💬 BookAFlight Support</strong>

            <div>
                <button type="button" id="baf-min"
                        aria-label="Minimize">−</button>
                <button type="button" id="baf-close"
                        aria-label="Close window">×</button>
            </div>
        </div>

        <div id="baf-info">
            🤖 Chatbot 24/7 ·
            <span id="baf-agents">Checking agents...</span>
        </div>

        <div id="baf-chat" role="log"
             aria-live="polite"></div>

        <div id="baf-quick"
             aria-label="Quick questions"></div>

        <div id="baf-actions">
            <button type="button" id="baf-connect">
                👤 Speak to Agent
            </button>

            <button type="button" id="baf-end">
                End Chat
            </button>

            <button type="button" id="baf-download">
                Download Transcript
            </button>

            <button type="button" id="baf-email">
                Email Transcript
            </button>
        </div>

        <div id="baf-rate" class="hidden">
            Rate your support:
            <span id="baf-stars"></span>
        </div>

        <form id="baf-form" enctype="multipart/form-data">

            <input id="baf-message"
                   type="text"
                   maxlength="2000"
                   placeholder="Ask a question..."
                   aria-label="Chat message">

            <input id="baf-file"
                   type="file"
                   accept=".png,.jpg,.jpeg,.webp,.pdf"
                   aria-label="Attach screenshot or PDF"
                   title="2 MB maximum">

            <button id="baf-send" type="submit">
                ➤
            </button>

        </form>

        <div id="baf-status">
            Never send passwords, OTPs, full card numbers or PINs.
        </div>

    </section>

    <button type="button" id="baf-toggle">
        <span id="baf-dot"></span>
        💬 Need Help?
    </button>

</div>

<script>
(() => {
    'use strict';

    if (window.__bafChatLoaded) return;
    window.__bafChatLoaded = true;

    const $ = id => document.getElementById('baf-' + id);
    const api = 'support-api.php';
    const panel = $('panel');
    const chat = $('chat');

    let state = {
        logged_in: false,
        is_admin: false,
        csrf: '',
        conversation: null,
        agents_online: 0
    };

    let lastSignature = '';
    let lastIncoming = 0;
    let initialized = false;

    function status(text) {
        $('status').textContent = text;
    }

    function addBubble(sender, text, id = 0, name = '') {

        const div = document.createElement('div');

        div.className = 'baf-bubble' +
            (sender === 'user' ? ' mine' : '');

        const label = document.createElement('small');

        label.textContent =
            sender === 'bot' ? '🤖 Chatbot' :
            sender === 'admin' ? '👤 Support Agent' : 'You';

        const message = document.createElement('span');
        message.textContent = text;

        div.append(label, message);

        if (name && id) {
            const link = document.createElement('a');

            link.href = api + '?action=file&id=' +
                encodeURIComponent(id);

            link.textContent = '📎 ' + name;

            div.appendChild(link);
        }

        chat.appendChild(div);
    }

    function welcome() {
        if (!initialized) {
            addBubble(
                'bot',
                'Hi there! 👋 Welcome to BookAFlight. ' +
                'How can we help you today?'
            );

            initialized = true;
        }
    }

    async function request(action, options) {

        const response = await fetch(
            api + (
                options
                ? ''
                : '?action=' + encodeURIComponent(action)
            ),
            options || { credentials: 'same-origin' }
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
        body.set('csrf_token', state.csrf);

        return request(action, {
            method: 'POST',
            body,
            credentials: 'same-origin'
        });
    }

    function beep() {
        try {
            const Audio = window.AudioContext ||
                window.webkitAudioContext;

            if (!Audio) return;

            const context = new Audio();
            const oscillator = context.createOscillator();
            const volume = context.createGain();

            oscillator.frequency.value = 660;
            volume.gain.value = .025;

            oscillator.connect(volume);
            volume.connect(context.destination);

            oscillator.start();
            oscillator.stop(context.currentTime + .1);

            oscillator.onended = () => context.close();

        } catch (e) {}
    }

    function syncButtons() {

        const c = state.conversation;

        const hasConversation =
            state.logged_in &&
            !state.is_admin &&
            !!c;

        $('end').style.display =
            hasConversation && c.status !== 'closed'
                ? ''
                : 'none';

        $('download').style.display =
            hasConversation ? '' : 'none';

        $('email').style.display =
            hasConversation ? '' : 'none';

        $('connect').style.display =
            state.is_admin ||
            c?.needs_agent ||
            c?.status === 'closed'
                ? 'none'
                : '';

        $('rate').classList.toggle(
            'hidden',
            !(hasConversation &&
              c.status === 'closed' &&
              !c.rating)
        );

        $('form').style.display =
            state.is_admin ? 'none' : '';

        if (state.is_admin) {
            $('quick').replaceChildren();
            status('Open the Admin Support Inbox to reply.');
        }
    }

    async function bootstrap() {

        try {
            state = await request('bootstrap');

            $('dot').classList.toggle(
                'online',
                state.agents_online > 0
            );

            $('agents').textContent =
                state.agents_online > 0
                    ? 'Agents Online: ' + state.agents_online
                    : 'Agents Offline · Leave a message';

            $('quick').replaceChildren();

            (state.quick_replies || []).forEach(question => {

                const button = document.createElement('button');
                button.type = 'button';
                button.textContent = question;

                button.onclick = () => send(question);

                $('quick').appendChild(button);
            });

            syncButtons();

            if (state.is_admin) {

                chat.replaceChildren();

                addBubble(
                    'bot',
                    'You are signed in as Admin. ' +
                    'Use the Support Inbox to reply.'
                );

                const link = document.createElement('a');
                link.href = 'support-admin.php';
                link.textContent = 'Open Support Inbox →';
                link.style.padding = '12px';

                chat.appendChild(link);

            } else if (state.logged_in) {

                await loadMessages();

            } else {
                welcome();
            }

        } catch (error) {
            status(error.message);
        }
    }

    async function loadMessages() {

        if (!state.logged_in || state.is_admin) return;

        try {
            const data = await request('messages');

            state.conversation = data.conversation;

            const messages = data.messages || [];

            const signature = messages.map(
                m => m.msg_id
            ).join(':');

            if (lastSignature !== signature) {

                const incoming = messages.filter(
                    m => Number(m.msg_id) > lastIncoming &&
                         m.sender_type === 'admin'
                );

                if (lastSignature && incoming.length) {
                    beep();
                }

                chat.replaceChildren();

                if (messages.length) {

                    messages.forEach(message => {

                        addBubble(
                            message.sender_type,
                            message.message_text,
                            Number(message.msg_id),
                            message.attachment_name || ''
                        );
                    });

                } else {
                    initialized = false;
                    welcome();
                }

                lastSignature = signature;

                lastIncoming = Math.max(
                    0,
                    ...messages.map(m => Number(m.msg_id))
                );

                chat.scrollTop = chat.scrollHeight;
            }

            syncButtons();

        } catch (error) {
            status(error.message);
        }
    }

    async function send(quickQuestion) {

        const message =
            typeof quickQuestion === 'string'
                ? quickQuestion
                : $('message').value.trim();

        const file = $('file').files[0];

        if (!message && !file) return;

        if (file && file.size > 2097152) {
            status('File must be 2 MB or smaller.');
            return;
        }

        try {
            $('send').disabled = true;

            if (state.is_admin) {
                location.href = 'support-admin.php';
                return;
            }

            if (state.logged_in) {

                const body = new FormData();
                body.append('message', message);

                if (file) {
                    body.append('attachment', file);
                }

                await post('send', body);
                await loadMessages();

            } else {

                if (file) {
                    status('Please log in to send attachments.');
                    return;
                }

                addBubble('user', message);

                const reply = await post('ask', {
                    message
                });

                addBubble('bot', reply.answer);
                chat.scrollTop = chat.scrollHeight;
            }

            $('message').value = '';
            $('file').value = '';
            status('');

        } catch (error) {
            status(error.message);

        } finally {
            $('send').disabled = false;
        }
    }

    $('form').onsubmit = event => {
        event.preventDefault();
        send();
    };

    $('toggle').onclick = () => {

        panel.classList.toggle('hidden');

        if (!panel.classList.contains('hidden')) {
            bootstrap();
            $('message').focus();
        }
    };

    $('min').onclick = () => panel.classList.add('hidden');
    $('close').onclick = () => panel.classList.add('hidden');

    $('connect').onclick = async () => {

        if (!state.logged_in) {

            status('Please log in to contact an agent.');

            const link = document.createElement('a');
            link.href = 'login.php';
            link.textContent = ' Log in →';

            $('status').appendChild(link);
            return;
        }

        try {
            await post('connect');
            await bootstrap();

            status('Your request has reached the support queue.');

        } catch (error) {
            status(error.message);
        }
    };

    $('end').onclick = async () => {

        if (!confirm('End this conversation?')) return;

        try {
            await post('end');
            await bootstrap();

            status('Chat ended. You can rate the experience.');

        } catch (error) {
            status(error.message);
        }
    };

    $('download').onclick = () => {

        if (state.conversation) {
            location.href = api +
                '?action=transcript&conv_id=' +
                state.conversation.conv_id;
        }
    };

    $('email').onclick = async () => {

        if (!state.conversation) return;

        try {
            const result = await post('email', {
                conv_id: state.conversation.conv_id
            });

            status(result.message || 'Email requested.');

        } catch (error) {
            status(error.message);
        }
    };

    const stars = $('stars');

    for (let i = 1; i <= 5; i++) {

        const button = document.createElement('button');
        button.type = 'button';
        button.textContent = '★';
        button.title = i + ' stars';

        button.onclick = async () => {

            try {
                await post('rate', {
                    conv_id: state.conversation.conv_id,
                    rating: i
                });

                $('rate').classList.add('hidden');
                status('Thanks for your ' + i + '-star rating!');

            } catch (error) {
                status(error.message);
            }
        };

        stars.appendChild(button);
    }

    bootstrap();

    setInterval(() => {

        if (!document.hidden) {
            bootstrap();
        }

    }, 15000);

    setInterval(() => {

        if (
            !document.hidden &&
            state.logged_in &&
            !state.is_admin
        ) {
            loadMessages();
        }

    }, 3000);

})();
</script>