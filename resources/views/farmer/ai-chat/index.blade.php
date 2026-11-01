@extends('layouts.sidebar')

@section('page-styles')
    {{-- One shared skin for every AI chat page: mute warm-sage palette and a
         viewport-locked layout so the composer is always on screen. --}}
    <link rel="stylesheet" href="{{ asset('css/ai-chat.css') }}?v=13" data-turbo-track="dynamic">
@endsection

@section('content')

<div class="chat-page assistant-chat-page">
    <div class="chat-container">

        <div class="chat-header">
            <button type="button" class="ai-avatar" id="aiAvatarBtn" title="Change AI avatar" aria-label="Change AI avatar">
                <img src="{{ $aiAvatar }}" alt="AI Assistant" data-ai-avatar>
                <span class="avatar-overlay">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 0 1 2-2h1.5l1-1.5h9l1 1.5H19a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V9Z" /><circle cx="12" cy="13" r="3.2" /></svg>
                </span>
            </button>
            {{-- The avatars belong to the system: the reader picks one of
                 them, and the account remembers which. --}}
            <div class="avatar-picker" id="avatarPicker" role="dialog" aria-modal="true" aria-label="Choose an AI avatar" hidden>
                <div class="avatar-picker-backdrop" data-avatar-close></div>
                <div class="avatar-picker-card">
                    <div class="avatar-picker-head">
                        <b>Choose an avatar</b>
                        <button type="button" class="avatar-picker-close" data-avatar-close aria-label="Close">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.4" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <div class="avatar-picker-grid">
                        @foreach($aiAvatars as $choice)
                            <button type="button" class="avatar-choice{{ $aiAvatarKey === $choice['key'] ? ' is-active' : '' }}" data-avatar-key="{{ $choice['key'] }}" data-avatar-url="{{ $choice['url'] }}" title="{{ $choice['label'] }}">
                                <img src="{{ $choice['url'] }}" alt="{{ $choice['label'] }}">
                                <span>{{ $choice['label'] }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="history-wrap">
                <button class="history-btn" id="historyBtn" aria-label="Chat history" title="Chat history">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 3v5h5" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.05 13A9 9 0 1 0 6 5.3L3 8" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 7v5l4 2" />
                    </svg>
                </button>
                <div class="history-dropdown" id="historyDropdown">
                    <div class="history-head">
                        <b>Chat History</b>
                        <button class="history-new-btn" id="newChatBtn">+ New Chat</button>
                    </div>
                    <div class="history-list" id="historyList"></div>
                </div>
            </div>
        </div>

        <div class="chat-messages" id="chatMessages">
            <div class="message-row">
                <div class="message-bubble">
                    <div>
                        Hello! I'm the Aquilala farming assistant for Bulan, Sorsogon.
                    </div>
                    <div style="margin-top: 6px;">
                        Ask me what to plant, what to use on it, what it will cost, or how to grow something — and I will
                        answer from the association's own records and from general farming practice.
                    </div>
                </div>
            </div>

            <div class="message-row">
                <div class="message-bubble">
                    <div class="quick-chips">
                        <button class="chip" onclick="sendChip(this)">
                            What fertilizers for rice?
                        </button>
                        <button class="chip" onclick="sendChip(this)">
                            Best products for wet season
                        </button>
                        <button class="chip" onclick="sendChip(this)">
                            Help me plan for corn
                        </button>
                        <button class="chip" onclick="sendChip(this)">
                            Estimate costs for 2 hectares
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="chat-input-area">
            <div class="chat-input-wrapper">
                <input
                    type="text"
                    class="chat-input"
                    id="chatInput"
                    placeholder="Ask about crops, products, or farming advice..."
                    autocomplete="off"
                />
                <button class="send-btn" id="sendBtn">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" />
                    </svg>
                </button>
            </div>
        </div>

    </div>
</div>

<div class="product-modal-overlay" id="productModal">
    <div class="product-modal">
        <div class="product-modal-head">
            <span class="product-modal-title">Product Details</span>
            <button class="product-modal-close" id="productModalClose" aria-label="Close">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>
        <div class="product-modal-body">
            <div class="product-modal-img"><img id="pmImg" alt="Product" src=""></div>
            <div class="product-modal-price-row">
                <span class="product-modal-price" id="pmPrice"></span>
                <span class="product-modal-price-unit" id="pmPriceUnit"></span>
            </div>
            <div class="product-modal-name" id="pmName"></div>
            <div class="product-modal-brand" id="pmBrand"></div>
            <div class="product-modal-meta">
                <div class="product-modal-meta-item">
                    <div class="product-modal-meta-label">Category</div>
                    <div class="product-modal-meta-value" id="pmCategory">—</div>
                </div>
                <div class="product-modal-meta-item">
                    <div class="product-modal-meta-label">Package</div>
                    <div class="product-modal-meta-value" id="pmPackage">—</div>
                </div>
                <div class="product-modal-meta-item">
                    <div class="product-modal-meta-label">Stock</div>
                    <div class="product-modal-meta-value" id="pmStock">—</div>
                </div>
                <div class="product-modal-meta-item">
                    <div class="product-modal-meta-label">Store</div>
                    <div class="product-modal-meta-value" id="pmStore">—</div>
                </div>
            </div>
            <div class="product-modal-desc" id="pmDesc"></div>
            <div class="product-modal-actions">
                <button class="product-modal-btn product-modal-btn-close" id="productModalBack">Back to chat</button>
                <a class="product-modal-btn product-modal-btn-visit" id="pmVisit" href="#">Visit Product</a>
            </div>
        </div>
    </div>
</div>

{{-- Toast sits outside the product overlay: an element inside a display:none
     overlay can never be seen, and the avatar/history feedback needs it. --}}
<div class="app-toast" id="appToast"></div>

<script>
    const chatMessages = document.getElementById('chatMessages');
    const chatInput = document.getElementById('chatInput');
    const sendBtn = document.getElementById('sendBtn');
    const chatHistory = [];
    // Structured conversation state from the server (last intent, crop, product
    // ids, season, area). Sent with every turn so "how much is it?" resolves
    // to the right product without replaying the whole transcript.
    let aiContext = null;
    let isSending = false;

    function scrollToBottom() {
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    // ─── Per-account AI avatar ──────────────────────────────────
    // The header avatar is a button: it opens the picker, the reader picks
    // one of the system's avatars, and the account remembers which one so it
    // follows them to every device. Nothing is uploaded.
    let aiAvatarUrl = @json($aiAvatar);
    const aiAvatarBtn = document.getElementById('aiAvatarBtn');
    const avatarPicker = document.getElementById('avatarPicker');

    function setAiAvatar(url) {
        aiAvatarUrl = url;
        document.querySelectorAll('[data-ai-avatar]').forEach(function (img) {
            img.src = url;
        });
    }

    if (aiAvatarBtn && avatarPicker) {
        aiAvatarBtn.addEventListener('click', function () {
            avatarPicker.hidden = false;
        });

        avatarPicker.addEventListener('click', function (e) {
            if (e.target.closest('[data-avatar-close]')) {
                avatarPicker.hidden = true;
                return;
            }

            const choice = e.target.closest('.avatar-choice');
            if (!choice) return;

            const url = choice.getAttribute('data-avatar-url');
            const key = choice.getAttribute('data-avatar-key');
            const previous = aiAvatarUrl;

            setAiAvatar(url);
            avatarPicker.querySelectorAll('.avatar-choice').forEach(function (b) {
                b.classList.toggle('is-active', b === choice);
            });

            fetch('{{ route("farmer.ai-chat.avatar") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ avatar: key }),
            })
                .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, d: d }; }); })
                .then(function (res) {
                    if (res.ok && res.d && res.d.ok) {
                        setAiAvatar(res.d.url);
                        showToast('AI avatar updated');
                        avatarPicker.hidden = true;
                    } else {
                        setAiAvatar(previous);
                        showToast((res.d && (res.d.message || res.d.error)) || 'Could not update avatar');
                    }
                })
                .catch(function () {
                    setAiAvatar(previous);
                    showToast('Could not update avatar');
                });
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !avatarPicker.hidden) avatarPicker.hidden = true;
        });
    }

    function escapeHtml(str) {
        return str
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function inlineFormat(s) {
        return s
            .replace(/`([^`]+)`/g, '<code>$1</code>')
            .replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>')
            .replace(/(^|[^*\s])\*([^*\n]+)\*/g, '$1<em>$2</em>');
    }

    function renderMarkdown(raw) {
        raw = String(raw).replace(/\[[ \t\u00A0]*SUGGEST:[^\]]*\]/gi, '');
        const lines = escapeHtml(raw).replace(/\r/g, '').split('\n');
        let html = '';
        let inUl = false, inOl = false, inBlockquote = false;

        const closeUl = () => { if (inUl) { html += '</ul>'; inUl = false; } };
        const closeOl = () => { if (inOl) { html += '</ol>'; inOl = false; } };
        const closeBlockquote = () => { if (inBlockquote) { html += '</blockquote>'; inBlockquote = false; } };

        const renderTable = (buf) => {
            const rows = buf
                .map(r => r.slice(1, -1).split('|').map(c => c.trim()))
                .filter(cells => !cells.every(c => /^:?-+:?$/.test(c)));
            if (!rows.length) return;
            html += '<div class="table-wrap"><table>';
            const header = rows.shift();
            html += '<thead><tr>' + header.map(c => '<th>' + inlineFormat(c) + '</th>').join('') + '</tr></thead><tbody>';
            rows.forEach(cells => {
                html += '<tr>' + cells.map(c => '<td>' + inlineFormat(c) + '</td>').join('') + '</tr>';
            });
            html += '</tbody></table></div>';
        };

        for (let i = 0; i < lines.length; i++) {
            const trimmed = lines[i].trim();

            if (!trimmed) {
                closeUl(); closeOl(); closeBlockquote();
                continue;
            }

            if (trimmed.startsWith('|') && trimmed.endsWith('|')) {
                closeUl(); closeOl();
                const buf = [];
                while (i < lines.length) {
                    const t = lines[i].trim();
                    if (t.startsWith('|') && t.endsWith('|')) { buf.push(t); i++; }
                    else break;
                }
                i--;
                renderTable(buf);
                continue;
            }

            if (trimmed.startsWith('&gt;')) {
                closeUl(); closeOl();
                const warnImg = '<img src="{{ asset('images/caution.png') }}" class="warn-icon-img" alt="Warning">';
                if (!inBlockquote) { html += '<blockquote>'; inBlockquote = true; }
                html += '<p>' + warnImg + inlineFormat(trimmed.replace(/^&gt;\s?/, '').replace(/^[\s\u{26A0}\u{FE0F}\u{2757}\u{2B21}]+/u, '').trim()) + '</p>';
                continue;
            } else {
                closeBlockquote();
            }

            const hMatch = trimmed.match(/^(#{1,3})\s+(.+)$/);
            if (hMatch) {
                closeUl(); closeOl();
                const lvl = hMatch[1].length;
                html += '<h' + lvl + '>' + inlineFormat(hMatch[2]) + '</h' + lvl + '>';
                continue;
            }

            if (/^[-*]\s+/.test(trimmed)) {
                if (!inUl) { closeOl(); html += '<ul>'; inUl = true; }
                html += '<li>' + inlineFormat(trimmed.replace(/^[-*]\s+/, '')) + '</li>';
                continue;
            } else {
                closeUl();
            }

            if (/^\d+[.)]\s+/.test(trimmed)) {
                if (!inOl) { closeUl(); html += '<ol>'; inOl = true; }
                html += '<li>' + inlineFormat(trimmed.replace(/^\d+[.)]\s+/, '')) + '</li>';
                continue;
            } else {
                closeOl();
            }

            html += '<p>' + inlineFormat(trimmed) + '</p>';
        }

        closeUl(); closeOl(); closeBlockquote();

        return html;
    }

    function addMessage(role, content) {
        const row = document.createElement('div');
        row.className = 'message-row';
        row.innerHTML =
            '<div class="message-bubble"><div>' + renderMarkdown(content) + '</div></div>';
        chatMessages.appendChild(row);
        scrollToBottom();
        return row;
    }

    function addUserMessage(text) {
        const row = document.createElement('div');
        row.className = 'message-row user';
        row.innerHTML =
            '<div class="message-bubble" style="background:#edf0e8;border-color:#e1e5da;"><div>' + escapeHtml(text).replace(/\n/g, '<br>') + '</div></div>';
        chatMessages.appendChild(row);
        scrollToBottom();
        return row;
    }

    function addLoadingMessage() {
        const row = document.createElement('div');
        row.className = 'message-row';
        row.id = 'loadingMsg';
        row.innerHTML =
            '<div class="message-bubble"><div style="color:#9ca3af;">Thinking...</div></div>';
        chatMessages.appendChild(row);
        scrollToBottom();
        return row;
    }

    function removeLoadingMessage() {
        const el = document.getElementById('loadingMsg');
        if (el) el.remove();
    }

    function disableInput() {
        isSending = true;
        chatInput.disabled = true;
        sendBtn.disabled = true;
        sendBtn.style.opacity = '0.5';
    }

    function enableInput() {
        isSending = false;
        chatInput.disabled = false;
        sendBtn.disabled = false;
        sendBtn.style.opacity = '1';
        chatInput.focus();
    }

    async function sendMessage(text) {
        if (!text || isSending) return;
        chatInput.value = '';

        // Add user message to UI
        addUserMessage(text);
        chatHistory.push({ role: 'user', content: text });

        disableInput();
        addLoadingMessage();

        try {
            const resp = await fetch('{{ route("farmer.ai-chat.send") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ messages: chatHistory, context: aiContext }),
            });

            const data = await resp.json();
            removeLoadingMessage();

            if (resp.ok && data.reply) {
                chatHistory.push({ role: 'assistant', content: data.reply });
                addMessage('assistant', data.reply);
                // Product cards are built by the server from database rows, so
                // the carousel and the prose can never disagree about a price.
                if (data.suggestions && data.suggestions.length) {
                    renderSuggestions(data.suggestions);
                }
                if (data.context) {
                    aiContext = data.context;
                }
                persistCurrentConversation();
            } else {
                addMessage('assistant', 'Sorry, something went wrong. ' + (data.error || 'Please try again.'));
            }
        } catch (err) {
            removeLoadingMessage();
            addMessage('assistant', 'Could not reach the AI service. Please check your connection and try again.');
        }

        enableInput();
    }

    function sendChip(btn) {
        const text = btn.textContent.trim();
        const next = quickSuggestionPool[quickSuggestionIndex % quickSuggestionPool.length];
        quickSuggestionIndex++;
        btn.textContent = next;
        sendMessage(text);
    }

    const quickSuggestionPool = [
        'What fertilizers for rice?',
        'Best products for wet season',
        'Help me plan for corn',
        'Estimate costs for 2 hectares',
        'How can I improve rice yield?',
        'What should I plant this month?',
        'Which products help with pests?',
        'Compare rice and corn costs',
        'What is a good dry-season crop?',
        'How much seed do I need for 1 hectare?',
        'How do I prepare soil for vegetables?',
        'What can I do about yellowing leaves?',
    ];
    quickSuggestionPool.sort(function () { return Math.random() - 0.5; });
    document.querySelectorAll('.quick-chips .chip').forEach(function (chip, index) {
        chip.textContent = quickSuggestionPool[index];
    });
    let quickSuggestionIndex = document.querySelectorAll('.quick-chips .chip').length;

    const productModal = document.getElementById('productModal');

    function openProductModal(s, url) {
        document.getElementById('pmImg').src = s.image;
        document.getElementById('pmImg').alt = s.name;
        document.getElementById('pmPrice').textContent = '₱' + s.price;
        document.getElementById('pmPriceUnit').textContent = '/ ' + (s.price_unit || 'each');
        document.getElementById('pmName').textContent = s.name;
        document.getElementById('pmBrand').textContent = s.brand ? s.brand + ' ' + (s.category || '') : (s.category || '');
        document.getElementById('pmCategory').textContent = s.category || '—';
        document.getElementById('pmPackage').textContent = s.package || '—';
        document.getElementById('pmStock').textContent = s.stock || '—';
        document.getElementById('pmStore').textContent = s.store || '—';
        document.getElementById('pmDesc').textContent = s.description || '';
        document.getElementById('pmVisit').href = url;
        productModal.classList.add('open');
        document.body.style.overflow = 'hidden';
    }

    function closeProductModal() {
        productModal.classList.remove('open');
        document.body.style.overflow = '';
    }

    function buildSuggestionRow(list) {
        const row = document.createElement('div');
        row.className = 'ai-suggestion';
        let cards = '';
        list.forEach(function (s) {
            // The carousel card has no separate size row, so it shows the
            // full label; the modal keeps the plain name plus its Package row.
            var label = s.display_name || s.name;
            cards +=
                '<a class="suggestion-card" href="' + s.url + '" data-product=\'' + JSON.stringify(s).replace(/'/g, '&#39;') + '\'>' +
                '<div class="suggestion-card-img"><img src="' + s.image + '" alt="' + escapeHtml(label) + '" loading="lazy"></div>' +
                '<div class="suggestion-card-body">' +
                '<div class="suggestion-card-name">' + escapeHtml(label) + '</div>' +
                '<div class="suggestion-card-price">₱' + s.price + ' / ' + escapeHtml(s.price_unit) + '</div>' +
                (s.store ? '<div class="suggestion-card-store">' + escapeHtml(s.store) + '</div>' : '') +
                '</div></a>';
        });
        row.innerHTML =
            '<div class="ai-suggestion-header">Available at Aquilala stores</div>' +
            '<div class="suggestion-carousel">' + cards + '</div>';

        row.querySelectorAll('.suggestion-card').forEach(function (card) {
            card.addEventListener('click', function (e) {
                e.preventDefault();
                const elem = this;
                openProductModal(JSON.parse(elem.getAttribute('data-product')), this.getAttribute('href'));
            });
        });

        return row;
    }

    function renderSuggestions(list) {
        const row = buildSuggestionRow(list);
        chatMessages.appendChild(row);
        scrollToBottom();
    }

    function extractSuggestKeywords(content) {
        const markers = String(content).match(/\[[ \t\u00A0]*SUGGEST:[^\]]*\]/gi);
        if (!markers) return [];
        const joined = markers.join(',')
            .replace(/\[[ \t\u00A0]*SUGGEST:[ \t\u00A0]*/gi, '')
            .replace(/]/g, '');
        return joined.split(',').map(function (s) { return s.trim(); }).filter(Boolean);
    }

    function fetchSuggestionsFor(keywords, anchorRow) {
        fetch('{{ route("farmer.ai-chat.suggestions") }}', {
            method: 'POST',
            headers: csrfHeader(),
            body: JSON.stringify({ keywords: keywords }),
        })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (d && d.suggestions && d.suggestions.length) {
                    const row = buildSuggestionRow(d.suggestions);
                    if (anchorRow && anchorRow.nextSibling) {
                        chatMessages.insertBefore(row, anchorRow.nextSibling);
                    } else {
                        chatMessages.appendChild(row);
                    }
                    scrollToBottom();
                }
            })
            .catch(function () {});
    }

    sendBtn.addEventListener('click', function () {
        sendMessage(chatInput.value.trim());
    });

    chatInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey && !isSending) {
            e.preventDefault();
            sendMessage(chatInput.value.trim());
        }
    });

    // Product modal: close on X, "Back to chat", overlay click, or Escape
    document.getElementById('productModalClose').addEventListener('click', closeProductModal);
    document.getElementById('productModalBack').addEventListener('click', closeProductModal);
    productModal.addEventListener('click', function (e) {
        if (e.target === productModal) closeProductModal();
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && productModal.classList.contains('open')) {
            closeProductModal();
        }
    });

    // ─── Chat history ────────────────────────────────────────────
    const historyBtn = document.getElementById('historyBtn');
    const historyDropdown = document.getElementById('historyDropdown');
    const historyList = document.getElementById('historyList');
    const newChatBtn = document.getElementById('newChatBtn');
    let currentHistoryId = null;
    let activeChatHasMessages = false;

    // Static welcome rows to restore on "New Chat"
    const staticRows = Array.prototype.slice.call(chatMessages.querySelectorAll('.message-row'));

    function csrfHeader() {
        return {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
        };
    }

    function persistCurrentConversation() {
        if (!chatHistory.length) return;
        fetch('{{ route("farmer.ai-chat.history.save") }}', {
            method: 'POST',
            headers: csrfHeader(),
            body: JSON.stringify({ id: currentHistoryId, messages: chatHistory, context: aiContext }),
        })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (d && d.id) {
                    currentHistoryId = d.id;
                    activeChatHasMessages = true;
                }
            })
            .catch(function () {});
    }

    function loadHistoryMessages(messages) {
        chatMessages.innerHTML = '';
        const awaitCards = [];
        messages.forEach(function (m) {
            if (m.role === 'user') {
                addUserMessage(m.content);
            } else if (m.role === 'assistant') {
                const row = addMessage('assistant', m.content);
                const keywords = extractSuggestKeywords(m.content);
                if (keywords.length) {
                    awaitCards.push({ row: row, keywords: keywords });
                }
            }
        });
        scrollToBottom();
        awaitCards.forEach(function (item) {
            fetchSuggestionsFor(item.keywords, item.row);
        });
    }

    function startNewChat() {
        if (chatHistory.length) {
            persistCurrentConversation();
        }
        closeDeleteModal();
        chatHistory.length = 0;
        aiContext = null;
        currentHistoryId = null;
        activeChatHasMessages = false;
        chatMessages.innerHTML = '';
        staticRows.forEach(function (row) {
            chatMessages.appendChild(row.cloneNode(true));
        });
        historyDropdown.classList.remove('open');
        chatInput.focus();
    }

    function renderHistoryList(histories) {
        closeDeleteModal();
        if (!histories.length) {
            historyList.innerHTML = '<div class="history-empty">No conversations yet. Start chatting below!</div>';
            return;
        }
        let items = '';
        histories.forEach(function (h) {
            items +=
                '<div class="history-item" data-id="' + h.id + '">' +
                '<div class="history-item-title" title="' + escapeHtml(h.title) + '">' + escapeHtml(h.title) + '</div>' +
                '<div class="history-item-time">' + escapeHtml(h.updated_at) + '</div>' +
                '<button class="history-item-del" title="Delete conversation">' +
                '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>' +
                '</button></div>';
        });
        historyList.innerHTML = items;
    }

    // Event delegation: single listener survives re-renders
    historyList.addEventListener('click', function (e) {
        const itemEl = e.target.closest('.history-item');
        if (!itemEl) return;
        if (e.target.closest('.history-item-del')) {
            e.stopPropagation();
            const titleEl = itemEl.querySelector('.history-item-title');
            openDeleteModal(itemEl.getAttribute('data-id'), itemEl, titleEl ? titleEl.textContent : 'this conversation');
        } else {
            e.stopPropagation();
            loadHistoryConversation(itemEl.getAttribute('data-id'));
        }
    });

    function loadHistoryConversation(id) {
        closeDeleteModal();
        fetch('{{ route("farmer.ai-chat.history.show", "__ID__") }}'.replace('__ID__', id), {
            method: 'GET',
            headers: csrfHeader(),
        })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (d && d.messages) {
                    chatHistory.length = 0;
                    d.messages.forEach(function (m) { chatHistory.push(m); });
                    aiContext = d.context || null;
                    currentHistoryId = Number(id);
                    activeChatHasMessages = true;
                    loadHistoryMessages(d.messages);
                }
            })
            .catch(function () {});
        historyDropdown.classList.remove('open');
    }

    let pendingDeleteId = null;
    let pendingDeleteItem = null;
    let toastTimer = null;

    const appToast = document.getElementById('appToast');

    function showToast(message) {
        clearTimeout(toastTimer);
        appToast.textContent = message;
        appToast.classList.add('show');
        toastTimer = setTimeout(function () {
            appToast.classList.remove('show');
        }, 2200);
    }

    // Delete-confirmation modal: built in JS and appended to <body> so no
    // parent overflow/transform/stacking context can ever hide it.
    const deleteModalOverlay = document.createElement('div');
    deleteModalOverlay.className = 'delete-modal-overlay';
    deleteModalOverlay.id = 'deleteConfirmModal';
    deleteModalOverlay.innerHTML =
        '<div class="delete-modal">' +
        '<div class="delete-modal-icon">' +
        '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>' +
        '</div>' +
        '<div class="delete-modal-title">Delete conversation?</div>' +
        '<div class="delete-modal-text">"<b id="deleteHistoryTitle"></b>" will be permanently removed from your chat history.</div>' +
        '<div class="delete-modal-actions">' +
        '<button class="delete-modal-btn delete-modal-cancel" id="deleteCancelBtn">Cancel</button>' +
        '<button class="delete-modal-btn delete-modal-delete" id="deleteConfirmBtn">Delete</button>' +
        '</div></div>';
    document.body.appendChild(deleteModalOverlay);
    const deleteHistoryTitle = document.getElementById('deleteHistoryTitle');
    const deleteConfirmBtn = document.getElementById('deleteConfirmBtn');
    const deleteCancelBtn = document.getElementById('deleteCancelBtn');

    function openDeleteModal(id, itemEl, title) {
        pendingDeleteId = id;
        pendingDeleteItem = itemEl;
        deleteHistoryTitle.textContent = title;
        deleteModalOverlay.classList.add('open');
    }

    function closeDeleteModal() {
        deleteModalOverlay.classList.remove('open');
        pendingDeleteId = null;
        pendingDeleteItem = null;
    }

    deleteConfirmBtn.addEventListener('click', function () {
        if (pendingDeleteId !== null) {
            deleteHistoryConversation(pendingDeleteId, pendingDeleteItem);
        }
        closeDeleteModal();
    });

    deleteCancelBtn.addEventListener('click', closeDeleteModal);
    deleteModalOverlay.addEventListener('click', function (e) {
        if (e.target === deleteModalOverlay) closeDeleteModal();
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && deleteModalOverlay.classList.contains('open')) {
            closeDeleteModal();
        }
    });

    function deleteHistoryConversation(id, itemEl) {
        fetch('{{ route("farmer.ai-chat.history.destroy", "__ID__") }}'.replace('__ID__', id), {
            method: 'DELETE',
            headers: csrfHeader(),
        })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (d && d.ok) {
                    itemEl.remove();
                    if (!historyList.querySelector('.history-item')) {
                        renderHistoryList([]);
                    }
                    showToast('Conversation deleted');
                }
            })
            .catch(function () {});
    }

    function toggleHistoryPanel() {
        if (historyDropdown.classList.contains('open')) {
            historyDropdown.classList.remove('open');
            return;
        }
        historyDropdown.classList.add('open');
        fetch('{{ route("farmer.ai-chat.histories") }}', {
            method: 'GET',
            headers: csrfHeader(),
        })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                renderHistoryList(d.histories || []);
            })
            .catch(function () {
                historyList.innerHTML = '<div class="history-empty">Could not load history.</div>';
            });
    }

    historyBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        toggleHistoryPanel();
    });

    newChatBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        startNewChat();
    });

    document.addEventListener('click', function (e) {
        if (!historyDropdown.contains(e.target) && !historyBtn.contains(e.target)) {
            historyDropdown.classList.remove('open');
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            historyDropdown.classList.remove('open');
        }
    });
</script>
@endsection
