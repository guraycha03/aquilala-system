@extends('layouts.auth-layout')

@section('title', 'Register')

@section('form-content')
    <h1 style="margin: 0 0 6px 0; font-size: 24px; font-weight: 700; color: #232f27; letter-spacing: -0.025em;">Create account</h1>
    <p style="margin: 0 0 24px 0; color: #606d64; font-size: 14px;">Get started by creating your account profile.</p>

    @if ($errors->any())
        <div style="background: #fdf2f2; color: #9b2c2c; border: 1px solid #fde8e8; padding: 14px; border-radius: 8px; margin-bottom: 20px; font-size: 14px;">
            <ul style="margin: 0; padding-left: 20px; line-height: 1.5;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('register.post') }}">
        @csrf
        
        <input type="hidden" name="role" value="farmer">

        <div style="display: grid; grid-template-columns: 2fr 1fr 2fr; gap: 12px; margin-bottom: 16px;">
            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #3d4a41;">First Name</label>
                <input type="text" name="first_name" value="{{ old('first_name') }}" required placeholder="Jane"
                       style="width: 100%; padding: 11px 14px; border: 1px solid #e2e8f0; border-radius: 8px; box-sizing: border-box; font-size: 15px; background: #fafbfa;" />
            </div>
            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #3d4a41;">M.I.</label>
                <input type="text" name="middle_initial" value="{{ old('middle_initial') }}" maxlength="2" placeholder="D."
                       style="width: 100%; padding: 11px 14px; border: 1px solid #e2e8f0; border-radius: 8px; box-sizing: border-box; font-size: 15px; background: #fafbfa; text-align: center;" />
            </div>
            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #3d4a41;">Last Name</label>
                <input type="text" name="last_name" value="{{ old('last_name') }}" required placeholder="Doe"
                       style="width: 100%; padding: 11px 14px; border: 1px solid #e2e8f0; border-radius: 8px; box-sizing: border-box; font-size: 15px; background: #fafbfa;" />
            </div>
        </div>

        <div style="margin-bottom: 16px;">
            <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #3d4a41;">Email Address</label>
            <input type="email" name="email" value="{{ old('email') }}" required autocomplete="email" placeholder="name@gmail.com"
                   style="width: 100%; padding: 11px 14px; border: 1px solid #e2e8f0; border-radius: 8px; box-sizing: border-box; font-size: 15px; background: #fafbfa;" />
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px;">
            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #3d4a41;">Password</label>
                <div style="position: relative;">
                    <input type="password" id="password" name="password" required minlength="8" placeholder="••••••••"
                           style="width: 100%; padding: 11px 44px 11px 14px; border: 1px solid #e2e8f0; border-radius: 8px; box-sizing: border-box; font-size: 15px; background: #fafbfa;" />
                    
                    <button type="button" onclick="toggleVisibility('password')" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; padding: 4px; display: flex; align-items: center; justify-content: center; color: #606d64;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path id="password-eye-open" d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0z"></path>
                            <circle id="password-eye-pupil" cx="12" cy="12" r="3"></circle>
                            <line id="password-eye-slash" x1="3" y1="3" x2="21" y2="21" stroke="currentColor" stroke-width="2" style="display: none;"></line>
                        </svg>
                    </button>
                </div>
            </div>
            
            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #3d4a41;">Confirm Password</label>
                <div style="position: relative;">
                    <input type="password" id="password_confirmation" name="password_confirmation" required minlength="8" placeholder="••••••••"
                           style="width: 100%; padding: 11px 44px 11px 14px; border: 1px solid #e2e8f0; border-radius: 8px; box-sizing: border-box; font-size: 15px; background: #fafbfa;" />
                    
                    <button type="button" onclick="toggleVisibility('password_confirmation')" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; padding: 4px; display: flex; align-items: center; justify-content: center; color: #606d64;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path id="password_confirmation-eye-open" d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0z"></path>
                            <circle id="password_confirmation-eye-pupil" cx="12" cy="12" r="3"></circle>
                            <line id="password_confirmation-eye-slash" x1="3" y1="3" x2="21" y2="21" stroke="currentColor" stroke-width="2" style="display: none;"></line>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <button type="submit" style="width: 100%; background: #436149; color: #ffffff; border: none; padding: 12px 14px; border-radius: 8px; font-size: 15px; font-weight: 600; cursor: pointer; transition: background 0.2s; box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);">
            Register
        </button>

        <p style="margin: 20px 0 0 0; color: #606d64; font-size: 14px; text-align: center;">
            Already have an account? 
            <a href="{{ route('login') }}" style="color: #436149; font-weight: 700; text-decoration: none;">Login</a>
        </p>
    </form>
@endsection

@section('scripts')
    <script>
        function toggleVisibility(fieldId) {
            const field = document.getElementById(fieldId);
            const eyeSlash = document.getElementById(fieldId + '-eye-slash');
            const eyePupil = document.getElementById(fieldId + '-eye-pupil');
            
            if (field.type === "password") {
                field.type = "text";
                eyeSlash.style.display = "block";
                eyePupil.style.display = "none";
            } else {
                field.type = "password";
                eyeSlash.style.display = "none";
                eyePupil.style.display = "block";
            }
        }
    </script>
@endsection