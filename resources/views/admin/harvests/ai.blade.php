@extends('layouts.sidebar')

@section('title', 'AI Assistant')

@section('page-styles')
  <link rel="stylesheet" href="{{ asset('css/ai-chat.css') }}?v=32" data-turbo-track="dynamic">
@endsection

@section('content')

@php
  $coverage = $context['coverage'] ?? ['reports' => 0, 'farmers' => 0, 'crops' => []];
  $hasData  = (int) ($coverage['reports'] ?? 0) > 0;

  // The shared greeting and model badge read these; the harvest controller
  // only supplies the digest, so resolve the display values here.
  $myName = auth()->user()?->name ?? '';
  $llm = app(\App\AI\AiLlmClient::class);
  $aiProvider = $aiProvider ?? $llm->provider();
  $aiModel = $aiModel ?? $llm->modelName();
@endphp

<style>
  /* The admin chat uses the same shared skin as the Farmer and Store Owner
     chat pages. These rules only cover the admin-specific history panel and
     the reply metadata the shared stylesheet does not define. */
  .admin-ai-page .chat-header { gap: 8px; }
  .admin-ai-page .admin-ai-new { margin-left: 0; }
  /* Match the shared "New chat" button: the plain .history-btn rule is sized
     for an icon and would wrap this text-only button onto two lines. */
  .admin-ai-page #aiClearBtn {
    width: auto;
    min-width: 76px;
    height: 38px;
    padding: 0 12px;
    font-size: 12px;
    font-weight: 700;
    line-height: 1;
    white-space: nowrap;
    vertical-align: middle;
  }
  .admin-ai-page .history-wrap { margin-left: auto; }
  .admin-ai-page .history-dropdown {
    position: fixed;
    top: auto;
    right: auto;
    max-width: calc(100vw - 24px);
  }

  .admin-ai-page .admin-ai-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: var(--aq-sage-500, #648268);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    overflow: hidden;
    border: 0;
    padding: 0;
    cursor: pointer;
  }
  .admin-ai-page .admin-ai-avatar img { width: 100%; height: 100%; object-fit: cover; border-radius: 50%; }
  .admin-ai-page .admin-ai-avatar:focus-visible { outline: 2px solid var(--aq-sage-500, #648268); outline-offset: 2px; }

  .admin-ai-page .admin-ai-avatar .avatar-overlay {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(35, 47, 39, 0.55);
    color: #ffffff;
    opacity: 0;
    transition: opacity 0.15s ease;
    border-radius: 50%;
  }
  .admin-ai-page .admin-ai-avatar .avatar-overlay svg { width: 16px; height: 16px; }
  .admin-ai-page .admin-ai-avatar:hover .avatar-overlay,
  .admin-ai-page .admin-ai-avatar:focus-visible .avatar-overlay,
  .admin-ai-page .admin-ai-avatar:active .avatar-overlay { opacity: 1; }

  /* Inline reply metadata and badges reuse the shared warm-sage tokens. */
  .ai-msg-meta { display: flex; align-items: center; gap: 8px; margin-bottom: 5px; flex-wrap: wrap; }
  .ai-msg-name { font-size: 11.5px; font-weight: 700; color: var(--aq-ink-faint, #879a8c); }
  .ai-source-badge { display: inline-flex; align-items: center; gap: 6px; padding: 4px 11px; border-radius: 20px; font-size: 11.5px; font-weight: 700; }
  .ai-source-badge.ai { background: var(--aq-sage-50, #f1f4ee); color: var(--aq-sage-600, #4f6a56); border: 1px solid #d8e0d2; }
  .ai-source-badge.fallback { background: #f5f1e4; color: #8a7b54; border: 1px solid #e8dfc8; }

  .ai-page-link {
    display: inline-flex; align-items: center; gap: 6px; padding: 5px 12px;
    border-radius: 999px; text-decoration: none; font-weight: 600; font-size: 13px;
    color: #14532d; background: var(--aq-sage-50, #f1f4ee);
    border: 1px solid var(--aq-sage-500, #648268);
    transition: all 0.15s ease; margin: 2px 4px 2px 0;
  }
  .ai-page-link::before { content: '\2197'; font-size: 12px; }
  .ai-page-link:hover { background: #e4ece0; border-color: var(--aq-sage-600, #4f6a56); transform: translateY(-1px); }
  .ai-page-link:focus { outline: 2px solid var(--aq-sage-500, #648268); outline-offset: 2px; }
  .ai-page-link-list li { margin-bottom: 4px; list-style: none; position: relative; padding-left: 14px; }
  .ai-page-link-list li::before { content: '\2022'; position: absolute; left: 0; color: var(--aq-sage-500, #648268); }

  .ai-typing { display: inline-flex; gap: 4px; align-items: center; height: 20px; }
  .ai-typing span { width: 7px; height: 7px; border-radius: 50%; background: var(--aq-sage-500, #648268); animation: aiBounce 1.2s infinite ease-in-out; }
  .ai-typing span:nth-child(2) { animation-delay: 0.15s; }
  .ai-typing span:nth-child(3) { animation-delay: 0.3s; }
  @keyframes aiBounce { 0%, 60%, 100% { transform: translateY(0); opacity: 0.45; } 30% { transform: translateY(-5px); opacity: 1; } }

  .ai-msg-error {
    font-size: 13.5px; color: #b91c1c; background: #fef2f2;
    border: 1px solid #fecaca; border-radius: 12px; padding: 11px 14px;
    display: flex; align-items: flex-start; gap: 8px;
  }
  .ai-msg-error button {
    background: none; border: none; padding: 0; color: #b91c1c;
    font-weight: 700; cursor: pointer; text-decoration: underline;
    font-size: inherit; font-family: inherit;
  }

  .ai-clarification { margin-top: 12px; }
  .ai-clarification-title { margin: 0 0 9px; font-size: 12px; font-weight: 700; color: var(--aq-ink-faint, #879a8c); }
  .ai-clarification-options { display: grid; gap: 7px; }
  .ai-clarification-option {
    display: flex; align-items: flex-start; gap: 8px; padding: 8px 10px;
    border: 1px solid #dadfd3; border-radius: 9px; background: #fff;
    cursor: pointer; font-size: 12.5px; color: var(--aq-ink, #2c3530);
  }
  .ai-clarification-option:hover { border-color: var(--aq-sage-500, #648268); background: var(--aq-sage-50, #f1f4ee); }
  .ai-clarification-option input { margin-top: 2px; accent-color: var(--aq-sage-500, #648268); }
  .ai-clarification-custom { display: flex; gap: 7px; margin-top: 9px; }
  .ai-clarification-custom input { flex: 1; min-width: 0; height: 34px; padding: 0 10px; border: 1px solid #dadfd3; border-radius: 8px; font: inherit; font-size: 12.5px; }
  .ai-clarification-custom button { border: 0; border-radius: 8px; padding: 0 12px; background: var(--aq-sage-500, #648268); color: #fff; font: inherit; font-size: 12px; font-weight: 700; cursor: pointer; }

  .basis-row { display: flex; align-items: baseline; justify-content: space-between; gap: 12px; padding: 9px 0; border-bottom: 1px dashed var(--aq-line, #e4e8de); font-size: 13px; }
  .basis-row:last-child { border-bottom: none; }
  .basis-key { color: var(--aq-ink-faint, #879a8c); font-weight: 500; }
  .basis-val { color: var(--aq-sage-700, #3f5a46); font-weight: 700; font-variant-numeric: tabular-nums; text-align: right; }
  .basis-list { list-style: none; margin: 0; padding: 0; font-size: 13px; }
  .basis-list li { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 8px 0; border-bottom: 1px dashed var(--aq-line, #e4e8de); }
  .basis-list li:last-child { border-bottom: none; }
  .basis-list .k { color: var(--aq-ink, #2c3530); font-weight: 600; }
  .basis-list .v { color: var(--aq-ink-faint, #879a8c); font-variant-numeric: tabular-nums; white-space: nowrap; }
  .pill { display: inline-block; padding: 3px 9px; border-radius: 20px; font-size: 11.5px; font-weight: 700; }
  .pill.wet, .pill.dry { background: var(--aq-sage-50, #f1f4ee); color: var(--aq-sage-600, #4f6a56); }
</style>

@if(! $hasData)
  <div class="chat-page assistant-chat-page admin-ai-page">
    <div class="chat-container">
      <div class="chat-header">
        <button type="button" class="admin-ai-avatar" id="adminAiAvatarBtn" title="Change AI avatar" aria-label="Change AI avatar">
          <img src="{{ $aiAvatar }}" alt="AI Assistant" data-ai-avatar>
          <span class="avatar-overlay">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 0 1 2-2h1.5l1-1.5h9l1 1.5H19a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V9Z" /><circle cx="12" cy="13" r="3.2" /></svg>
          </span>
        </button>
        @include('admin.harvests.partials.ai-avatar-picker')
        <button id="aiClearBtn" class="history-btn admin-ai-new" type="button" onclick="resetChat()">New chat</button>
        <div class="history-wrap">
          <button class="history-btn" id="adminHistoryBtn" type="button" aria-label="Chat history" title="Chat history">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" d="M3 3v5h5" />
              <path stroke-linecap="round" stroke-linejoin="round" d="M3.05 13A9 9 0 1 0 6 5.3L3 8" />
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 7v5l4 2" />
            </svg>
          </button>
          <div class="history-dropdown" id="adminHistoryDropdown">
            <div class="history-head">
              <b>Chat History</b>
              <button class="history-new-btn" id="adminNewChatBtn" type="button">+ New Chat</button>
            </div>
            <div class="history-list" id="adminHistoryList"></div>
          </div>
        </div>
      </div>
      <div id="aiChatLog" class="chat-messages" role="log" aria-live="polite">
        <div class="chat-greeting">Hello{{ $myName ? ', ' . $myName : '' }}!</div>
        <div class="message-row welcome-suggestions">
          <div class="message-bubble">
            No harvest reports are available yet. Once records are added, I can explain performance, crops, seasons, costs, and the next items to verify.
          </div>
        </div>
      </div>
      <form id="aiChatForm" class="chat-input-area" onsubmit="submitChat(event)">
        <div class="chat-input-wrapper">
          <input id="aiChatInput" class="chat-input ai-chat-input" type="text" maxlength="500" placeholder="Ask about your association data…" autocomplete="off" aria-label="Ask about association data" />
          <button id="aiChatSend" class="send-btn" type="submit" title="Send" aria-label="Send">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" /></svg>
          </button>
        </div>
        <div class="ai-model-badge" aria-label="Current AI model">
          Powered by {{ ucfirst($aiProvider) }}{{ $aiModel ? ' (' . $aiModel . ')' : '' }}
        </div>
      </form>
    </div>
  </div>
@else
  <div class="chat-page assistant-chat-page admin-ai-page">
    <div class="chat-container">
      <div class="chat-header">
        <button type="button" class="admin-ai-avatar" id="adminAiAvatarBtn" title="Change AI avatar" aria-label="Change AI avatar">
          <img src="{{ $aiAvatar }}" alt="AI Assistant" data-ai-avatar>
          <span class="avatar-overlay">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 0 1 2-2h1.5l1-1.5h9l1 1.5H19a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V9Z" /><circle cx="12" cy="13" r="3.2" /></svg>
          </span>
        </button>
        @include('admin.harvests.partials.ai-avatar-picker')
        <button id="aiClearBtn" class="history-btn admin-ai-new" type="button" onclick="resetChat()">New chat</button>
        <div class="history-wrap">
          <button class="history-btn" id="adminHistoryBtn" type="button" aria-label="Chat history" title="Chat history">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" d="M3 3v5h5" />
              <path stroke-linecap="round" stroke-linejoin="round" d="M3.05 13A9 9 0 1 0 6 5.3L3 8" />
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 7v5l4 2" />
            </svg>
          </button>
          <div class="history-dropdown" id="adminHistoryDropdown">
            <div class="history-head">
              <b>Chat History</b>
              <button class="history-new-btn" id="adminNewChatBtn" type="button">+ New Chat</button>
            </div>
            <div class="history-list" id="adminHistoryList"></div>
          </div>
        </div>
      </div>

      <div id="aiChatLog" class="chat-messages" role="log" aria-live="polite">
        <div class="chat-greeting">Hello{{ $myName ? ', ' . $myName : '' }}!</div>
        <div class="message-row welcome-suggestions">
          <div class="message-bubble">
            <div class="quick-chips admin-ai-suggestions">
              <button type="button" class="chip ai-chip" onclick="askChip(this)">How did the latest season compare with the one before it?</button>
              <button type="button" class="chip ai-chip" onclick="askChip(this)">Is the wet season out-yielding the dry season?</button>
              <button type="button" class="chip ai-chip" onclick="askChip(this)">Which crops are trending up or down?</button>
            </div>
          </div>
        </div>
      </div>

      <form id="aiChatForm" class="chat-input-area" onsubmit="submitChat(event)">
        <div class="chat-input-wrapper">
          <input id="aiChatInput" class="chat-input ai-chat-input" type="text" maxlength="500" placeholder="Ask about your association data…" autocomplete="off" aria-label="Ask about association data" />
          <button id="aiChatSend" class="send-btn" type="submit" title="Send" aria-label="Send">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" /></svg>
          </button>
        </div>
        <div class="ai-model-badge" aria-label="Current AI model">
          Powered by {{ ucfirst($aiProvider) }}{{ $aiModel ? ' (' . $aiModel . ')' : '' }}
        </div>
      </form>
    </div>
  </div>
@endif

<script>
  (function () {
    var button = document.getElementById('adminAiAvatarBtn');
    var picker = document.getElementById('adminAvatarPicker');
    if (!button || !picker) return;

    button.addEventListener('click', function () { picker.hidden = false; });
    picker.addEventListener('click', function (event) {
      if (event.target.closest('[data-admin-avatar-close]')) {
        picker.hidden = true;
        return;
      }

      var choice = event.target.closest('[data-admin-avatar-key]');
      if (!choice) return;

      var key = choice.getAttribute('data-admin-avatar-key');
      var url = choice.getAttribute('data-admin-avatar-url');
      document.querySelectorAll('[data-ai-avatar]').forEach(function (image) { image.src = url; });

      fetch('{{ route("admin.harvests.ai.avatar") }}', {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': '{{ csrf_token() }}',
          'Accept': 'application/json',
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({ avatar: key })
      }).then(function (response) {
        if (!response.ok) throw new Error('avatar update failed');
        picker.hidden = true;
      }).catch(function () {
        picker.hidden = true;
      });
    });
  })();

  function escapeAiHtml(s) {
    return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }
  function inlineAi(t) {
    return t
      .replace(/\[([^\]]+)\]\((https?:[^)]+)\)/g, '<a class="ai-page-link" href="$2" target="_blank" rel="noopener noreferrer">$1</a>')
      .replace(/`([^`]+)`/g, '<code>$1</code>')
      .replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>')
      .replace(/\*([^*\n]+)\*/g, '<em>$1</em>')
      .replace(/\u2014/g, '&mdash;');
  }
  function renderAiMarkdown(raw) {
    raw = String(raw || '').replace(/\[[ \t\u00A0]*SUGGEST:[^\]]*\]/gi, '');
    var lines = escapeAiHtml(raw).replace(/\r/g, '').split('\n');
    var html = '', inUl = false, inOl = false;
    function closeUl() { if (inUl) { html += '</ul>'; inUl = false; } }
    function closeOl() { if (inOl) { html += '</ol>'; inOl = false; } }
    for (var i = 0; i < lines.length; i++) {
      var trimmed = lines[i].trim();
      if (!trimmed) { closeUl(); closeOl(); continue; }
      var hMatch = trimmed.match(/^(#{1,4})\s+(.+)$/);
      if (hMatch) {
        closeUl(); closeOl();
        var lvl = hMatch[1].length;
        html += '<h' + lvl + '>' + inlineAi(hMatch[2]) + '</h' + lvl + '>';
        continue;
      }
      if (/^[-*]\s+/.test(trimmed)) {
        if (!inUl) { closeOl(); html += '<ul>'; inUl = true; }
        html += '<li>' + inlineAi(trimmed.replace(/^[-*]\s+/, '')) + '</li>';
        continue;
      } else { closeUl(); }
      if (/^\d+[.)]\s+/.test(trimmed)) {
        if (!inOl) { closeUl(); html += '<ol>'; inOl = true; }
        html += '<li>' + inlineAi(trimmed.replace(/^\d+[.)]\s+/, '')) + '</li>';
        continue;
      } else { closeOl(); }
      html += '<p>' + inlineAi(trimmed) + '</p>';
    }
    closeUl(); closeOl();
    return html;
  }

  var aiLoading = false;
  var chatHistory = [];
  var chatInitial = null;
  var currentHistoryId = null;
  var lastQuestion = '';
  var askUrl = @json(route('admin.harvests.ai.ask'));
  var adminSuggestionPool = [
    'How did the latest season compare with the one before it?',
    'Is the wet season out-yielding the dry season?',
    'Which crops are trending up or down?',
    'Which crop has the highest average yield?',
    'Which season had the highest total harvest?',
    'Are harvest costs rising over time?',
    'Which crops have the most recorded reports?',
    'What changed most in the latest reporting period?',
    'Which farmers have submitted reports recently?',
    'Which crop should we review for unusual results?',
    'How consistent are yields across seasons?',
    'Summarize the association harvest performance.',
  ];
  adminSuggestionPool.sort(function () { return Math.random() - 0.5; });
  document.querySelectorAll('.admin-ai-suggestions .ai-chip').forEach(function (chip, index) {
    chip.textContent = adminSuggestionPool[index];
  });
  var adminSuggestionIndex = document.querySelectorAll('.admin-ai-suggestions .ai-chip').length;

  var BADGE_AI = '<span class="ai-source-badge ai" title="Wording by the model, every number from your harvest reports"><svg viewBox="0 0 24 24" width="11" height="11" stroke="currentColor" stroke-width="2.5" fill="none"><path d="M12 3l1.9 5.1L19 10l-5.1 1.9L12 17l-1.9-5.1L5 10l5.1-1.9L12 3z"/><path d="M19 15.5l.85 2.15L22 18.5l-2.15.85L19 21.5l-.85-2.15L16 18.5l2.15-.85L19 15.5z"/></svg>AI assisted</span>';
  var BADGE_DATA = '<span class="ai-source-badge ai" title="Read directly from Aquilala records"><svg viewBox="0 0 24 24" width="11" height="11" stroke="currentColor" stroke-width="2.5" fill="none"><polyline points="20 6 9 17 4 12"/></svg>From system records</span>';
  var BADGE_FALLBACK = '<span class="ai-source-badge fallback" title="Computed directly from Aquilala records"><svg viewBox="0 0 24 24" width="11" height="11" stroke="currentColor" stroke-width="2.5" fill="none"><polyline points="20 6 9 17 4 12"/></svg>Computed from system records</span>';

  function chatLog() { return document.getElementById('aiChatLog'); }

  function scrollChat() {
    var log = chatLog();
    if (log) log.scrollTop = log.scrollHeight;
  }

  function appendNode(kind, inner, plainText) {
    var log = chatLog();
    if (!log) return null;
    var el = document.createElement('div');
    el.className = 'message-row ' + kind;
    var hasCopy = arguments.length > 2;
    if (hasCopy) el.dataset.text = plainText == null ? '' : String(plainText);
    el.innerHTML = '<div class="message-bubble">' + inner +
      (hasCopy ? '<button type="button" class="copy-btn">Copy</button>' : '') + '</div>';
    log.appendChild(el);
    scrollChat();
    return el;
  }

  function appendUser(text) {
    appendNode('user', escapeAiHtml(text), text);
  }

  function appendAssistant(html, source) {
    var tmp = document.createElement('div');
    tmp.innerHTML = html;
    appendNode('bot',
      '<div class="ai-msg-meta"><span class="ai-msg-name">AI Assistant</span>' +
      (source === 'ai' ? BADGE_AI : (source === 'data' ? BADGE_DATA : (source === 'conversation' ? '' : BADGE_FALLBACK))) + '</div>' +
      html,
      (tmp.textContent || '').trim());
  }

  function appendClarification(options) {
    var choices = (options || []).map(function (option, index) {
      return '<label class="ai-clarification-option">' +
        '<input type="radio" name="aiClarification" value="' + escapeAiHtml(option.question) + '" data-clarification-question="' + escapeAiHtml(option.question) + '">' +
        '<span>' + escapeAiHtml(option.label) + '</span></label>';
    }).join('');
    appendAssistant(
      '<div class="ai-clarification">' +
      '<p class="ai-clarification-title">Choose what you meant, or clarify it below:</p>' +
      '<div class="ai-clarification-options">' + choices + '</div>' +
      '<form class="ai-clarification-custom" onsubmit="submitClarification(event)">' +
      '<input type="text" placeholder="Type your clarification…" aria-label="Clarify your question">' +
      '<button type="submit">Ask</button></form></div>',
      'conversation'
    );
  }

  function appendError(message) {
    appendNode('bot',
      '<div class="ai-msg-meta"><span class="ai-msg-name">AI Assistant</span></div>' +
      '<div class="ai-msg-error">' +
      '<svg viewBox="0 0 24 24" width="15" height="15" stroke="currentColor" stroke-width="2" fill="none" style="flex-shrink:0;margin-top:2px;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>' +
      '<span>' + escapeAiHtml(message) + ' <button type="button" onclick="retryLast()">Try again</button></span>' +
      '</div>');
  }

  function appendTyping() {
    return appendNode('bot',
      '<div class="ai-msg-meta"><span class="ai-msg-name">AI Assistant</span></div>' +
      '<span class="ai-typing" role="status" aria-label="Thinking"><span></span><span></span><span></span></span>');
  }

  /* ---------- Copy button (hover on desktop, tap on touch) ---------- */
  function showToast(message) {
    var t = document.getElementById('aiCopyToast');
    if (!t) {
      t = document.createElement('div');
      t.id = 'aiCopyToast';
      t.setAttribute('role', 'status');
      document.body.appendChild(t);
    }
    t.textContent = message;
    t.classList.add('show');
    clearTimeout(showToast._timer);
    showToast._timer = setTimeout(function () { t.classList.remove('show'); }, 1800);
  }

  function copyRowText(row) {
    var text = row && row.dataset ? row.dataset.text : '';
    if (!text) return;
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(text).then(function () {
        showToast('Copied to clipboard');
      }).catch(function () { legacyCopy(text); });
    } else {
      legacyCopy(text);
    }
  }

  function legacyCopy(text) {
    var textarea = document.createElement('textarea');
    textarea.value = text;
    textarea.style.position = 'fixed';
    textarea.style.opacity = '0';
    document.body.appendChild(textarea);
    textarea.select();
    var ok = false;
    try { ok = document.execCommand('copy'); } catch (err) { ok = false; }
    document.body.removeChild(textarea);
    showToast(ok ? 'Copied to clipboard' : 'Copy failed');
  }

  (function bindCopyHandlers() {
    var log = chatLog();
    if (!log) return;
    log.addEventListener('click', function (e) {
      var btn = e.target.closest ? e.target.closest('.copy-btn') : null;
      if (!btn) return;
      e.preventDefault();
      e.stopPropagation();
      copyRowText(btn.closest('.message-row'));
    });
    log.addEventListener('touchstart', function (e) {
      var row = e.target.closest ? e.target.closest('.message-row') : null;
      var rows = log.querySelectorAll('.message-row.touching');
      for (var i = 0; i < rows.length; i++) rows[i].classList.remove('touching');
      if (row) row.classList.add('touching');
    }, { passive: true });
    log.addEventListener('mousedown', function (e) {
      var row = e.target.closest ? e.target.closest('.message-row') : null;
      var rows = log.querySelectorAll('.message-row.copy-open');
      for (var i = 0; i < rows.length; i++) rows[i].classList.remove('copy-open');
      if (row && e.target.closest && !e.target.closest('.copy-btn')) row.classList.add('copy-open');
    });
  })();

  function setBusy(busy) {
    aiLoading = busy;
    var input = document.getElementById('aiChatInput');
    var send = document.getElementById('aiChatSend');
    var clear = document.getElementById('aiClearBtn');
    if (input) { input.disabled = busy; }
    if (clear) { clear.disabled = busy; }
    if (send) {
      send.disabled = busy;
      var label = send.querySelector('span');
      if (label) label.textContent = busy ? 'Thinking…' : 'Send';
    }
    var chips = document.querySelectorAll('#aiChatLog .ai-chip');
    for (var i = 0; i < chips.length; i++) chips[i].disabled = busy;
  }

  function retryLast() { if (lastQuestion) sendQuestion(lastQuestion); }

  function sendQuestion(message) {
    if (aiLoading) return;
    message = String(message || '').trim();
    if (!message) return;

    var log = chatLog();
    if (!log) return;

    if (chatInitial === null) chatInitial = log.innerHTML;
    lastQuestion = message;

    appendUser(message);
    setBusy(true);

    var payload = chatHistory.concat([{ role: 'user', content: message }]);
    var typing = appendTyping();

    fetch(askUrl, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': '{{ csrf_token() }}',
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ message: message, messages: payload })
    })
    .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, data: d }; }); })
    .then(function (res) {
      if (typing) typing.remove();
      setBusy(false);
      if (res.ok && res.data && res.data.reply) {
        appendAssistant(renderAiMarkdown(res.data.reply), res.data.source);
        if (res.data.source === 'clarification' && Array.isArray(res.data.clarification)) {
          appendClarification(res.data.clarification);
        }
        chatHistory = payload.concat([{ role: 'assistant', content: res.data.reply }]);
        persistCurrentConversation(res.data.context || null);
      } else if (res.data && res.data.error) {
        appendError(res.data.error);
      } else {
        appendError('That question could not be answered right now. Please try again.');
      }
      scrollChat();
    })
    .catch(function () {
      if (typing) typing.remove();
      setBusy(false);
      appendError('Connection failed. Please try again.');
      scrollChat();
    });
  }

  function submitChat(e) {
    if (e) e.preventDefault();
    var input = document.getElementById('aiChatInput');
    if (!input) return;
    var value = input.value.trim();
    if (!value || aiLoading) return;
    input.value = '';
    sendQuestion(value);
    input.focus();
  }

  function askChip(btn) {
    if (!btn || aiLoading) return;
    var question = btn.textContent;
    btn.textContent = adminSuggestionPool[adminSuggestionIndex % adminSuggestionPool.length];
    adminSuggestionIndex++;
    sendQuestion(question);
    var input = document.getElementById('aiChatInput');
    if (input) input.focus();
  }

  function submitClarification(event) {
    if (event) event.preventDefault();
    var form = event && event.target;
    if (!form) return;
    var input = form.querySelector('input[type="text"]');
    var radio = form.parentElement.querySelector('input[name="aiClarification"]:checked');
    var message = input && input.value.trim()
      ? input.value.trim()
      : (radio ? radio.getAttribute('data-clarification-question') : '');
    if (message) sendQuestion(message);
  }

  document.addEventListener('change', function (event) {
    var input = event.target;
    if (!input || input.name !== 'aiClarification' || !input.value) return;
    if (input.value.trim() !== '') sendQuestion(input.value);
  });

  function resetChat() {
    if (aiLoading) return;
    var log = chatLog();
    if (!log) return;
    persistCurrentConversation();
    if (chatInitial === null) chatInitial = log.innerHTML;
    log.innerHTML = chatInitial;
    chatHistory = [];
    currentHistoryId = null;
    lastQuestion = '';
    var input = document.getElementById('aiChatInput');
    if (input) { input.value = ''; input.focus(); }
  }

  function csrfHeader() {
    return {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': '{{ csrf_token() }}',
      'Accept': 'application/json'
    };
  }

  function persistCurrentConversation(context) {
    if (!chatHistory.length) return;
    fetch('{{ route("admin.harvests.ai.history.save") }}', {
      method: 'POST',
      headers: csrfHeader(),
      body: JSON.stringify({
        id: currentHistoryId,
        messages: chatHistory,
        context: context || {}
      })
    }).then(function (response) {
      return response.json();
    }).then(function (data) {
      if (data && data.id) currentHistoryId = data.id;
    }).catch(function () {});
  }

  function loadAdminHistoryMessages(messages) {
    var log = chatLog();
    if (!log) return;
    log.innerHTML = '';
    messages.forEach(function (message) {
      if (message.role === 'user') appendUser(message.content);
      if (message.role === 'assistant') appendAssistant(renderAiMarkdown(message.content), 'conversation');
    });
    scrollChat();
  }

  function renderAdminHistoryList(histories) {
    var list = document.getElementById('adminHistoryList');
    if (!list) return;
    if (!histories.length) {
      list.innerHTML = '<div class="history-empty">No conversations yet. Start chatting below!</div>';
      return;
    }
    list.innerHTML = histories.map(function (history) {
      return '<div class="history-item" data-id="' + history.id + '">' +
        '<div class="history-item-title" title="' + escapeAiHtml(history.title) + '">' + escapeAiHtml(history.title) + '</div>' +
        '<div class="history-item-time">' + escapeAiHtml(history.updated_at) + '</div>' +
        '<button class="history-item-del" type="button" title="Delete conversation" aria-label="Delete conversation">×</button>' +
        '</div>';
    }).join('');
  }

  function refreshAdminHistories() {
    fetch('{{ route("admin.harvests.ai.histories") }}', { headers: csrfHeader() })
      .then(function (response) { return response.json(); })
      .then(function (data) { renderAdminHistoryList((data && data.histories) || []); })
      .catch(function () {});
  }

  function loadAdminHistory(id) {
    fetch('{{ route("admin.harvests.ai.history.show", "__ID__") }}'.replace('__ID__', id), {
      headers: csrfHeader()
    }).then(function (response) {
      return response.json();
    }).then(function (data) {
      if (!data || !data.messages) return;
      chatHistory = data.messages.slice();
      currentHistoryId = Number(id);
      loadAdminHistoryMessages(data.messages);
      var dropdown = document.getElementById('adminHistoryDropdown');
      if (dropdown) dropdown.classList.remove('open');
    }).catch(function () {});
  }

  (function initialiseAdminHistory() {
    var button = document.getElementById('adminHistoryBtn');
    var dropdown = document.getElementById('adminHistoryDropdown');
    var list = document.getElementById('adminHistoryList');
    var newChat = document.getElementById('adminNewChatBtn');
    if (!button || !dropdown || !list || !newChat) return;
    if (chatInitial === null) {
      var initialLog = chatLog();
      chatInitial = initialLog ? initialLog.innerHTML : '';
    }

    // The chat shell (and the page itself) clip overflow, so a card anchored
    // inside the header gets cut off at an edge. Place it against the window
    // instead: right-aligned under the history button, flipped above it when
    // the space below runs out, and capped to whatever room is left so it
    // never hangs off the screen.
    function placeHistoryPanel() {
      if (!dropdown.classList.contains('open')) return;
      var rect = button.getBoundingClientRect();
      var margin = 12;

      // Measure the panel at its natural size first, or a height left over
      // from a previous (shorter) window would hide how much room it really
      // needs on this one.
      dropdown.style.maxHeight = '';
      var width = dropdown.offsetWidth;
      var height = dropdown.offsetHeight;

      var left = rect.right - width;
      if (left < margin) left = margin;
      if (left + width > window.innerWidth - margin) left = window.innerWidth - width - margin;

      var top = rect.bottom + 8;
      if (top + height > window.innerHeight - margin) top = rect.top - 8 - height;
      if (top < margin) top = margin;

      var room = window.innerHeight - top - margin;
      dropdown.style.left = Math.round(left) + 'px';
      dropdown.style.top = Math.round(top) + 'px';
      dropdown.style.maxHeight = Math.round(Math.max(room, 160)) + 'px';
    }

    button.addEventListener('click', function () {
      dropdown.classList.toggle('open');
      if (dropdown.classList.contains('open')) {
        placeHistoryPanel();
        refreshAdminHistories();
      }
    });
    // Reposition while it is open: the window can change, and the list grows
    // or shrinks as histories load and are deleted.
    window.addEventListener('resize', placeHistoryPanel);
    if (window.MutationObserver) {
      new MutationObserver(placeHistoryPanel).observe(list, { childList: true });
    }
    newChat.addEventListener('click', function () {
      resetChat();
      dropdown.classList.remove('open');
    });
    list.addEventListener('click', function (event) {
      var item = event.target.closest('.history-item');
      if (!item) return;
      var id = item.getAttribute('data-id');
      if (event.target.closest('.history-item-del')) {
        fetch('{{ route("admin.harvests.ai.history.destroy", "__ID__") }}'.replace('__ID__', id), {
          method: 'DELETE',
          headers: csrfHeader()
        }).then(function () { refreshAdminHistories(); }).catch(function () {});
        return;
      }
      loadAdminHistory(id);
    });
    document.addEventListener('click', function (event) {
      if (!dropdown.classList.contains('open')) return;
      if (event.target.closest('#adminHistoryBtn') || event.target.closest('#adminHistoryDropdown')) return;
      dropdown.classList.remove('open');
    });
    refreshAdminHistories();
  }());
</script>
@endsection
