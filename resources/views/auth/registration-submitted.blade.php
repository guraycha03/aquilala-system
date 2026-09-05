@extends('layouts.auth-layout')

@section('title', 'Registration Submitted')

@section('form-content')
    <style>
        .success-icon {
            width: 64px; height: 64px; margin: 0 auto 18px; border-radius: 50%;
            background: #eef3ef; color: #3f7a4f; display: flex; align-items: center; justify-content: center;
        }
    </style>

    <div style="text-align: center; padding: 12px 4px;">
        <div class="success-icon">
            <svg viewBox="0 0 24 24" width="30" height="30" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
        </div>

        <h1 style="margin: 0 0 8px; font-size: 22px; font-weight: 700; color: #232f27; letter-spacing: -0.025em;">
            Registration Request Submitted
        </h1>
        <p style="margin: 0 0 22px; color: #606d64; font-size: 14px; line-height: 1.6;">
            Thank you! Your membership application has been received and is now
            <strong style="color: #143d23;">pending review</strong> by the Aquilala Irrigators Association administrator.
        </p>

        <div style="background: #f6f8f7; border: 1px solid #e2e8f0; border-left: 3px solid #5DC257; border-radius: 10px; padding: 16px 18px; margin-bottom: 22px; text-align: left;">
            <div style="font-size: 13px; color: #334155; line-height: 1.7;">
                <p style="margin: 0 0 10px;">
                    <strong style="color: #232f27;">What happens next?</strong>
                </p>
                <p style="margin: 0 0 10px;">
                    1. The administrator reviews the details you provided in your registration request.
                </p>
                <p style="margin: 0 0 10px;">
                    2. Once approved, a <strong>secure link</strong> will be emailed to you to set your own password.
                </p>
                <p style="margin: 0;">
                    3. After setting your password, you can sign in with your email and password.
                </p>
            </div>
        </div>

        <p style="margin: 0 0 24px; color: #94a3b8; font-size: 13px;">
            No password was created during registration for your security. Only you can set it once your membership is approved.
        </p>

        <a href="{{ route('login') }}" style="display: inline-block; background: #436149; color: #ffffff; text-decoration: none; padding: 12px 32px; border-radius: 8px; font-size: 14px; font-weight: 600;">
            Back to Login
        </a>
    </div>
@endsection
