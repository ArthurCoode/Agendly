@extends('layouts.app')

@section('title', 'Agendar horário')

@php
    // Valores digitados antes de um erro do servidor; a tela reabre já preenchida.
    $oldInput = [
        'service_id' => old('service_id'),
        'professional_id' => old('professional_id'),
        'starts_at' => old('starts_at'),
    ];
@endphp

@section('content')
    <h1>Agende seu horário</h1>

    @if ($errors->any())
        <div class="alert" role="alert">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form id="booking-form" class="card" method="POST" action="{{ route('booking.store') }}">
        @csrf
        <input type="hidden" name="starts_at" id="starts_at" value="">

        <label for="service_id">1. Serviço</label>
        <select name="service_id" id="service_id" required>
            <option value="">Escolha um serviço</option>
        </select>

        <label for="professional_id">2. Profissional</label>
        <select name="professional_id" id="professional_id" required disabled>
            <option value="">Escolha um profissional</option>
        </select>

        <label for="date">3. Dia</label>
        <input type="date" id="date">

        <label>4. Horário</label>
        <div id="slots" class="slots" aria-live="polite"></div>
        <p id="slots-msg" class="muted">Escolha serviço, profissional e dia para ver os horários.</p>

        <label for="customer_name">Seu nome</label>
        <input type="text" name="customer_name" id="customer_name" value="{{ old('customer_name') }}" maxlength="120" required>

        <label for="customer_phone">Telefone (com DDD)</label>
        <input type="tel" name="customer_phone" id="customer_phone" value="{{ old('customer_phone') }}" placeholder="(77) 99999-9999" required>

        <label for="customer_email">E-mail (opcional)</label>
        <input type="email" name="customer_email" id="customer_email" value="{{ old('customer_email') }}">

        <button type="submit" id="submit" class="btn" disabled>Confirmar agendamento</button>
    </form>
@endsection

@push('scripts')
<script>
    const catalog = @json($catalog);
    const slotsUrl = @json(route('booking.slots'));
    const old = @json($oldInput);

    const $ = (id) => document.getElementById(id);
    const serviceEl = $('service_id');
    const proEl = $('professional_id');
    const dateEl = $('date');
    const slotsEl = $('slots');
    const msgEl = $('slots-msg');
    const startsEl = $('starts_at');
    const submitEl = $('submit');
    const brl = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' });

    let requestId = 0; // descarta respostas antigas se o cliente mudar a escolha rápido

    function option(value, text) {
        const el = document.createElement('option');
        el.value = value;
        el.textContent = text;
        return el;
    }

    function isoDate(date) {
        return new Date(date.getTime() - date.getTimezoneOffset() * 60000).toISOString().slice(0, 10);
    }

    function resetSlots(message) {
        slotsEl.replaceChildren();
        startsEl.value = '';
        submitEl.disabled = true;
        msgEl.textContent = message;
    }

    function fillProfessionals() {
        const service = catalog.find((s) => String(s.id) === serviceEl.value);
        proEl.replaceChildren(option('', 'Escolha um profissional'));
        proEl.disabled = !service;
        (service?.professionals ?? []).forEach((p) => proEl.append(option(p.id, p.name)));
    }

    function selectSlot(button, time) {
        slotsEl.querySelectorAll('.slot').forEach((b) => b.classList.remove('selected'));
        button.classList.add('selected');
        startsEl.value = `${dateEl.value} ${time}`;
        submitEl.disabled = false;
    }

    async function loadSlots() {
        const current = ++requestId;
        resetSlots('Escolha serviço, profissional e dia para ver os horários.');

        if (!serviceEl.value || !proEl.value || !dateEl.value) return;

        msgEl.textContent = 'Buscando horários...';
        const params = new URLSearchParams({
            service_id: serviceEl.value,
            professional_id: proEl.value,
            date: dateEl.value,
        });

        try {
            const response = await fetch(`${slotsUrl}?${params}`, { headers: { Accept: 'application/json' } });
            if (!response.ok) throw new Error('request failed');
            const { slots } = await response.json();
            if (current !== requestId) return;

            if (slots.length === 0) {
                msgEl.textContent = 'Sem horários livres nesse dia. Tente outra data.';
                return;
            }

            msgEl.textContent = '';
            slots.forEach((time) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'slot';
                button.textContent = time;
                button.addEventListener('click', () => selectSlot(button, time));
                slotsEl.append(button);
            });
        } catch {
            if (current === requestId) msgEl.textContent = 'Não foi possível buscar os horários. Tente novamente.';
        }
    }

    // Catálogo e limites de data (hoje até 60 dias, igual à validação do servidor)
    catalog.forEach((s) => serviceEl.append(option(s.id, `${s.name} · ${s.duration} min · ${brl.format(s.price)}`)));
    const today = new Date();
    const limit = new Date();
    limit.setDate(limit.getDate() + 60);
    dateEl.min = isoDate(today);
    dateEl.max = isoDate(limit);

    serviceEl.addEventListener('change', () => { fillProfessionals(); loadSlots(); });
    proEl.addEventListener('change', loadSlots);
    dateEl.addEventListener('change', loadSlots);

    // Reabre a tela já preenchida quando o servidor devolveu erro (ex.: horário ocupado)
    if (old.service_id) {
        serviceEl.value = old.service_id;
        fillProfessionals();
        proEl.value = old.professional_id ?? '';
        if (old.starts_at) dateEl.value = old.starts_at.slice(0, 10);
        loadSlots();
    }
</script>
@endpush
