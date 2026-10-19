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

    /* Full-page layout: kill padding/whitespace so the chat fills the content area */
    .main-content {
        padding: 0;
    }

    .breadcrumb {
        height: 64px;
        padding: 0 24px;
        display: flex;
        align-items: center;
        background: #ffffff;
        border-bottom: 1px solid #e5e7eb;
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
        height: calc(100vh - 64px);
        padding: 0;
        margin: 0;
    }

    .chat-container {
        display: flex;
        flex-direction: column;
        flex: 1;
        background: #ffffff;
        border: none;
        border-radius: 0;
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
        overflow: hidden;
    }

    .ai-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 50%;
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

    .chat-messages {
        flex: 1;
        overflow-y: auto;
        overflow-x: hidden;
        padding: 24px;
        scroll-behavior: smooth;
        display: flex;
        flex-direction: column;
        gap: 20px;
        background: linear-gradient(180deg, #f8fbf9 0%, #f0f6f2 100%);
    }

    .message-row {
        display: flex;
        gap: 10px;
        max-width: 720px;
        width: 100%;
        min-width: 0;
    }

    /* User messages: full-width row, avatar at the far right, text just left of it */
    .message-row.user {
        max-width: 100%;
        flex-direction: row-reverse;
    }

    .message-row.user .message-bubble {
        max-width: 720px;
    }

    /* Keep AI/user bubbles and their contents inside their containers */
    .message-bubble {
        overflow-wrap: break-word;
        word-break: break-word;
        min-width: 0;
    }

    /* Markdown tables: scroll inside the bubble instead of forcing the page wide */
    .message-bubble .table-wrap {
        overflow-x: auto;
        max-width: 100%;
        -webkit-overflow-scrolling: touch;
    }

    .message-bubble code {
        white-space: pre-wrap;
        word-break: break-all;
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
        overflow: hidden;
    }

    .message-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 50%;
    }

    .message-bubble {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 14px 18px;
        font-size: 14px;
        line-height: 1.65;
        color: #1f2937;
        box-shadow: 0 1px 2px rgba(16, 24, 40, 0.05);
        max-width: 720px;
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
        justify-content: center;
        padding: 16px 20px;
        border-top: 1px solid #e5e7eb;
        background: #ffffff;
        flex-shrink: 0;
    }

    .chat-input-wrapper {
        display: flex;
        align-items: center;
        flex: 1;
        max-width: 720px;
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

    .message-bubble h1,
    .message-bubble h2,
    .message-bubble h3,
    .message-bubble h4 {
        margin: 14px 0 6px;
        font-weight: 700;
        color: #111827;
        line-height: 1.4;
    }

    .message-bubble h1 { font-size: 16px; }
    .message-bubble h2 { font-size: 15.5px; }
    .message-bubble h3 { font-size: 15px; }

    .message-bubble p {
        margin: 6px 0;
    }

    .message-bubble ul,
    .message-bubble ol {
        margin: 8px 0 8px 18px;
        padding: 0;
    }

    .message-bubble li {
        margin-bottom: 4px;
    }

    .message-bubble blockquote {
        margin: 10px 0;
        padding: 10px 14px;
        background: #fef3c7;
        border-left: 4px solid #f59e0b;
        border-radius: 8px;
        color: #78350f;
        font-size: 13.5px;
    }

    .message-bubble blockquote p {
        margin: 0;
    }

    .message-bubble .warn-icon-img {
        width: 18px;
        height: 18px;
        display: inline-block;
        margin-right: 6px;
        vertical-align: -3px;
        flex-shrink: 0;
    }

    .message-bubble code {
        background: #f1f5f9;
        padding: 2px 5px;
        border-radius: 5px;
        font-size: 12.5px;
    }

    .message-bubble table {
        width: 100%;
        border-collapse: collapse;
        margin: 10px 0;
        font-size: 13px;
    }

    .message-bubble th {
        background: #f0fdf4;
        text-align: left;
        font-weight: 600;
    }

    .message-bubble th,
    .message-bubble td {
        border: 1px solid #e5e7eb;
        padding: 8px 10px;
    }

    .message-bubble strong {
        color: #111827;
    }

    .message-bubble em {
        color: #4b5563;
    }

    /* Suggested products carousel */
    .ai-suggestion {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
        max-width: 720px;
        width: 100%;
        margin-top: -6px;
        margin-left: 44px;
    }

    .ai-suggestion-header {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.07em;
        color: #4b5563;
    }

    .suggestion-carousel {
        display: flex;
        gap: 12px;
        overflow-x: auto;
        width: 100%;
        padding: 4px 2px 8px;
        scroll-snap-type: x mandatory;
        scrollbar-width: none;
        -ms-overflow-style: none;
    }

    .suggestion-carousel::-webkit-scrollbar {
        display: none;
    }

    .suggestion-card {
        flex: 0 0 172px;
        width: 172px;
        scroll-snap-align: start;
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        overflow: hidden;
        text-decoration: none;
        color: inherit;
        display: flex;
        flex-direction: column;
        transition: box-shadow 0.2s ease, transform 0.2s ease;
    }

    .suggestion-card:hover {
        box-shadow: 0 6px 16px rgba(16, 24, 40, 0.12);
        transform: translateY(-2px);
    }

    .suggestion-card-img {
        width: 100%;
        height: 96px;
        background: #f3f4f6;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .suggestion-card-img img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .suggestion-card-body {
        padding: 10px 12px 12px;
        display: flex;
        flex-direction: column;
        gap: 4px;
        min-width: 0;
    }

    .suggestion-card-name {
        font-size: 13px;
        font-weight: 600;
        color: #111827;
        line-height: 1.35;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .suggestion-card-price {
        font-size: 14px;
        font-weight: 800;
        color: #16a34a;
    }

    .suggestion-card-store {
        font-size: 11px;
        color: #6b7280;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* Product info modal */
    .product-modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.15);
        display: none;
        align-items: center;
        justify-content: center;
        padding: 24px;
        z-index: 1000;
    }

    .product-modal-overlay.open {
        display: flex;
    }

    .product-modal {
        background: #ffffff;
        border-radius: 16px;
        width: 100%;
        max-width: 420px;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: 0 24px 60px rgba(16, 24, 40, 0.25);
        scrollbar-width: none;
        -ms-overflow-style: none;
    }

    .product-modal::-webkit-scrollbar {
        display: none;
        width: 0;
        height: 0;
    }

    .product-modal-head {
        position: relative;
        padding: 14px 20px;
        border-bottom: 1px solid #eef0f3;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .product-modal-title {
        font-size: 15px;
        font-weight: 700;
        color: #111827;
    }

    .product-modal-close {
        background: none;
        border: none;
        cursor: pointer;
        color: #6b7280;
        padding: 4px;
        display: inline-flex;
        border-radius: 6px;
    }

    .product-modal-close:hover {
        background: #f3f4f6;
        color: #111827;
    }

    .product-modal-close svg {
        width: 18px;
        height: 18px;
    }

    .product-modal-body {
        padding: 20px;
    }

    .product-modal-img {
        width: 100%;
        height: 180px;
        background: #f3f4f6;
        border-radius: 12px;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 14px;
    }

    .product-modal-img img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .product-modal-price-row {
        display: flex;
        align-items: baseline;
        gap: 8px;
        flex-wrap: wrap;
    }

    .product-modal-price {
        font-size: 22px;
        font-weight: 800;
        color: #16a34a;
    }

    .product-modal-price-unit {
        font-size: 13px;
        color: #6b7280;
    }

    .product-modal-name {
        font-size: 17px;
        font-weight: 700;
        color: #111827;
        margin: 8px 0 2px;
    }

    .product-modal-brand {
        font-size: 13px;
        color: #6b7280;
        margin-bottom: 10px;
    }

    .product-modal-meta {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px 14px;
        margin: 12px 0;
    }

    .product-modal-meta-item {
        background: #f8fafc;
        border-radius: 10px;
        padding: 8px 12px;
        min-width: 0;
    }

    .product-modal-meta-label {
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #94a3b8;
        font-weight: 700;
        margin-bottom: 2px;
    }

    .product-modal-meta-value {
        font-size: 13px;
        font-weight: 600;
        color: #1f2937;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .product-modal-desc {
        font-size: 13px;
        line-height: 1.6;
        color: #4b5563;
        margin: 10px 0 16px;
        display: -webkit-box;
        -webkit-line-clamp: 4;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .product-modal-actions {
        display: flex;
        gap: 10px;
    }

    .product-modal-btn {
        flex: 1;
        padding: 11px 12px;
        border-radius: 10px;
        border: none;
        cursor: pointer;
        font-size: 14px;
        font-weight: 700;
        font-family: 'DM Sans', sans-serif;
        text-align: center;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: background 0.2s ease;
    }

    .product-modal-btn-visit {
        background: #16a34a;
        color: #ffffff;
    }

    .product-modal-btn-visit:hover {
        background: #15803d;
    }

    .product-modal-btn-close {
        background: #f3f4f6;
        color: #374151;
    }

    .product-modal-btn-close:hover {
        background: #e5e7eb;
    }

    @media (max-width: 480px) {
        .product-modal-overlay {
            padding: 12px;
            align-items: flex-end;
        }
        .product-modal {
            border-radius: 16px 16px 0 0;
            max-height: 88vh;
        }
        .product-modal-img {
            height: 150px;
        }
        .product-modal-body {
            padding: 16px;
        }
        .product-modal-meta {
            grid-template-columns: 1fr;
        }
        .product-modal-actions {
            flex-direction: column-reverse;
        }
    }

    /* Chat history */
    .history-wrap {
        position: relative;
        margin-left: auto;
        flex-shrink: 0;
    }

    .history-btn {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        background: none;
        border: 1px solid #e5e7eb;
        color: #4b5563;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .history-btn:hover {
        border-color: #16a34a;
        color: #16a34a;
        background: #f0fdf4;
    }

    .history-btn svg {
        width: 18px;
        height: 18px;
    }

    .history-dropdown {
        position: absolute;
        top: 48px;
        right: 0;
        width: 320px;
        max-width: calc(100vw - 32px);
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        box-shadow: 0 12px 32px rgba(16, 24, 40, 0.14);
        display: none;
        flex-direction: column;
        overflow: hidden;
        z-index: 500;
    }

    .history-dropdown.open {
        display: flex;
    }

    .history-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 14px;
        border-bottom: 1px solid #eef0f3;
    }

    .history-head b {
        font-size: 13px;
        color: #111827;
    }

    .history-new-btn {
        font-size: 12px;
        font-weight: 700;
        color: #16a34a;
        background: none;
        border: none;
        cursor: pointer;
        font-family: 'DM Sans', sans-serif;
    }

    .history-new-btn:hover {
        color: #15803d;
    }

    .history-list {
        max-height: 320px;
        overflow-y: auto;
        scrollbar-width: none;
        -ms-overflow-style: none;
    }

    .history-list::-webkit-scrollbar {
        display: none;
    }

    .history-empty {
        padding: 28px 16px;
        text-align: center;
        font-size: 13px;
        color: #94a3b8;
    }

    .history-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 11px 14px;
        cursor: pointer;
        border-bottom: 1px solid #f5f6f8;
        transition: background 0.15s ease;
    }

    .history-item:hover {
        background: #f8fafc;
    }

    .history-item-title {
        flex: 1;
        min-width: 0;
        font-size: 13px;
        font-weight: 600;
        color: #1f2937;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .history-item-time {
        font-size: 11px;
        color: #9ca3af;
        white-space: nowrap;
    }

    .history-item-del {
        width: 26px;
        height: 26px;
        border: none;
        background: none;
        cursor: pointer;
        color: #cbd5e1;
        border-radius: 6px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .history-item-del:hover {
        background: #fee2e2;
        color: #dc2626;
    }

    .history-item-del svg {
        width: 14px;
        height: 14px;
    }

    /* Separate delete-confirmation modal */
    .delete-modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.15);
        display: none;
        align-items: center;
        justify-content: center;
        padding: 24px;
        z-index: 1100;
    }

    .delete-modal-overlay.open {
        display: flex;
    }

    .delete-modal {
        background: #ffffff;
        border-radius: 16px;
        max-width: 380px;
        width: 100%;
        box-shadow: 0 24px 60px rgba(16, 24, 40, 0.28);
        padding: 26px 22px 22px;
        text-align: center;
    }

    .delete-modal-icon {
        width: 52px;
        height: 52px;
        margin: 0 auto 14px;
        border-radius: 50%;
        background: #fef2f2;
        color: #dc2626;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .delete-modal-icon svg {
        width: 24px;
        height: 24px;
    }

    .delete-modal-title {
        font-size: 16px;
        font-weight: 700;
        color: #111827;
        margin-bottom: 8px;
    }

    .delete-modal-text {
        font-size: 13px;
        color: #6b7280;
        line-height: 1.55;
        margin-bottom: 20px;
        word-break: break-word;
    }

    .delete-modal-text b {
        color: #1f2937;
        font-weight: 700;
    }

    .delete-modal-actions {
        display: flex;
        gap: 10px;
    }

    .delete-modal-btn {
        flex: 1;
        padding: 11px 12px;
        border-radius: 10px;
        border: none;
        cursor: pointer;
        font-size: 14px;
        font-weight: 700;
        font-family: 'DM Sans', sans-serif;
        transition: background 0.2s ease;
    }

    .delete-modal-cancel {
        background: #f3f4f6;
        color: #374151;
    }

    .delete-modal-cancel:hover {
        background: #e5e7eb;
    }

    .delete-modal-delete {
        background: #dc2626;
        color: #ffffff;
    }

    .delete-modal-delete:hover {
        background: #b91c1c;
    }

    /* In-app toast */
    .app-toast {
        position: fixed;
        left: 50%;
        bottom: 28px;
        transform: translateX(-50%) translateY(20px);
        background: #111827;
        color: #ffffff;
        padding: 10px 18px;
        border-radius: 10px;
        font-size: 13px;
        font-weight: 600;
        opacity: 0;
        pointer-events: none;
        transition: all 0.25s ease;
        z-index: 1200;
        box-shadow: 0 8px 24px rgba(16, 24, 40, 0.22);
        white-space: nowrap;
    }

    .app-toast.show {
        opacity: 1;
        transform: translateX(-50%) translateY(0);
    }

    @media (max-width: 768px) {
        .main-content {
            padding: 88px 0 0;
        }
        .chat-page {
            height: calc(100vh - 88px - 64px);
        }
        .chat-messages {
            padding: 16px;
        }
        .chat-header {
            padding: 12px 16px;
        }
        .chat-input-area {
            padding: 12px 16px;
        }
    }
