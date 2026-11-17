@extends('layouts.app')

@php($cancelled = $appointment->status === \App\Enums\AppointmentStatus::Cancelled)

@section('title', $cancelled ? 'Agendamento cancelado' : 'Seu agendamento')

@section('content')
    <h1>{{ $cancelled ? 'Agendamento cancelado' : 'Seu agendamento' }}</h1>

    @if (session('status'))
        <div class="alert success" role="status">{{ session('status') }}</div>
    @endif

    @error('cancel')
        <div class="alert" role="alert">{{ $message }}</div>
    @enderror

    <div class="card">
        <dl class="summary">
            <dt>Cliente</dt>
            <dd>{{ $appointment->customer_name }}</dd>

            <dt>Serviço</dt>
            <dd>{{ $appointment->service->name }}</dd>

            <dt>Profissional</dt>
            <dd>{{ $appointment->professional->name }}</dd>

            <dt>Data</dt>
            <dd>{{ $appointment->starts_at->translatedFormat('l, d/m/Y') }}</dd>

            <dt>Horário</dt>
            <dd>{{ $appointment->starts_at->format('H:i') }} às {{ $appointment->ends_at->format('H:i') }}</dd>

            <dt>Status</dt>
            <dd>{{ $appointment->status->label() }}</dd>
        </dl>
    </div>

    @unless ($cancelled)
        <p class="muted">Guarde o endereço desta página: ele é o seu comprovante e serve para cancelar.</p>
    @endunless

    <a class="btn" href="{{ route('booking.create') }}">Fazer novo agendamento</a>

    @if ($appointment->customerCanCancel())
        <form method="POST" action="{{ route('booking.cancel', $appointment->token) }}"
              onsubmit="return confirm('Cancelar este agendamento? O horário será liberado para outra pessoa.')">
            @csrf
            <button type="submit" class="btn outline-danger">Cancelar agendamento</button>
        </form>
        <p class="muted">
            Cancelamentos pelo site até {{ \App\Models\Appointment::CUSTOMER_CANCEL_NOTICE_HOURS }}h antes do horário.
        </p>
    @endif
@endsection
