<x-layouts.guest title="Masuk">
    <h2 class="text-lg font-bold text-text">Selamat datang kembali</h2>
    <p class="mt-1 mb-6 text-sm text-muted">Masuk untuk mengelola keuangan Anda.</p>

    @if ($errors->any())
        <div class="mb-4 rounded-2xl bg-danger/10 p-4 text-sm text-danger">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <div>
            <x-neo-input label="Email" type="email" name="email" placeholder="nama@email.com" required autofocus autocomplete="email" />
        </div>

        <div>
            <x-neo-input label="Kata Sandi" type="password" name="password" placeholder="••••••••" required autocomplete="current-password" />
        </div>

        <div class="flex items-center justify-between">
            <label class="flex items-center gap-2 text-sm text-muted">
                <input type="checkbox" name="remember" class="h-4 w-4 rounded border-shadow-dark accent-[var(--primary)]">
                Ingat saya
            </label>
            <a href="{{ route('password.request') }}" class="text-sm font-medium text-primary transition-colors hover:text-primary-dark">
                Lupa kata sandi?
            </a>
        </div>

        <x-neo-button variant="primary" class="w-full">
            Masuk
        </x-neo-button>
    </form>

    <p class="mt-6 text-center text-sm text-muted">
        Belum punya akun?
        <a href="{{ route('register') }}" class="font-semibold text-primary transition-colors hover:text-primary-dark">Daftar gratis</a>
    </p>
</x-layouts.guest>