</style>

<div class="breadcrumb">
    <a href="{{ route('store.quotations.index') }}">Quotation Demand Trends</a>
    <span>/</span>
    AI Assistant
</div>

<div class="chat-page">
    <div class="chat-container">

        <div class="chat-header">
            <div class="ai-avatar">
                <img src="{{ asset('images/ai-avatar.png') }}" alt="AI Assistant">
            </div>
            <div class="header-info">
                <div class="header-title">AI Assistant</div>
                <div class="header-status">
                    <div class="status-dot"></div>
                    Online
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
                <div class="message-avatar">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 00-2.455 2.456z" />
                    </svg>
                </div>
                <div class="message-bubble">
                    <div>
                        Hello! I'm your AI assistant for Aquilala stores. I can help you with:
                    </div>
                    <ul>
                        <li>Reading farmer quotations to see what they need</li>
                        <li>Spotting product and crop trends in your area</li>
                        <li>Deciding what to stock or restock</li>
                        <li>Pricing and quoting guidance</li>
                    </ul>
                    <div style="margin-top: 10px;">
                        Ask me anything about your store!
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
                        <button class="chip" onclick="sendChip(this)">
                            Show me my quotation trends
                        </button>
                        <button class="chip" onclick="sendChip(this)">
                            Which products should I restock?
                        </button>
                        <button class="chip" onclick="sendChip(this)">
                            What's trending this week?
                        </button>
                        <button class="chip" onclick="sendChip(this)">
                            Which crops are in demand?
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
                    placeholder="Ask about quotations, crops, products to stock, or pricing..."
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
                <a class="product-modal-btn product-modal-btn-visit" id="pmVisit" href="#">Open Inventory</a>
            </div>
        </div>
    </div>

    <div class="app-toast" id="appToast"></div>
