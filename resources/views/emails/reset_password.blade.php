<x-mail::message>
{{-- Logo --}}
<div style="text-align: center; margin-bottom: 20px;">
    <h1 style="color: #22c55e; font-size: 32px; font-weight: bold; margin: 0;">WellNezt</h1>
    <p style="color: #666; margin: 5px 0 0 0;">Your Health Journey Partner</p>
</div>

Halo!

Kamu menerima email ini karena kami menerima permintaan reset password untuk akun kamu.

Klik tombol di bawah untuk reset password:

<x-mail::button :url="config('app.frontend_url') . '/reset-password?token=' . $token . '&email=' . urlencode($email)" color="success">
Reset Password
</x-mail::button>

**Link ini akan kadaluarsa dalam 15 menit.**

Jika kamu tidak meminta reset password, abaikan email ini. Password kamu tidak akan berubah.

Thanks,<br>
**Tigagit** 💚

---

<div style="text-align: center; color: #999; font-size: 12px; margin-top: 20px;">
    <p>© {{ date('Y') }} WellNezt. All rights reserved.</p>
    <p>Email ini dikirim otomatis, mohon tidak membalas.</p>
</div>

</x-mail::message>