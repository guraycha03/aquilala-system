@extends('layouts.sidebar')

@section('title', 'AI Harvest Insights')

@section('content')
@include('partials.admin-tabs', [
  'tabs' => [
    ['key' => 'overview', 'label' => 'Overview',             'url' => route('admin.harvests.index')],
    ['key' => 'reports',  'label' => 'All Harvest Reports',  'url' => route('admin.harvests.reports')],
    ['key' => 'compare',  'label' => 'Estimate vs Harvest',   'url' => route('admin.harvests.compare')],
    ['key' => 'ai',       'label' => 'AI Insights',          'url' => route('admin.harvests.ai')],
  ],
  'activeTab' => 'ai',
])

@php
  $coverage = $context['coverage'] ?? ['reports' => 0, 'farmers' => 0, 'crops' => []];
  $totals   = $context['totals'] ?? [];
  $hasData  = (int) ($coverage['reports'] ?? 0) > 0;
  $crops    = $coverage['crops'] ?? [];
  // `farmers` is sorted best-yield-per-hectare first, so the last row is the
  // weakest performer — the question every admin wants to ask second.
  $worst    = collect($context['farmers'] ?? [])->last();
  $worstYield = $worst && isset($worst['yield_per_ha']) && $worst['yield_per_ha'] !== null
      ? number_format((float) $worst['yield_per_ha'], 3)
      : null;
@endphp