</div>

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

    function getAiAvatarSvg() {
        return '<img src="{{ asset('images/ai-avatar.png') }}" alt="AI">';
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
            '<div class="message-avatar">' + getAiAvatarSvg() + '</div>' +
            '<div class="message-bubble"><div>' + renderMarkdown(content) + '</div></div>';
        chatMessages.appendChild(row);
        scrollToBottom();
        return row;
    }

    function addUserMessage(text) {
        const row = document.createElement('div');
        row.className = 'message-row user';
        row.innerHTML =
            '<div class="message-avatar" style="background:#374151;">' +
            '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" /></svg>' +
            '</div>' +
            '<div class="message-bubble" style="background:#f3f4f6;border-color:#e5e7eb;"><div>' + escapeHtml(text).replace(/\n/g, '<br>') + '</div></div>';
        chatMessages.appendChild(row);
        scrollToBottom();
        return row;
    }

    function addLoadingMessage() {
        const row = document.createElement('div');
        row.className = 'message-row';
        row.id = 'loadingMsg';
        row.innerHTML =
            '<div class="message-avatar">' + getAiAvatarSvg() + '</div>' +
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
            const resp = await fetch('{{ route("store.ai-chat.send") }}', {
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
        sendMessage(text);
    }

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
            cards +=
                '<a class="suggestion-card" href="' + s.url + '" data-product=\'' + JSON.stringify(s).replace(/'/g, '&#39;') + '\'>' +
                '<div class="suggestion-card-img"><img src="' + s.image + '" alt="' + escapeHtml(s.name) + '" loading="lazy"></div>' +
                '<div class="suggestion-card-body">' +
                '<div class="suggestion-card-name">' + escapeHtml(s.name) + '</div>' +
                '<div class="suggestion-card-price">₱' + s.price + ' / ' + escapeHtml(s.price_unit) + '</div>' +
                (s.store ? '<div class="suggestion-card-store">' + escapeHtml(s.store) + '</div>' : '') +
                '</div></a>';
        });
        row.innerHTML =
            '<div class="ai-suggestion-header">Recommended from your inventory</div>' +
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
        fetch('{{ route("store.ai-chat.suggestions") }}', {
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
        fetch('{{ route("store.ai-chat.history.save") }}', {
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
        fetch('{{ route("store.ai-chat.history.show", "__ID__") }}'.replace('__ID__', id), {
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
        fetch('{{ route("store.ai-chat.history.destroy", "__ID__") }}'.replace('__ID__', id), {
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
        fetch('{{ route("store.ai-chat.histories") }}', {
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

    // ─── Seed a quotation trends overview handed off from Quotation Demand Trends ───
    (function restoreSeededOverview() {
        let seed = null;
        try { seed = sessionStorage.getItem('aiSeedOverview'); } catch (e) {}
        if (!seed) return;
        try { sessionStorage.removeItem('aiSeedOverview'); } catch (e) {}

        const prompt = 'Show me my quotation trends with product recommendations.';
        addUserMessage(prompt);
        chatHistory.push({ role: 'user', content: prompt });

        const row = addMessage('assistant', seed);
        chatHistory.push({ role: 'assistant', content: seed });

        const keywords = extractSuggestKeywords(seed);
        if (keywords.length) {
            fetchSuggestionsFor(keywords, row);
        }
        persistCurrentConversation();
    })();

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            historyDropdown.classList.remove('open');
        }
    });
</script>
@endsection
