@extends('layouts.app')

@section('title', 'TempChat')

@section('content')
    <section id="home-app" class="flex min-h-screen items-center justify-center px-4">
        <div class="w-full max-w-md text-center">
            <p class="font-display text-xs tracking-[0.3em] uppercase text-secondary">nameless · live · 24h</p>
            <h1 class="mt-3 font-display text-5xl font-medium tracking-tight text-primary">TempChat</h1>
            <p class="mt-4 text-secondary">
                Room chat sementara tanpa login. Buat room, bagikan link, dan ngobrol maksimal 24 jam.
            </p>

            <form id="create-room-form" method="POST" action="{{ route('rooms.store') }}" class="mt-10 space-y-5 text-left">
                @csrf

                <div>
                    <label for="name" class="font-display text-xs text-secondary">Nama Anda</label>
                    <input id="name" name="name" type="text" maxlength="30" placeholder="mis. Rafif"
                        value="{{ old('name', $name) }}" autofocus required autocomplete="off"
                        class="field mt-2">

                    @error('name')
                        <p class="mt-2 text-xs text-danger">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit"
                    class="w-full rounded-md bg-tertiary px-5 py-3 font-display text-on-tertiary transition-opacity hover:opacity-90 focus:outline-none focus-visible:ring-2 focus-visible:ring-tertiary focus-visible:ring-offset-2 focus-visible:ring-offset-neutral">
                    Create Room
                </button>
            </form>

            <p class="mt-6 font-display text-xs text-secondary">
                Room otomatis terhapus <span class="text-tertiary">24 jam</span> setelah dibuat.
            </p>
        </div>
    </section>
@endsection