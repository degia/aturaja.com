<x-layouts.guest title="Konfirmasi Kata Sandi">
    <h2 class="text-lg font-bold text-text">Konfirmasi kata sandi</h2>
    <p class="mt-1 mb-6 text-sm text-muted">Demi keamanan, konfirmasikan kata sandi Anda untuk melanjutkan.</p>

    @if ($errors->any())
        <div class="mb-4 rounded-2xl bg-danger/10 p-4 text-sm text-danger">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.confirm') }}" class="space-y-5">
        @csrf

        <div>
            <x-neo-input label="Kata Sandi" type="password" name="password" placeholder="••••••••" required autocomplete="current-password" />
        </div>

        <x-neo-button variant="primary" class="w-full">
            Konfirmasi
        </x-neo-button>
    </form>
</x-layouts.guest>