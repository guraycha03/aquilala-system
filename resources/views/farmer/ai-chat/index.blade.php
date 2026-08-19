@extends('layouts.sidebar')

@section('content')
<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'DM Sans', sans-serif;
        background: #f3f6f4;
    }

    .breadcrumb {
        padding: 16px 24px 0;
        font-size: 13px;
        color: #6b7280;
    }

    .breadcrumb a {
        color: #6b7280;
        text-decoration: none;
    }

    .breadcrumb a:hover {
        color: #16a34a;
    }

    .breadcrumb span {
        margin: 0 6px;
        color: #9ca3af;
    }

    .chat-page {
        display: flex;
        flex-direction: column;
        height: calc(100vh - 60px);
        padding: 0 24px 24px;
        margin-top: 8px;
    }

    .chat-container {
        display: flex;
        flex-direction: column;
        flex: 1;
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        overflow: hidden;
        min-height: 0;
    }

    .chat-header {
        display: flex;
        align-items: center;
        padding: 16px 20px;
        border-bottom: 1px solid #e5e7eb;
        background: #ffffff;
        flex-shrink: 0;
    }

    .ai-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: #16a34a;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .ai-avatar svg {
        width: 20px;
        height: 20px;
        color: #ffffff;
    }

    .header-info {
        margin-left: 12px;
        flex: 1;
    }

    .header-title {
        font-size: 16px;
        font-weight: 600;
        color: #111827;
    }

    .header-status {
        display: flex;
        align-items: center;
        font-size: 12px;
        color: #6b7280;
        margin-top: 2px;
    }

    .status-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: #16a34a;
        margin-right: 6px;
    }

    .back-link {
        font-size: 13px;
        color: #16a34a;
        text-decoration: none;
        font-weight: 500;
        white-space: nowrap;
    }

    .back-link:hover {
        text-decoration: underline;
    }

    .chat-messages {
        flex: 1;
        overflow-y: auto;
        padding: 24px;
        scroll-behavior: smooth;
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .message-row {
        display: flex;
        gap: 10px;
        max-width: 720px;
        width: 100%;
    }

    .message-avatar {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        background: #16a34a;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        margin-top: 2px;
    }

    .message-avatar svg {
        width: 16px;
        height: 16px;
        color: #ffffff;
    }

    .message-bubble {
        background: #f0fdf4;
        border: 1px solid #dcfce7;
        border-radius: 12px;
        padding: 14px 18px;
        font-size: 14px;
        line-height: 1.65;
        color: #1f2937;
    }

    .message-bubble ul {
        margin: 10px 0 4px 18px;
        padding: 0;
    }

    .message-bubble ul li {
        margin-bottom: 4px;
    }

    .placeholder-badge {
        display: inline-block;
        background: #fef9c3;
        color: #92400e;
        font-size: 11px;
        font-weight: 500;
        padding: 3px 10px;
        border-radius: 20px;
        margin-bottom: 12px;
        border: 1px solid #fde68a;
    }

    .quick-chips {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 4px;
    }

    .chip {
        display: inline-flex;
        align-items: center;
        padding: 8px 14px;
        background: #ffffff;
        border: 1px solid #d1d5db;
        border-radius: 20px;
        font-size: 13px;
        color: #374151;
        cursor: pointer;
        transition: all 0.2s ease;
        font-family: 'DM Sans', sans-serif;
    }

    .chip:hover {
        border-color: #16a34a;
        background: #f0fdf4;
        color: #16a34a;
    }

    .chip svg {
        width: 14px;
        height: 14px;
        margin-right: 6px;
        color: #16a34a;
    }

    .chat-input-area {
        display: flex;
        align-items: center;
        padding: 16px 20px;
        border-top: 1px solid #e5e7eb;
        background: #ffffff;
        flex-shrink: 0;
    }

    .chat-input-wrapper {
        display: flex;
        align-items: center;
        flex: 1;
        background: #f9fafb;
        border: 1px solid #d1d5db;
        border-radius: 12px;
        padding: 4px 4px 4px 16px;
        transition: border-color 0.2s ease;
    }

    .chat-input-wrapper:focus-within {
        border-color: #16a34a;
        box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.1);
    }

    .chat-input {
        flex: 1;
        border: none;
        outline: none;
        background: transparent;
        font-size: 14px;
        font-family: 'DM Sans', sans-serif;
        color: #1f2937;
        padding: 10px 0;
    }

    .chat-input::placeholder {
        color: #9ca3af;
    }

    .send-btn {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        border: none;
        background: #16a34a;
        color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        flex-shrink: 0;
        transition: background 0.2s ease;
    }

    .send-btn:hover {
        background: #15803d;
    }

    .send-btn svg {
        width: 18px;
        height: 18px;
    }

    .chat-messages::-webkit-scrollbar {
        width: 6px;
    }

    .chat-messages::-webkit-scrollbar-track {
        background: transparent;
    }

    .chat-messages::-webkit-scrollbar-thumb {
        background: #d1d5db;
        border-radius: 3px;
    }

    .chat-messages::-webkit-scrollbar-thumb:hover {
        background: #9ca3af;
    }
