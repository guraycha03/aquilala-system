@extends('layouts.auth-layout')

@section('title', 'Login')

@section('form-content')
    <h1 style="margin: 0 0 6px 0; font-size: 24px; font-weight: 700; color: #232f27; letter-spacing: -0.025em;">Welcome back</h1>
    <p style="margin: 0 0 24px 0; color: #606d64; font-size: 14px;">Sign in to access your dashboard workspace.</p>

    <form method="POST" action="{{ route('login.post') }}">
        @csrf
        <div style="margin-bottom: 16px;">
            <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #3d4a41;">Email Address</label>
            <input type="email" name="email" required placeholder="name@gmail.com"
                   style="width: 100%; padding: 11px 14px; border: 1px solid #e2e8f0; border-radius: 8px; box-sizing: border-box; font-size: 15px; background: #fafbfa;" />
        </div>

        <div style="margin-bottom: 24px;">
            <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #3d4a41;">Password</label>
            <!-- Relative container to place the icon properly inside the input element field -->
            <div style="position: relative;">
                <input type="password" id="password" name="password" required placeholder="••••••••"
                       style="width: 100%; padding: 11px 44px 11px 14px; border: 1px solid #e2e8f0; border-radius: 8px; box-sizing: border-box; font-size: 15px; background: #fafbfa;" />
                
                <button type="button" onclick="togglePasswordVisibility()" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; padding: 4px; display: flex; align-items: center; justify-content: center; color: #606d64;">
                    <!-- SVG Eye Wrapper -->
                    <svg id="eye-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <!-- Open Eye Path (Default) -->
                        <path id="eye-open" d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0z"></path>
                        <circle id="eye-pupil" cx="12" cy="12" r="3"></circle>
                        <!-- Hidden Slash Path (Toggled via JS) -->
                        <line id="eye-slash" x1="3" y1="3" x2="21" y2="21" stroke="currentColor" stroke-width="2" style="display: none;"></line>
                    </svg>
                </button>
            </div>
        </div>

        <button type="submit" style="width: 100%; background: #436149; color: #ffffff; border: none; padding: 12px 14px; border-radius: 8px; font-size: 15px; font-weight: 600; cursor: pointer; transition: background 0.2s; box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);">
            Log In
        </button>

        <p style="margin: 20px 0 0 0; color: #606d64; font-size: 14px; text-align: center;">
            Don't have an account yet? 
            <a href="{{ route('register') }}" style="color: #436149; font-weight: 700; text-decoration: none;">Register</a>
        </p>
    </form>
@endsection

@section('scripts')
<script>
    function togglePasswordVisibility() {
        const passwordInput = document.getElementById('password');
        const eyeSlash = document.getElementById('eye-slash');
        const eyePupil = document.getElementById('eye-pupil');

        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            eyeSlash.style.display = 'block'; // Shows the slash mark across the eye
            eyePupil.style.display = 'none';  // Hides pupil loop
        } else {
            passwordInput.type = 'password';
            eyeSlash.style.display = 'none';  // Removes slash line
            eyePupil.style.display = 'block'; // Restores regular view
        }
    }
</script>
@endsection