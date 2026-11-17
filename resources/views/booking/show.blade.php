@extends('layouts.app')

@section('title', 'Agendamento confirmado')

@section('content')
    <h1>Agendamento confirmado</h1>

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

    <p class="muted">Guarde o endereço desta página: ele é o seu comprovante.</p>
    <a class="btn" href="{{ route('booking.create') }}">Fazer novo agendamento</a>
@endsection
