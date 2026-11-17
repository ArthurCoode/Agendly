<?php

namespace App\Http\Controllers;

use App\Exceptions\SlotUnavailableException;
use App\Http\Requests\AvailableSlotsRequest;
use App\Http\Requests\StoreAppointmentRequest;
use App\Models\Appointment;
use App\Models\Professional;
use App\Models\Service;
use App\Services\AvailabilityService;
use App\Services\BookingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;

class BookingController extends Controller
{
    /** Página pública: o catálogo vai embutido na página para a tela filtrar sem novas requisições. */
    public function create(): View
    {
        $catalog = Service::query()
            ->where('active', true)
            ->with(['professionals' => fn ($query) => $query->where('active', true)])
            ->orderBy('name')
            ->get()
            ->map(fn (Service $service) => [
                'id' => $service->id,
                'name' => $service->name,
                'duration' => $service->duration_minutes,
                'price' => (float) $service->price,
                'professionals' => $service->professionals
                    ->map(fn (Professional $p) => ['id' => $p->id, 'name' => $p->name])
                    ->values(),
            ])
            ->values();

        return view('booking.create', compact('catalog'));
    }

    /** JSON com os horários livres de um profissional/serviço/dia. */
    public function slots(AvailableSlotsRequest $request, AvailabilityService $availability): JsonResponse
    {
        $data = $request->validated();

        $slots = $availability
            ->slotsFor(
                Professional::findOrFail($data['professional_id']),
                Service::findOrFail($data['service_id']),
                $data['date'],
            )
            ->map(fn (Carbon $slot) => $slot->format('H:i'))
            ->values();

        return response()->json(['slots' => $slots]);
    }

    public function store(StoreAppointmentRequest $request, BookingService $booking): RedirectResponse
    {
        $data = $request->validated();

        try {
            $appointment = $booking->book(
                Professional::findOrFail($data['professional_id']),
                Service::findOrFail($data['service_id']),
                Carbon::createFromFormat('Y-m-d H:i', $data['starts_at']),
                [
                    'customer_name' => $data['customer_name'],
                    'customer_phone' => $data['customer_phone'],
                    'customer_email' => $data['customer_email'] ?? null,
                ],
            );
        } catch (SlotUnavailableException $e) {
            return back()->withInput()->withErrors(['starts_at' => $e->getMessage()]);
        }

        return redirect()->route('booking.show', $appointment->token);
    }

    /** Confirmação acessada pelo token secreto do agendamento. */
    public function show(string $token): View
    {
        $appointment = Appointment::with(['service', 'professional'])
            ->where('token', $token)
            ->firstOrFail();

        return view('booking.show', compact('appointment'));
    }
}
