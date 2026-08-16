@extends('layouts.sidebar')

@section('title', 'Notifications')

@section('content')
<style>
    .page-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 32px;
        flex-wrap: wrap;
        gap: 16px;
    }
    .page-title {
        font-size: 26px;
        font-weight: 800;
        color: #0f2918;
        letter-spacing: -0.02em;
        margin: 0;
    }
    .page-subtitle {
        color: #5c6e62;
        font-size: 14px;
        margin-top: 6px;
        line-height: 1.5;
    }

    .empty-card {
        background: #ffffff;
        border: 2px dashed #cbd5e1;
        border-radius: 18px;
        padding: 80px 24px;
        text-align: center;
        max-width: 640px;
        margin: 0 auto;
        box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.03);
    }
    .empty-icon-wrapper {
        width: 80px;
        height: 80px;
        background: #f0f7f2;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 24px auto;
        color: #2e7d32;
        box-shadow: inset 0 0 0 1px rgba(46, 125, 50, 0.15);
    }
    .empty-title {
        font-size: 18px;
        font-weight: 700;
        color: #0f2918;
        margin: 0 0 10px 0;
    }
    .empty-description {
        color: #64748b;
        font-size: 14px;
        line-height: 1.6;
        max-width: 420px;
        margin: 0 auto;
    }
</style>

<div class="page-header">
    <div>
        <h1 class="page-title">Notifications</h1>
        <p class="page-subtitle">Stay updated on your quotations, store dispatches, and platform activity.</p>
    </div>
</div>

<div style="padding: 32px 0;">
    <div class="empty-card">
        <div class="empty-icon-wrapper">
            <svg width="38" height="38" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
            </svg>
        </div>
        <h3 class="empty-title">No Notifications Yet</h3>
        <p class="empty-description">
            There's nothing here right now. Updates on your quotation requests, store dispatches, and other platform activity will appear here.
        </p>
    </div>
</div>
@endsection
