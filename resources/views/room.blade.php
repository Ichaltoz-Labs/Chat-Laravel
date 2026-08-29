@extends('layouts.app')

@section('title', $room->code)

@section('content')
    @unless ($user)
        <section class="flex min-h-screen items-center justify-center px-4">
            <div class="w-full max-w-sm text-center">
                <p class="font-display text-xs tracking-[0.3em] uppercase text-secondary">join room</p>
                <h1 class="mt-3 font-display text-3xl font-medium tracking-tight text-primary">{{ $room->code }}</h1>
                <p class="mt-3 text-secondary">Masukkan nama Anda untuk bergabung ke room ini.</p>

                <form method="POST" action="{{ route('room.join', $room->code) }}"
                    class="mt-8 space-y-5 text-left">
                    @csrf

                    <div>
                        <label for="join-name" class="font-display text-xs text-secondary">Nama Anda</label>
                        <input id="join-name" name="name" type="text" maxlength="30" placeholder="mis. Rafif"
                            value="{{ old('name', session('chat_name', '')) }}" required autocomplete="off"
                            class="field mt-2">

                        @error('name')
                            <p class="mt-2 text-xs text-danger">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit"
                        class="w-full rounded-md bg-tertiary px-5 py-3 font-display text-on-tertiary transition-opacity hover:opacity-90 focus:outline-none">
                        Join Room
                    </button>
                </form>
            </div>
        </section>
    @else
        <div id="chat-app"
            data-code="{{ $room->code }}"
            data-expired-at="{{ $room->expired_at->toIso8601String() }}"
            data-current-user="{{ $user->name }}"
            class="mx-auto flex h-screen max-w-3xl flex-col">

            {{-- Header --}}
            <header class="flex items-center justify-between gap-3 border-b border-secondary/30 px-4 py-3">
                <div class="flex items-baseline gap-3">
                    <a href="{{ route('home') }}" class="font-display text-sm text-secondary hover:text-tertiary">TempChat</a>
                    <span class="font-display text-sm tracking-widest text-primary">{{ $room->code }}</span>
                    <span id="online-count" class="font-display text-xs text-secondary">- online</span>
                </div>

                <div class="flex items-center gap-2">
                    <button id="sound-toggle" type="button" title="Suara notifikasi"
                        class="rounded-md border border-secondary/40 px-2 py-1 font-display text-xs text-secondary hover:border-tertiary hover:text-tertiary">🔊</button>
                    <button id="copy-link-btn" type="button" title="Copy Link"
                        class="rounded-md border border-secondary/40 px-2 py-1 font-display text-xs text-secondary hover:border-tertiary hover:text-tertiary">Copy</button>
                    <a id="share-wa-btn" href="#" target="_blank" rel="noopener" title="Share via WhatsApp"
                        class="rounded-md border border-secondary/40 px-2 py-1 font-display text-xs text-secondary hover:border-tertiary hover:text-tertiary">WA</a>
                    <a id="share-tg-btn" href="#" target="_blank" rel="noopener" title="Share via Telegram"
                        class="rounded-md border border-secondary/40 px-2 py-1 font-display text-xs text-secondary hover:border-tertiary hover:text-tertiary">TG</a>
                    <button id="leave-btn" type="button"
                        class="rounded-md border border-danger/60 px-2 py-1 font-display text-xs text-danger hover:bg-danger hover:text-neutral">Leave</button>
                </div>
            </header>

            {{-- Room info strip --}}
            <div class="flex items-center justify-between px-4 py-2 font-display text-xs text-secondary">
                <span class="capitalize">kamu: <span class="text-tertiary">{{ $user->name }}</span></span>
                <span>expired dalam <span id="countdown" class="text-secondary">--:--:--</span></span>
            </div>

            {{-- Messages --}}
            <div id="messages" class="flex-1 space-y-3 overflow-y-auto px-4 py-4">
                <div id="empty-state" class="flex h-full flex-col items-center justify-center gap-2 text-center">
                    <p class="font-display text-2xl text-secondary">Belum ada pesan</p>
                    <p class="text-sm text-secondary">Kirim pesan pertama atau share link room-nya!</p>
                </div>
            </div>

            {{-- Typing indicator --}}
            <div id="typing-indicator" class="hidden px-4 pb-1 font-display text-xs text-secondary">
                <span id="typing-names"></span>
                <span class="typing-dot"></span>
                <span class="typing-dot"></span>
                <span class="typing-dot"></span>
            </div>

            {{-- Input --}}
            <form id="send-form" class="flex items-end gap-2 border-t border-secondary/30 p-4">
                <textarea id="message-input" rows="1" maxlength="500" placeholder="Ketik pesan..."
                    class="field resize-none text-sm"></textarea>
                <button id="send-btn" type="submit"
                    class="shrink-0 rounded-md bg-tertiary px-5 py-3 font-display text-on-tertiary transition-opacity hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-40">
                    Kirim
                </button>
            </form>
        </div>
    @endunless
@endsection