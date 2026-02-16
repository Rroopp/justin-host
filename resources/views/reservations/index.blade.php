@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8 flex flex-col md:flex-row justify-between items-end gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Surgery Case Reservations</h1>
            <p class="text-gray-600 mt-1">Manage stock reservations for upcoming procedures</p>
        </div>
        <div>
            <a href="{{ route('reservations.create') }}" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                <i class="fas fa-plus mr-2"></i> Book New Case
            </a>
        </div>
    </div>

    <!-- Stats / Summary -->
    <div class="mb-6 grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white rounded-lg shadow p-6 border-l-4 border-indigo-500">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-indigo-100 rounded-md p-3">
                    <i class="fas fa-calendar-alt text-indigo-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-medium text-gray-900">Upcoming Cases</h3>
                    <div class="text-2xl font-bold text-gray-900">{{ $totalUpcoming }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
        <form action="{{ route('reservations.index') }}" method="GET" class="flex flex-col md:flex-row gap-4">
            <div class="flex-1">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search patient, surgeon, case #..." class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
            </div>
            <div class="w-full md:w-48">
                <select name="status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    <option value="">All Statuses</option>
                    <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="confirmed" {{ request('status') == 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                    <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-200 font-medium text-sm">Filter</button>
                <a href="{{ route('reservations.index') }}" class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-50 font-medium text-sm">Clear</a>
            </div>
        </form>
    </div>

    <!-- Tabs -->
    <div class="border-b border-gray-200 mb-6">
        <nav class="-mb-px flex space-x-8" aria-label="Tabs">
            <a href="{{ route('reservations.index', array_merge(request()->all(), ['date_filter' => 'upcoming'])) }}" class="{{ (request('date_filter') == 'upcoming' || !request('date_filter')) ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                Upcoming
            </a>
            <a href="{{ route('reservations.index', array_merge(request()->all(), ['date_filter' => 'past'])) }}" class="{{ request('date_filter') == 'past' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                Past History
            </a>
            <a href="{{ route('reservations.index', array_merge(request()->except('date_filter'))) }}" class="{{ request()->has('date_filter') ? 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' : (request('date_filter') === null && request('search') ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300') }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                All Cases
            </a>
        </nav>
    </div>

    <!-- List -->
    <div class="bg-white shadow sm:rounded-md">
        <ul role="list" class="divide-y divide-gray-200">
            @forelse($reservations as $reservation)
                <li>
                    <a href="{{ route('reservations.show', $reservation->id) }}" class="block hover:bg-gray-50 transition duration-150 ease-in-out">
                        <div class="px-4 py-4 sm:px-6">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center truncate">
                                    <p class="text-sm font-medium text-indigo-600 truncate flex items-center gap-2">
                                        {{ $reservation->patient_name }}
                                        <span class="text-gray-400 font-normal">#{{ $reservation->patient_id ?? 'N/A' }}</span>
                                    </p>
                                    @if($reservation->procedure_name)
                                        <span class="ml-2 px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                            {{ $reservation->procedure_name }}
                                        </span>
                                    @endif
                                </div>
                                <div class="ml-2 flex-shrink-0 flex">
                                    @php
                                        $statusClass = match($reservation->status) {
                                            'draft' => 'bg-gray-100 text-gray-800',
                                            'confirmed' => 'bg-green-100 text-green-800',
                                            'completed' => 'bg-blue-100 text-blue-800',
                                            'cancelled' => 'bg-red-100 text-red-800',
                                            default => 'bg-gray-100 text-gray-800',
                                        };
                                    @endphp
                                    <p class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusClass }}">
                                        {{ ucfirst($reservation->status) }}
                                    </p>
                                </div>
                            </div>
                            <div class="mt-2 sm:flex sm:justify-between">
                                <div class="sm:flex">
                                    <p class="flex items-center text-sm text-gray-500">
                                        <i class="fas fa-user-md flex-shrink-0 mr-1.5 text-gray-400"></i>
                                        {{ $reservation->surgeon_name }}
                                    </p>
                                    <p class="mt-2 flex items-center text-sm text-gray-500 sm:mt-0 sm:ml-6">
                                        <i class="fas fa-hashtag flex-shrink-0 mr-1.5 text-gray-400"></i>
                                        {{ $reservation->case_number }}
                                    </p>
                                </div>
                                <div class="mt-2 flex items-center text-sm text-gray-500 sm:mt-0">
                                    <i class="far fa-calendar-alt flex-shrink-0 mr-1.5 text-gray-400"></i>
                                    <p>
                                        {{ $reservation->surgery_date->format('M d, Y @ h:i A') }}
                                        @if($reservation->surgery_date->isToday())
                                            <span class="text-green-600 font-bold ml-1">(Today)</span>
                                        @elseif($reservation->surgery_date->isTomorrow())
                                            <span class="text-blue-600 ml-1">(Tomorrow)</span>
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </div>
                    </a>
                </li>
            @empty
                <li class="px-4 py-8 text-center text-gray-500">
                    <i class="fas fa-notes-medical text-4xl mb-3 text-gray-300"></i>
                    <p class="text-lg font-medium text-gray-900">No reservations found</p>
                    <p>Get started by booking a new surgery case.</p>
                </li>
            @endforelse
        </ul>
        @if($reservations->hasPages())
            <div class="bg-gray-50 px-4 py-4 border-t border-gray-200 sm:px-6">
                {{ $reservations->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
