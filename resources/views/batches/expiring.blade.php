@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">⚠️ Expiring Batches</h1>
        <div class="flex space-x-2">
            <a href="{{ route('batches.index') }}" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
                ← Back to Batches
            </a>
        </div>
    </div>

    <!-- Filter -->
    <div class="bg-white rounded-lg shadow p-4 mb-6">
        <form method="GET" action="{{ route('batches.expiring') }}" class="flex items-end space-x-4">
            <div>
                <label class="block text-sm font-medium mb-1">Days Until Expiry</label>
                <select name="days" class="border rounded px-3 py-2">
                    <option value="30" {{ $days == 30 ? 'selected' : '' }}>30 days</option>
                    <option value="60" {{ $days == 60 ? 'selected' : '' }}>60 days</option>
                    <option value="90" {{ $days == 90 ? 'selected' : '' }}>90 days</option>
                    <option value="180" {{ $days == 180 ? 'selected' : '' }}>180 days</option>
                </select>
            </div>
            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Filter</button>
        </form>
    </div>

    <!-- Summary -->
    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
        <div class="text-sm text-gray-600">Batches Expiring Within {{ $days }} Days</div>
        <div class="text-2xl font-bold text-yellow-700">{{ $batches->count() }}</div>
    </div>

    <!-- Batches Table -->
    <div class="bg-white rounded-lg shadow">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Batch #</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Serial #</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Expiry Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Days Until Expiry</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Qty</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Location</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($batches as $batch)
                    @php
                        $daysUntilExpiry = $batch->expiry_date->diffInDays(now());
                        $urgencyColor = $daysUntilExpiry <= 30 ? 'red' : ($daysUntilExpiry <= 60 ? 'orange' : 'yellow');
                    @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm">
                            <div class="font-medium">{{ $batch->inventory->product_name ?? 'N/A' }}</div>
                            @if($batch->manufacturer)
                                <div class="text-xs text-gray-500">{{ $batch->manufacturer->name }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $batch->batch_number }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            @if($batch->serial_number)
                                <span class="font-mono bg-gray-100 px-2 py-1 rounded">{{ $batch->serial_number }}</span>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <span class="font-semibold text-{{ $urgencyColor }}-600">
                                {{ $batch->expiry_date->format('Y-m-d') }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <span class="px-2 py-1 rounded-full bg-{{ $urgencyColor }}-100 text-{{ $urgencyColor }}-800">
                                {{ $daysUntilExpiry }} days
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $batch->quantity }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $batch->location->name ?? '-' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs rounded-full bg-{{ $batch->status_color }}-100 text-{{ $batch->status_color }}-800">
                                {{ ucfirst($batch->status) }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-6 py-4 text-center text-gray-500">
                            No batches expiring within {{ $days }} days
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
