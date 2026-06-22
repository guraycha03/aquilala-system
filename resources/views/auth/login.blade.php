<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login | Aquilala</title>
</head>
<body style="font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; background: #f3f6f4; padding: 40px 24px; color: #2c3530; display: flex; align-items: center; min-height: 85vh;">
    <div style="width: 100%; max-width: 420px; margin: 0 auto; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 32px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.03), 0 2px 4px -1px rgba(0, 0, 0, 0.02);">
        
        <h1 style="margin: 0 0 6px 0; font-size: 24px; font-weight: 700; color: #232f27; letter-spacing: -0.025em;">Sign in</h1>
        <p style="margin: 0 0 24px 0; color: #606d64; font-size: 14px;">Welcome back! Please enter your details.</p>

        @if ($errors->any())
            <div style="background: #fdf2f2; color: #9b2c2c; border: 1px solid #fde8e8; padding: 14px; border-radius: 8px; margin-bottom: 20px; font-size: 14px;">
                <ul style="margin: 0; padding-left: 20px; line-height: 1.5;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('login.post') }}">
            @csrf

            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #3d4a41;">Email Address</label>
                <input type="email" name="email" value="{{ old('email') }}" required autocomplete="email" placeholder="name@gmail.com"
                       style="width: 100%; padding: 11px 14px; border: 1px solid #e2e8f0; border-radius: 8px; box-sizing: border-box; font-size: 15px; background: #fafbfa; transition: border-color 0.2s;" />
            </div>

            <div style="margin-bottom: 18px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #3d4a41;">Password</label>
                <div style="position: relative;">
                    <input type="password" id="login_password" name="password" required placeholder="••••••••"
                           style="width: 100%; padding: 11px 40px 11px 14px; border: 1px solid #e2e8f0; border-radius: 8px; box-sizing: border-box; font-size: 15px; background: #fafbfa;" />
                    <span onclick="toggleVisibility('login_password', this)" style="position: absolute; right: 14px; top: 50%; transform: translateY(-50%); cursor: pointer; font-size: 13px; font-weight: 600; color: #606d64; user-select: none;">Show</span>
                </div>
            </div>

            <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 24px;">
                <label style="font-size: 14px; color: #4a574e; display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <input type="checkbox" name="remember" value="1" style="accent-color: #436149; width: 15px; height: 15px;"> Remember me
                </label>
                <a href="#" style="font-size: 13px; font-weight: 600; color: #436149; text-decoration: none;">Forgot password?</a>
            </div>

            <button type="submit" style="width: 100%; background: #436149; color: #ffffff; border: none; padding: 12px 14px; border-radius: 8px; font-size: 15px; font-weight: 600; cursor: pointer; transition: background 0.2s; box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);">
                Log in
            </button>

            <p style="margin: 20px 0 0 0; color: #606d64; font-size: 14px; text-align: center;">
                Don't have an account? 
                <a href="{{ route('register') }}" style="color: #436149; font-weight: 700; text-decoration: none;">Register</a>
            </p>
        </form>
    </div>

    <script>
        function toggleVisibility(fieldId, element) {
            const field = document.getElementById(fieldId);
            if (field.type === "password") {
                field.type = "text";
                element.textContent = "Hide";
            } else {
                field.type = "password";
                element.textContent = "Show";
            }
        }
    </script>
</body>
</html>