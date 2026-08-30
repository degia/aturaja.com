<x-layouts.guest title="Daftar">
    <h2 class="text-lg font-bold text-text">Buat akun Anda</h2>
    <p class="mt-1 mb-6 text-sm text-muted">Workspace pertama akan dibuat otomatis saat Anda daftar.</p>

    @if ($errors->any())
        <div class="mb-4 rounded-2xl bg-danger/10 p-4 text-sm text-danger">
            <ul class="list-inside list-disc space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('register') }}" class="space-y-5">
        @csrf

        <div>
            <x-neo-input label="Nama Lengkap" type="text" name="name" value="{{ old('name') }}" placeholder="Nama Anda" required autofocus autocomplete="name" />
        </div>

        <div>
            <x-neo-input label="Email" type="email" name="email" value="{{ old('email') }}" placeholder="nama@email.com" required autocomplete="email" />
        </div>

        <div>
            <x-neo-input label="Kata Sandi" type="password" name="password" placeholder="Minimal 8 karakter" required autocomplete="new-password" />
        </div>

        <div>
            <x-neo-input label="Konfirmasi Kata Sandi" type="password" name="password_confirmation" placeholder="Ulangi kata sandi" required autocomplete="new-password" />
        </div>

        <x-neo-button variant="primary" class="w-full">
            Daftar & buat workspace
        </x-neo-button>
    </form>

    <p class="mt-6 text-center text-sm text-muted">
        Sudah punya akun?
        <a href="{{ route('login') }}" class="font-semibold text-primary transition-colors hover:text-primary-dark">Masuk</a>
    </p>
</x-layouts.guest>