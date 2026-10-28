@extends('layouts.sidebar')

@section('title', 'AI Insight')

@section('page-styles')
  <link rel="stylesheet" href="{{ asset('css/ai-chat.css') }}?v=2" data-turbo-track="dynamic">
@endsection

@section('content')

@php
  $coverage = $context['coverage'] ?? ['reports' => 0, 'farmers' => 0, 'crops' => []];
  $hasData  = (int) ($coverage['reports'] ?? 0) > 0;
@endphp

<style>
  .ai-page-head { margin-bottom: 8px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; }
  .ai-page-head h1 { font-size: 24px; font-weight: 700; color: #1e2922; margin: 0; }

  .ai-grid { display: block; }

  .ai-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 14px; overflow: hidden; box-shadow: 0 1px 2px rgba(0,0,0,.03); }
  .ai-card-head { padding: 18px 20px; border-bottom: 1px solid #eef1f4; display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; }
  .ai-card-title { font-size: 15px; font-weight: 700; color: #1a2b1e; margin: 0; }
  .ai-card-sub { font-size: 12.5px; color: #879a8c; margin: 3px 0 0 0; }
  .ai-card-body { padding: 20px; }

  .ai-ask {
    appearance: none; -webkit-appearance: none; height: 42px; padding: 0 20px;
    font-size: 13px; font-weight: 700; border-radius: 10px; border: none;
    background: #6d28d9; color: #fff; cursor: pointer;
    display: inline-flex; align-items: center; gap: 8px;
    box-shadow: 0 4px 12px rgba(109,40,217,.25);
    transition: all .2s ease;
  }
  .ai-ask:hover:not(:disabled) { background: #5b21b6; transform: translateY(-1px); }
  .ai-ask:disabled { opacity: .65; cursor: progress; transform: none; }

  .ai-back { font-size: 13px; font-weight: 600; color: #64748b; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
  .ai-back:hover { color: #4A7A54; }

  .ai-chat-card { display: flex; flex-direction: column; }
  .ai-chat-log {
    height: 440px; overflow-y: auto; padding: 20px;
    display: flex; flex-direction: column; gap: 16px;
  }
  .ai-msg { display: flex; gap: 10px; }
  .ai-msg.user { flex-direction: row-reverse; }
  .ai-msg-avatar {
    width: 30px; height: 30px; border-radius: 50%; flex-shrink: 0;
    display: inline-flex; align-items: center; justify-content: center;
    font-size: 11px; font-weight: 800;
  }
  .ai-msg.bot .ai-msg-avatar { background: #edf1e9; color: #648268; border: 1px solid #ddd6fe; }
  .ai-msg.user .ai-msg-avatar { background: #edf1e9; color: #648268; border: 1px solid #d4d4d4; }
  .ai-msg-body { max-width: 84%; min-width: 0; }
  .ai-msg-meta { display: flex; align-items: center; gap: 8px; margin-bottom: 5px; flex-wrap: wrap; }
  .ai-msg-name { font-size: 11.5px; font-weight: 700; color: #64748b; }
  .ai-msg-bubble {
    font-size: 13.5px; color: #3d4a41; line-height: 1.65;
    background: #f8fafc; border: 1px solid #eef1f4; border-radius: 12px;
    padding: 11px 14px; overflow-wrap: anywhere;
  }
  .ai-msg.user .ai-msg-bubble { background: #f5f3ff; border-color: #ddd6fe; color: #4c1d95; font-weight: 600; }
  .ai-msg-bubble p { margin: 0 0 9px; }
  .ai-msg-bubble p:last-child { margin-bottom: 0; }
  .ai-msg-bubble ul, .ai-msg-bubble ol { margin: 0 0 9px; padding-left: 19px; }
  .ai-msg-bubble li { margin-bottom: 5px; }
  .ai-msg-bubble li:last-child { margin-bottom: 0; }
  .ai-msg-bubble strong { color: #143d23; font-weight: 700; }
  .ai-msg.user .ai-msg-bubble strong { color: inherit; }
  .ai-msg-bubble code { background: #eef2f7; padding: 1px 5px; border-radius: 4px; font-size: 12.5px; }

  .ai-chip-row { display: flex; flex-wrap: wrap; gap: 8px; }
  .ai-chip {
    appearance: none; -webkit-appearance: none; cursor: pointer;
    background: #fff; border: 1.5px solid #e2e8f0; border-radius: 999px;
    padding: 7px 13px; font-size: 12.5px; font-weight: 600; color: #334155;
    font-family: inherit; text-align: left; transition: all .15s ease;
  }
  .ai-chip:hover { background: #f5f3ff; border-color: #c4b5fd; color: #6d28d9; }
  .ai-chip:disabled { opacity: .5; cursor: default; }

  .ai-typing { display: inline-flex; gap: 4px; align-items: center; height: 20px; }
  .ai-typing span { width: 7px; height: 7px; border-radius: 50%; background: #c4b5fd; animation: aiBounce 1.2s infinite ease-in-out; }
  .ai-typing span:nth-child(2) { animation-delay: .15s; }
  .ai-typing span:nth-child(3) { animation-delay: .3s; }
  @keyframes aiBounce { 0%, 60%, 100% { transform: translateY(0); opacity: .45; } 30% { transform: translateY(-5px); opacity: 1; } }

  .ai-msg-error {
    font-size: 13.5px; color: #b91c1c; background: #fef2f2;
    border: 1px solid #fecaca; border-radius: 12px; padding: 11px 14px;
    display: flex; align-items: flex-start; gap: 8px;
  }
  .ai-msg-error button {
    background: none; border: none; padding: 0; color: #b91c1c; font-weight: 700;
    cursor: pointer; text-decoration: underline; font-size: inherit; font-family: inherit;
  }

  .ai-chat-form { display: flex; gap: 10px; padding: 14px 20px; border-top: 1px solid #eef1f4; background: #fff; }
  .ai-chat-input {
    flex: 1; min-width: 0; height: 42px; padding: 0 14px;
    border: 1.5px solid #e2e8f0; border-radius: 10px;
    font-size: 13.5px; font-family: inherit; color: #1e293b; background: #f8fafc;
    outline: none; transition: border-color .15s ease;
  }
  .ai-chat-input:focus { border-color: #c4b5fd; background: #fff; }
  .ai-chat-input::placeholder { color: #94a3b8; }
  .ai-chat-input:disabled { opacity: .6; }

  .ai-source-badge { display: inline-flex; align-items: center; gap: 6px; padding: 4px 11px; border-radius: 20px; font-size: 11.5px; font-weight: 700; }
  .ai-source-badge.ai { background: #edf1e9; color: #648268; border: 1px solid #ddd6fe; }
  .ai-source-badge.fallback { background: #edf1e9; color: #648268; border: 1px solid #e2e8f0; }

  .basis-row { display: flex; align-items: baseline; justify-content: space-between; gap: 12px; padding: 9px 0; border-bottom: 1px dashed #eef1f4; font-size: 13px; }
  .basis-row:last-child { border-bottom: none; }
  .basis-key { color: #64748b; font-weight: 500; }
  .basis-val { color: #143d23; font-weight: 700; font-variant-numeric: tabular-nums; text-align: right; }

  .basis-list { list-style: none; margin: 0; padding: 0; font-size: 13px; }
  .basis-list li { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 8px 0; border-bottom: 1px dashed #eef1f4; }
  .basis-list li:last-child { border-bottom: none; }
  .basis-list .k { color: #334155; font-weight: 600; }
  .basis-list .v { color: #64748b; font-variant-numeric: tabular-nums; white-space: nowrap; }

  .pill { display: inline-block; padding: 3px 9px; border-radius: 20px; font-size: 11.5px; font-weight: 700; }
  .pill.wet { background: #edf1e9; color: #648268; }
  .pill.dry { background: #edf1e9; color: #648268; }

  .empty-state { text-align: center; padding: 54px 24px; }
  .empty-state h3 { font-size: 16px; color: #1e2922; margin: 14px 0 6px; }
  .empty-state p { font-size: 13.5px; color: #64748b; margin: 0 auto 18px; max-width: 440px; line-height: 1.65; }

  .admin-ai-page .chat-header { gap: 12px; }
  .admin-ai-avatar {
    width: 40px; height: 40px; border-radius: 50%; flex-shrink: 0;
    display: inline-flex; align-items: center; justify-content: center;
    background: var(--aq-sage-100, #e6ebe2); color: var(--aq-sage-600, #4f6a56);
  }
  .admin-ai-avatar svg { width: 21px; height: 21px; stroke: currentColor; fill: none; stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round; }
  .admin-ai-new { margin-left: auto; }
  /* Header order: New chat first (left), history last (right). The chat
     shell clips anything that runs past its own box, so the history card is
     positioned against the window instead — placeHistoryPanel() below sets
     its left/top when it opens, which keeps it fully on screen. */
  .admin-ai-page .history-wrap { margin-left: auto; }
  .admin-ai-page .history-dropdown {
    position: fixed;
    top: auto; right: auto;
    max-width: calc(100vw - 24px);
  }
  .admin-ai-suggestions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 16px;
    padding-top: 14px;
    border-top: 1px solid var(--aq-line, #e4e8de);
  }
  .admin-ai-suggestions::before {
    content: 'Suggested questions';
    flex-basis: 100%;
    color: var(--aq-ink-faint, #879a8c);
    font-size: 11px;
    font-weight: 700;
    letter-spacing: .06em;
    text-transform: uppercase;
  }
  .admin-ai-suggestions .chip { box-shadow: 0 1px 3px rgba(44, 53, 48, .06); }
  .ai-clarification { margin-top: 12px; }
  .ai-clarification-title { margin: 0 0 9px; font-size: 12px; font-weight: 700; color: #64748b; }
  .ai-clarification-options { display: grid; gap: 7px; }
  .ai-clarification-option { display: flex; align-items: flex-start; gap: 8px; padding: 8px 10px; border: 1px solid #e2e8f0; border-radius: 9px; background: #fff; cursor: pointer; font-size: 12.5px; color: #334155; }
  .ai-clarification-option:hover { border-color: #c4b5fd; background: #f5f3ff; }
  .ai-clarification-option input { margin-top: 2px; accent-color: #6d28d9; }
  .ai-clarification-custom { display: flex; gap: 7px; margin-top: 9px; }
  .ai-clarification-custom input { flex: 1; min-width: 0; height: 34px; padding: 0 10px; border: 1px solid #e2e8f0; border-radius: 8px; font: inherit; font-size: 12.5px; }
  .ai-clarification-custom button { border: 0; border-radius: 8px; padding: 0 12px; background: #6d28d9; color: #fff; font: inherit; font-size: 12px; font-weight: 700; cursor: pointer; }
</style>

<div class="breadcrumb">
  <span>AI Insight</span>
</div>

@if(! $hasData)
  <div class="chat-page admin-ai-page">
    <div class="chat-container">
      <div class="chat-header">
        <div class="admin-ai-avatar" aria-hidden="true">
          <svg viewBox="0 0 24 24"><path d="m12 3 1.8 5.2L19 10l-5.2 1.8L12 17l-1.8-5.2L5 10l5.2-1.8L12 3Z"/><path d="m19 15 .7 2.3L22 18l-2.3.7L19 21l-.7-2.3L16 18l2.3-.7L19 15Z"/></svg>
        </div>
        <div class="header-info">
          <div class="header-title">AI Insight</div>
          <div class="header-status"><div class="status-dot"></div>Admin data assistant</div>
        </div>
        <button id="aiClearBtn" class="chip admin-ai-new" type="button" onclick="resetChat()">New chat</button>
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
      <div class="chat-messages" id="aiChatLog" role="log" aria-live="polite">
        <div class="message-row">
          <div class="message-avatar"><span aria-hidden="true">AI</span></div>
          <div class="message-bubble">
            No harvest reports are available yet. Once records are added, I can explain performance, crops, seasons, costs, and the next items to verify.
          </div>
        </div>
      </div>
      <form id="aiChatForm" class="chat-input-area" onsubmit="submitChat(event)">
        <div class="chat-input-wrapper">
          <input id="aiChatInput" class="chat-input ai-chat-input" type="text" maxlength="500" placeholder="Ask about your association data…" autocomplete="off" aria-label="Ask about association data" />
          <button id="aiChatSend" class="send-btn" type="submit" title="Send" aria-label="Send">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/></svg>
          </button>
        </div>
      </form>
    </div>
  </div>
@else
  <div class="chat-page admin-ai-page">
    <div class="chat-container">
      <div class="chat-header">
        <div class="admin-ai-avatar" aria-hidden="true">
          <svg viewBox="0 0 24 24"><path d="m12 3 1.8 5.2L19 10l-5.2 1.8L12 17l-1.8-5.2L5 10l5.2-1.8L12 3Z"/><path d="m19 15 .7 2.3L22 18l-.7 2.3L19 21l-.7-2.3L16 18l2.3-.7L19 15Z"/></svg>
        </div>
        <div class="header-info">
          <div class="header-title">AI Insight</div>
          <div class="header-status"><div class="status-dot"></div>Admin data assistant</div>
        </div>
        <button id="aiClearBtn" class="chip admin-ai-new" type="button" onclick="resetChat()">New chat</button>
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
        <div class="message-row">
          <div class="message-avatar" aria-hidden="true">AI</div>
          <div class="message-bubble">
            <p>Hello. I’m Aquilala’s administrative insight assistant.</p>
            <p>I can help you interpret association performance, harvest trends, costs, crops, seasons, and recorded activities.</p>
            <p>Ask a question to begin. I’ll provide concise, evidence-based observations to support your review.</p>
            <div class="admin-ai-suggestions">
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
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/></svg>
          </button>
        </div>
      </form>
    </div>
  </div>
@endif

<script>
  function escapeAiHtml(s) {
    return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }
  function inlineAi(t) {
    return t
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

  var AVATAR_BOT = '<div class="message-avatar" aria-hidden="true">AI</div>';
  var AVATAR_USER = '<div class="message-avatar" aria-hidden="true"><svg viewBox="0 0 24 24" width="15" height="15" stroke="currentColor" stroke-width="2.2" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></div>';
  var BADGE_AI = '<span class="ai-source-badge ai" title="Wording by the model, every number from your harvest reports"><svg viewBox="0 0 24 24" width="11" height="11" stroke="currentColor" stroke-width="2.5" fill="none"><path d="M12 3l1.9 5.1L19 10l-5.1 1.9L12 17l-1.9-5.1L5 10l5.1-1.9L12 3z"/><path d="M19 15.5l.85 2.15L22 18.5l-2.15.85L19 21.5l-.85-2.15L16 18.5l2.15-.85L19 15.5z"/></svg>AI assisted</span>';
  var BADGE_DATA = '<span class="ai-source-badge ai" title="Read directly from Aquilala records"><svg viewBox="0 0 24 24" width="11" height="11" stroke="currentColor" stroke-width="2.5" fill="none"><polyline points="20 6 9 17 4 12"/></svg>From system records</span>';
  var BADGE_FALLBACK = '<span class="ai-source-badge fallback" title="Computed directly from harvest_reports while the model is offline"><svg viewBox="0 0 24 24" width="11" height="11" stroke="currentColor" stroke-width="2.5" fill="none"><polyline points="20 6 9 17 4 12"/></svg>Computed summary (AI offline)</span>';

  function chatLog() { return document.getElementById('aiChatLog'); }

  function scrollChat() {
    var log = chatLog();
    if (log) log.scrollTop = log.scrollHeight;
  }

  function appendNode(kind, inner) {
    var log = chatLog();
    if (!log) return null;
    var el = document.createElement('div');
    el.className = 'message-row ' + kind;
    el.innerHTML = (kind === 'user' ? AVATAR_USER : AVATAR_BOT) + '<div class="message-bubble">' + inner + '</div>';
    log.appendChild(el);
    scrollChat();
    return el;
  }

  function appendUser(text) {
    appendNode('user', escapeAiHtml(text));
  }

  function appendAssistant(html, source) {
    appendNode('bot',
      '<div class="ai-msg-meta"><span class="ai-msg-name">Aquilala admin insight</span>' +
      (source === 'ai' ? BADGE_AI : (source === 'data' ? BADGE_DATA : (source === 'conversation' ? '' : BADGE_FALLBACK))) + '</div>' +
      html);
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
      '<div class="ai-msg-meta"><span class="ai-msg-name">Aquilala admin insight</span></div>' +
      '<div class="ai-msg-error">' +
      '<svg viewBox="0 0 24 24" width="15" height="15" stroke="currentColor" stroke-width="2" fill="none" style="flex-shrink:0;margin-top:2px;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>' +
      '<span>' + escapeAiHtml(message) + ' <button type="button" onclick="retryLast()">Try again</button></span>' +
      '</div>');
  }

  function appendTyping() {
    return appendNode('bot',
      '<div class="ai-msg-meta"><span class="ai-msg-name">Aquilala admin insight</span></div>' +
      '<span class="ai-typing" role="status" aria-label="Thinking"><span></span><span></span><span></span></span>');
  }

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
    sendQuestion(btn.textContent);
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
    refreshAdminHistories();
  }());
</script>
@endsection
