@extends('layouts.sidebar')

@section('title', 'My Crops')

@section('content')
<style>
    /* View-specific modern CSS variables and styling */
    .page-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 24px;
        flex-wrap: wrap;
        gap: 16px;
    }
    .page-title {
        font-size: 26px;
        font-weight: 800;
        color: #0f2918;
        letter-spacing: -0.02em;
    }
    .page-subtitle {
        color: #5c6e62;
        font-size: 14px;
        margin-top: 4px;
    }

    .btn-primary {
        background: #143d23;
        color: #ffffff;
        border: none;
        padding: 10px 20px;
        font-size: 14px;
        font-weight: 600;
        border-radius: 10px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        box-shadow: 0 4px 12px rgba(20, 61, 35, 0.15);
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        text-decoration: none;
    }
    .btn-primary:hover {
        background: #1b4d2e;
        transform: translateY(-1px);
        box-shadow: 0 6px 16px rgba(20, 61, 35, 0.22);
    }
    .btn-primary:active {
        transform: translateY(0);
    }

    /* Stats Overview Header (When empty or active) */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 16px;
        margin-bottom: 24px;
    }
    .stat-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 18px 20px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
    }
    .stat-label {
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #718096;
    }
    .stat-value {
        font-size: 24px;
        font-weight: 800;
        color: #1a202c;
        margin-top: 6px;
    }

    /* Redesigned Empty State Card */
    .empty-card {
        background: #ffffff;
        border: 1px dashed #cbd5e1;
        border-radius: 18px;
        padding: 64px 24px;
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
        margin: 0 auto 20px auto;
        color: #2e7d32;
        box-shadow: inset 0 0 0 1px rgba(46, 125, 50, 0.15);
    }
    .empty-title {
        font-size: 18px;
        font-weight: 700;
        color: #0f2918;
        margin-bottom: 8px;
    }
    .empty-description {
        color: #64748b;
        font-size: 14px;
        line-height: 1.5;
        max-width: 420px;
        margin: 0 auto 24px auto;
    }

    /* Hints / Quick Guides */
    .quick-tips {
        margin-top: 32px;
        padding-top: 24px;
        border-top: 1px solid #f1f5f9;
        display: flex;
        justify-content: center;
        gap: 24px;
        font-size: 13px;
        color: #64748b;
    }
    .tip-item {
        display: flex;
        align-items: center;
        gap: 6px;
    }
</style>

<!-- Header Section -->
<div class="page-header">
    <div>
        <h1 class="page-title">My Crops</h1>
        <p class="page-subtitle">Track active farm yields, crop management cycles, and seasonal harvests.</p>
    </div>
    <a href="#" class="btn-primary">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
        Add Crop Batch
    </a>
</div>

<!-- Key Stat Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label">Active Crop Batches</div>
        <div class="stat-value">0</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Total Land Area</div>
        <div class="stat-value">0.0 <span style="font-size: 14px; font-weight: 500; color: #64748b;">ha</span></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Estimated Harvest</div>
        <div class="stat-value">--</div>
    </div>
</div>

<!-- Main Section: Empty State Container -->
<div style="padding: 20px 0;">
    <div class="empty-card">
        <div class="empty-icon-wrapper">
            <svg width="38" height="38" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 2a10 10 0 0 1 10 10c0 5.25-4.2 10-10 10S2 17.25 2 12A10 10 0 0 1 12 2z"/>
                <path d="M12 18V6"/>
                <path d="M12 10c-2.5 0-5 1.5-5 4"/>
                <path d="M12 8c2.5 0 5 1.5 5 4"/>
            </svg>
        </div>
        <h3 class="empty-title">No Active Crop Batches</h3>
        <p class="empty-description">
            You haven't logged any crop inventory or planting details for this season cycle yet. Start by logging your first batch.
        </p>
        
        <button class="btn-primary" style="margin: 0 auto;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            Add First Crop Batch
        </button>

        <div class="quick-tips">
            <span class="tip-item">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#2e7d32" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                Track Growth Stages
            </span>
            <span class="tip-item">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#2e7d32" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                Calculate Input Costs
            </span>
            <span class="tip-item">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#2e7d32" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                Estimate Yields
            </span>
        </div>
    </div>
</div>
@endsection