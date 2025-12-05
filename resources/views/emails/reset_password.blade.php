<x-mail::message>
{{-- Bagian Logo (Tengah) --}}
<div style="text-align: center; margin-bottom: 20px;">
    <img src="https://www.google.com/url?sa=i&url=https%3A%2F%2Fpikbest.com%2Fso%2Fgaming-logo.html&psig=AOvVaw2gwuvpIiZsZyKeuoB99Ku5&ust=1764990791139000&source=images&cd=vfe&opi=89978449&ved=0CBUQjRxqFwoTCMjkv5m9pZEDFQAAAAAdAAAAABAE" alt="NutriGo Team" width="100">
</div>

# Reset Password

Use the **'Reset Password'** button below to reset your password.

<x-mail::button :url="'http://localhost:3000/reset-password?token=' . $token">
Reset Password
</x-mail::button>

If you did not request a password reset, no further action is required.

Thanks,<br>
NutriGo Team
</x-mail::message>