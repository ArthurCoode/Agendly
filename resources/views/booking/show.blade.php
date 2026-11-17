@extends('layouts.app')

@php($cancelled = $appointment->status === \App\Enums\AppointmentStatus::Cancelled)

@section('title', $cancelled ? 'Agendamento cancelado' : 'Seu agendamento')

@section('content')
    <div class="page-header">
        <h1>{{ $cancelled ? 'Agendamento cancelado' : 'Seu agendamento' }}</h1>
        @unless ($cancelled)
            <p>Guarde o endereço desta página: ele é o seu comprovante e serve para cancelar.</p>
        @endunless
    </div>

    @if (session('status'))
        <div class="alert success" role="status">{{ session('status') }}</div>
    @endif

    @error('cancel')
        <div class="alert" role="alert">{{ $message }}</div>
    @enderror

    <section class="section">
        <div class="section-header">
            <h2>Detalhes</h2>
        </div>
        <div class="section-body">
            <dl class="summary">
                <dt>Status</dt>
                <dd><span class="badge {{ $appointment->status->getColor() }}">{{ $appointment->status->label() }}</span></dd>

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
            </dl>
        </div>
    </section>

    <div class="actions">
        <a class="btn {{ $appointment->customerCanCancel() ? 'secondary' : '' }}" href="{{ route('booking.create') }}">Fazer novo agendamento</a>

        @if ($appointment->customerCanCancel())
            <form method="POST" action="{{ route('booking.cancel', $appointment->token) }}"
                  onsubmit="return confirm('Cancelar este agendamento? O horário será liberado para outra pessoa.')">
                @csrf
                <button type="submit" class="btn outline-danger">Cancelar agendamento</button>
            </form>
            <p class="muted" style="text-align:center">
                Cancelamentos pelo site até {{ \App\Models\Appointment::CUSTOMER_CANCEL_NOTICE_HOURS }}h antes do horário.
            </p>
        @endif
    </div>
@endsection
