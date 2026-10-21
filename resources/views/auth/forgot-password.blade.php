@extends('layouts.auth-layout')

@section('title', 'Forgot Password')

@section('form-content')
    <h1 style="margin: 0 0 6px 0; font-size: 24px; font-weight: 700; color: #232f27; letter-spacing: 0.03em;">Forgot your password?</h1>
    <p style="margin: 0 0 20px 0; color: #606d64; font-size: 14px; letter-spacing: 0.03em;">
        No problem. Enter the email address on your account and we'll email you a 6-digit verification code to reset your password.
    </p>

    @if (session('success'))
        <div style="background: #edf1e9; color: #648268; border: 1px solid #bbf7d0; padding: 14px; border-radius: 8px; margin-bottom: 20px; font-size: 14px;">
            {{ session('success') }}
        </div>
    @endif

    @if (session('info'))
        <div style="background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; padding: 14px; border-radius: 8px; margin-bottom: 20px; font-size: 14px;">
            {{ session('info') }}
        </div>
    @endif

    @if ($errors->any())
        <div style="background: #fdf2f2; color: #9b2c2c; border: 1px solid #fde8e8; padding: 14px; border-radius: 8px; margin-bottom: 20px; font-size: 14px;">
            <ul style="margin: 0; padding-left: 20px; line-height: 1.5;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" novalidate>
        @csrf

        <div style="margin-bottom: 24px;">
            <label for="fp-email" style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #3d4a41; letter-spacing: 0.05em;">
                Email Address
            </label>
            <input type="email" id="fp-email" name="email" value="{{ old('email') }}" required placeholder="name@gmail.com"
                   autocomplete="email"
                   style="width: 100%; padding: 11px 14px; border: 1px solid #e2e8f0; border-radius: 8px; box-sizing: border-box; font-size: 15px; background: #fafbfa; transition: border-color 0.2s, box-shadow 0.2s; outline: none;" />
        </div>

        <button type="submit" style="width: 100%; background: #4A7A54; color: #ffffff; border: none; padding: 12px 14px; border-radius: 8px; font-size: 15px; font-weight: 600; letter-spacing: 0.08em; cursor: pointer; box-shadow: 0 4px 12px rgba(22, 163, 74, 0.25); transition: background 0.2s, transform 0.2s;">
            Send Verification Code
        </button>
    </form>

    <p style="margin: 22px 0 0 0; color: #606d64; font-size: 13px; text-align: center; letter-spacing: 0.03em;">
        Already received a code?
        <a href="{{ route('password.verify') }}" style="color: #436149; font-weight: 700; text-decoration: none;">Enter it here</a>
    </p>

    <p style="margin: 12px 0 0 0; color: #606d64; font-size: 13px; text-align: center; letter-spacing: 0.03em;">
        Remembered it?
        <a href="{{ route('login') }}" style="color: #436149; font-weight: 700; text-decoration: none;">Back to log in</a>
    </p>
@endsection