</style>

<div class="breadcrumb">
    <a href="{{ route('dashboard.farmer') }}">Dashboard</a>
    <span>/</span>
    AI Assistant
</div>

<div class="chat-page">
    <div class="chat-container">

        <div class="chat-header">
            <div class="ai-avatar">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 00-2.455 2.456z" />
                </svg>
            </div>
            <div class="header-info">
                <div class="header-title">AI Assistant</div>
                <div class="header-status">
                    <div class="status-dot"></div>
                    Online
                </div>
            </div>
            <a href="{{ route('farmer.recommendations.index') }}" class="back-link">Back to Recommendations</a>
        </div>

        <div class="chat-messages" id="chatMessages">
            <div class="message-row">
                <div class="message-avatar">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 00-2.455 2.456z" />
                    </svg>
                </div>
                <div class="message-bubble">
                    <span class="placeholder-badge">Coming Soon - AI features under development</span>
                    <div>
                        Hello! I'm your AI farming assistant for Bulan, Sorsogon. I can help you with:
                    </div>
                    <ul>
                        <li>Finding the right fertilizers and inputs for your crops</li>
                        <li>Understanding application rates and timing</li>
                        <li>Seasonal recommendations</li>
                        <li>Cost estimation for your farm</li>
                    </ul>
                    <div style="margin-top: 10px;">
                        Tell me about your farm or ask me anything!
                    </div>
                </div>
            </div>

            <div class="message-row">
                <div class="message-avatar">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 00-2.455 2.456z" />
                    </svg>
                </div>
                <div class="message-bubble">
                    <div class="quick-chips">
                        <button class="chip" onclick="insertChip(this)">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 18v-5.25m0 0a6.01 6.01 0 001.5-.189m-1.5.189a6.01 6.01 0 01-1.5-.189m3.75 7.478a12.06 12.06 0 01-4.5 0m3.75 2.383a14.406 14.406 0 01-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 10-7.517 0c.85.493 1.509 1.333 1.509 2.316V18" /></svg>
                            What fertilizers for rice?
                        </button>
                        <button class="chip" onclick="insertChip(this)">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15a4.5 4.5 0 004.5 4.5H18a3.75 3.75 0 001.332-7.257 3 3 0 00-3.758-3.848 5.25 5.25 0 00-10.233 2.33A4.502 4.502 0 002.25 15z" /></svg>
                            Best products for wet season
                        </button>
                        <button class="chip" onclick="insertChip(this)">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" /></svg>
                            Help me plan for corn
                        </button>
                        <button class="chip" onclick="insertChip(this)">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
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

<script>
    function insertChip(el) {
        var text = el.textContent.trim();
        document.getElementById('chatInput').value = text;
        document.getElementById('chatInput').focus();
    }

    document.getElementById('sendBtn').addEventListener('click', function () {
        var input = document.getElementById('chatInput');
        var text = input.value.trim();
        if (!text) return;
        input.value = '';
    });

    document.getElementById('chatInput').addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            document.getElementById('sendBtn').click();
        }
    });
</script>
@endsection
