<x-layouts.guest title="Reset Kata Sandi">
    <h2 class="text-lg font-bold text-text">Buat kata sandi baru</h2>
    <p class="mt-1 mb-6 text-sm text-muted">Masukkan kata sandi baru untuk akun Anda.</p>

    @if ($errors->any())
        <div class="mb-4 rounded-2xl bg-danger/10 p-4 text-sm text-danger">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.update') }}" class="space-y-5">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div>
            <x-neo-input label="Email" type="email" name="email" value="{{ old('email', $request->email) }}" placeholder="nama@email.com" required autocomplete="email" />
        </div>

        <div>
            <x-neo-input label="Kata Sandi Baru" type="password" name="password" placeholder="Minimal 8 karakter" required autocomplete="new-password" />
        </div>

        <div>
            <x-neo-input label="Konfirmasi Kata Sandi" type="password" name="password_confirmation" placeholder="Ulangi kata sandi" required autocomplete="new-password" />
        </div>

        <x-neo-button variant="primary" class="w-full">
            Simpan kata sandi
        </x-neo-button>
    </form>
</x-layouts.guest>