<style>
  .ai-page-head { margin-bottom: 8px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; }
  .ai-page-head h1 { font-size: 24px; font-weight: 700; color: #1e2922; margin: 0; }

  .ai-grid { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1.75fr); gap: 18px; align-items: start; }
  @media (max-width: 960px) { .ai-grid { grid-template-columns: minmax(0, 1fr); } }

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
  .ai-back:hover { color: #16a34a; }

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
  .ai-msg.bot .ai-msg-avatar { background: #f5f3ff; color: #6d28d9; border: 1px solid #ddd6fe; }
  .ai-msg.user .ai-msg-avatar { background: #ecfdf5; color: #16a34a; border: 1px solid #bbf7d0; }
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
  .ai-source-badge.ai { background: #f5f3ff; color: #6d28d9; border: 1px solid #ddd6fe; }
  .ai-source-badge.fallback { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }

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
  .pill.wet { background: #eff6ff; color: #1d4ed8; }
  .pill.dry { background: #fffbeb; color: #b45309; }

  .empty-state { text-align: center; padding: 54px 24px; }
  .empty-state h3 { font-size: 16px; color: #1e2922; margin: 14px 0 6px; }
  .empty-state p { font-size: 13.5px; color: #64748b; margin: 0 auto 18px; max-width: 440px; line-height: 1.65; }
</style>

<div class="ai-page-head">
  <div>
    <h1>AI Harvest Insights</h1>
    <p class="ai-card-sub" style="margin: 5px 0 0;">Ask anything about your harvest reports — every answer is read straight from those records, nothing else.</p>
  </div>
  <a href="{{ route('admin.harvests.index') }}" class="ai-back">
    <svg viewBox="0 0 24 24" width="15" height="15" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
    Back to Harvest Performance
  </a>
</div>

@if(! $hasData)
  <div class="ai-card" style="margin-top: 14px;">
    <div class="empty-state">
      <svg viewBox="0 0 24 24" width="42" height="42" stroke="#cbd5e1" stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
      <h3>No harvest reports yet</h3>
      <p>Once farmers start recording harvests, this page will explain recent versus past performance, yield factors, and the farmers behind the numbers.</p>
      <a href="{{ route('admin.harvests.index') }}" class="ai-ask" style="text-decoration:none;">View Harvest Performance</a>
    </div>
  </div>
@else
  <div class="ai-grid">
    {{-- ── Deterministic data basis ───────────────────────────── --}}
    <div class="ai-card">
      <div class="ai-card-head">
        <div>
          <p class="ai-card-title">Data this chat reads</p>
          <p class="ai-card-sub">Computed from harvest_reports, no AI involved.</p>
        </div>
      </div>
      <div class="ai-card-body" style="padding-top:6px; padding-bottom:6px;">
        <div class="basis-row"><span class="basis-key">Harvest reports</span><span class="basis-val">{{ number_format((int) $coverage['reports']) }}</span></div>
        <div class="basis-row"><span class="basis-key">Reporting farmers</span><span class="basis-val">{{ number_format((int) ($coverage['farmers'] ?? 0)) }}</span></div>
        <div class="basis-row"><span class="basis-key">Date range</span><span class="basis-val">{{ ($coverage['first_harvest'] ?? '—') }} &rarr; {{ ($coverage['last_harvest'] ?? '—') }}</span></div>
        <div class="basis-row"><span class="basis-key">Total yield</span><span class="basis-val">{{ number_format((float) ($totals['yield_tons'] ?? 0), 2) }} MT</span></div>
        <div class="basis-row"><span class="basis-key">Harvested area</span><span class="basis-val">{{ number_format((float) ($totals['area_ha'] ?? 0), 2) }} ha</span></div>
        <div class="basis-row"><span class="basis-key">Yield per hectare</span><span class="basis-val">{{ isset($totals['yield_per_ha']) && $totals['yield_per_ha'] !== null ? number_format((float) $totals['yield_per_ha'], 3) . ' MT/ha' : '—' }}</span></div>
        <div class="basis-row"><span class="basis-key">Expenses</span><span class="basis-val">&#8369;{{ number_format((float) ($totals['expenses_php'] ?? 0), 0) }}</span></div>
        <div class="basis-row">
          <span class="basis-key">Crops</span>
          <span class="basis-val">
            @forelse($crops as $crop)
              <span class="pill" style="background:#f1f5f9; color:#475569;">{{ $crop }}</span>
            @empty
              —
            @endforelse
          </span>
        </div>
      </div>
    </div>

    {{-- ── Chat ─────────────────────────────────────────────── --}}
    <div class="ai-card ai-chat-card">
      <div class="ai-card-head">
        <div>
          <p class="ai-card-title">Chat</p>
          <p class="ai-card-sub">Pick a question below, or type your own.</p>
        </div>
        <button id="aiClearBtn" class="ai-ask" type="button" style="background:#fff; color:#64748b; border:1.5px solid #e2e8f0; box-shadow:none; height:34px; padding:0 14px;" onclick="resetChat()">New chat</button>
      </div>

      <div id="aiChatLog" class="ai-chat-log" role="log" aria-live="polite">
        <div class="ai-msg bot">
          <div class="ai-msg-avatar" aria-hidden="true">AI</div>
          <div class="ai-msg-body">
            <div class="ai-msg-meta"><span class="ai-msg-name">Aquilala harvest assistant</span></div>
            <div class="ai-msg-bubble">
              <p>Hi — I read your <strong>{{ number_format((int) $coverage['reports']) }} harvest reports</strong> from {{ ($coverage['first_harvest'] ?? '—') }} to {{ ($coverage['last_harvest'] ?? '—') }} and nothing else.</p>
              <p>Ask me about seasons, crops, farmers, yield, costs, planting records, or the weather events you logged. Every figure I give you comes from those records.</p>
              <p>It is a conversation — if I name a farmer, just reply <strong>why</strong> and I will walk through their own land, planting cadence, seasons and crop batches with you.</p>
            </div>
          </div>
        </div>

        <div class="ai-msg bot">
          <div class="ai-msg-avatar" aria-hidden="true">AI</div>
          <div class="ai-msg-body">
            <div class="ai-msg-meta"><span class="ai-msg-name">Try one of these</span></div>
            <div class="ai-chip-row">
              @if($worst !== null && $worstYield !== null)
                <button type="button" class="ai-chip" onclick="askChip(this)">Why does {{ $worst['farmer'] }} sit at {{ $worstYield }} MT/ha?</button>
              @endif
              <button type="button" class="ai-chip" onclick="askChip(this)">How did the latest season compare with the one before it?</button>
              <button type="button" class="ai-chip" onclick="askChip(this)">Which farmers should I recognise?</button>
              <button type="button" class="ai-chip" onclick="askChip(this)">Is the wet season out-yielding the dry season?</button>
              <button type="button" class="ai-chip" onclick="askChip(this)">Which crops are trending up or down?</button>
              <button type="button" class="ai-chip" onclick="askChip(this)">What did we harvest in total and what did it cost?</button>
              <button type="button" class="ai-chip" onclick="askChip(this)">What should I verify next?</button>
            </div>
          </div>
        </div>
      </div>

      <form id="aiChatForm" class="ai-chat-form" onsubmit="submitChat(event)">
        <input id="aiChatInput" class="ai-chat-input" type="text" maxlength="500"
               placeholder="Ask about your harvest reports…" autocomplete="off"
               aria-label="Ask about your harvest reports" />
        <button id="aiChatSend" class="ai-ask" type="submit" title="Send">
          <svg viewBox="0 0 24 24" width="15" height="15" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
          <span>Send</span>
        </button>
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
  var lastQuestion = '';
  var askUrl = @json(route('admin.harvests.ai.ask'));

  var AVATAR_BOT = '<div class="ai-msg-avatar" aria-hidden="true">AI</div>';
  var AVATAR_USER = '<div class="ai-msg-avatar" aria-hidden="true"><svg viewBox="0 0 24 24" width="15" height="15" stroke="currentColor" stroke-width="2.2" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></div>';
  var BADGE_AI = '<span class="ai-source-badge ai" title="Wording by the model, every number from your harvest reports"><svg viewBox="0 0 24 24" width="11" height="11" stroke="currentColor" stroke-width="2.5" fill="none"><path d="M12 3l1.9 5.1L19 10l-5.1 1.9L12 17l-1.9-5.1L5 10l5.1-1.9L12 3z"/><path d="M19 15.5l.85 2.15L22 18.5l-2.15.85L19 21.5l-.85-2.15L16 18.5l2.15-.85L19 15.5z"/></svg>AI assisted</span>';
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
    el.className = 'ai-msg ' + kind;
    el.innerHTML = (kind === 'user' ? AVATAR_USER : AVATAR_BOT) + '<div class="ai-msg-body">' + inner + '</div>';
    log.appendChild(el);
    scrollChat();
    return el;
  }

  function appendUser(text) {
    appendNode('user', '<div class="ai-msg-bubble">' + escapeAiHtml(text) + '</div>');
  }

  function appendAssistant(html, source) {
    appendNode('bot',
      '<div class="ai-msg-meta"><span class="ai-msg-name">Aquilala harvest assistant</span>' +
      (source === 'ai' ? BADGE_AI : BADGE_FALLBACK) + '</div>' +
      '<div class="ai-msg-bubble">' + html + '</div>');
  }

  function appendError(message) {
    appendNode('bot',
      '<div class="ai-msg-meta"><span class="ai-msg-name">Aquilala harvest assistant</span></div>' +
      '<div class="ai-msg-error">' +
      '<svg viewBox="0 0 24 24" width="15" height="15" stroke="currentColor" stroke-width="2" fill="none" style="flex-shrink:0;margin-top:2px;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>' +
      '<span>' + escapeAiHtml(message) + ' <button type="button" onclick="retryLast()">Try again</button></span>' +
      '</div>');
  }

  function appendTyping() {
    return appendNode('bot',
      '<div class="ai-msg-meta"><span class="ai-msg-name">Aquilala harvest assistant</span></div>' +
      '<div class="ai-msg-bubble"><span class="ai-typing" role="status" aria-label="Thinking"><span></span><span></span><span></span></span></div>');
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
        chatHistory = payload.concat([{ role: 'assistant', content: res.data.reply }]);
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

  function resetChat() {
    if (aiLoading) return;
    var log = chatLog();
    if (!log) return;
    if (chatInitial === null) chatInitial = log.innerHTML;
    log.innerHTML = chatInitial;
    chatHistory = [];
    lastQuestion = '';
    var input = document.getElementById('aiChatInput');
    if (input) { input.value = ''; input.focus(); }
  }
</script>
@endsection
