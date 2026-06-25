@extends('layouts.app')

@section('title', 'Join classroom — ClassPulse')

@section('content')
@php
    $prefill = strtoupper((string) old('room_code', $code ?? request('code') ?? request()->cookie('last_room_code') ?? ''));
    $chars = str_split(str_pad(substr($prefill, 0, 6), 6, ' '));
@endphp

<div class="cp-join-hero cp-fade-in">
    <div class="cp-empty-shapes mb-3" aria-hidden="true">
        <span></span><span></span><span></span><span></span>
    </div>
    <h1 class="cp-page-title mb-2">Enter the room</h1>
    <p class="cp-page-sub mb-4">Type the 6-character code on the board. Game on.</p>

    <div class="cp-join-slots" aria-hidden="true" data-join-slots>
        @foreach ($chars as $ch)
            <div class="cp-join-slot {{ trim($ch) !== '' ? 'is-filled' : '' }}">{{ trim($ch) !== '' ? $ch : '' }}</div>
        @endforeach
    </div>

    <form method="POST" action="{{ route('student.join.submit') }}">
        @csrf
        <div class="mb-3">
            <label for="room_code" class="form-label">Room code</label>
            <input id="room_code" name="room_code" type="text" maxlength="6"
                   value="{{ $prefill }}"
                   class="form-control cp-join-input @error('room_code') is-invalid @enderror"
                   required autofocus autocomplete="off" inputmode="text" data-join-input>
            @error('room_code')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>
        <button type="submit" class="btn btn-cp btn-lg w-100">Join the fun</button>
    </form>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var input = document.querySelector('[data-join-input]');
    var slots = document.querySelectorAll('[data-join-slots] .cp-join-slot');
    if (!input || !slots.length) return;
    function paint() {
        var v = (input.value || '').toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 6);
        input.value = v;
        for (var i = 0; i < slots.length; i++) {
            var ch = v.charAt(i) || '';
            slots[i].textContent = ch;
            slots[i].classList.toggle('is-filled', !!ch);
        }
    }
    input.addEventListener('input', paint);
    paint();
})();
</script>
@endpush
