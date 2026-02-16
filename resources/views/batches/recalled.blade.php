@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">🚨 Recalled Batches</h1>
        <div class="flex space-x-2">
            <a href="{{ route('batches.index') }}" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
                ← Back to Batches
            </a>
        </div>
    </div>

    <!-- Summary -->
    <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
        <div class="text-sm text-gray-600">Total Recalled Batches</div>
        <div class="text-2xl font-bold text-red-700">{{ $batches->count() }}</div>
    </div>

    <!-- Batches Table -->
    <div class="bg-white rounded-lg shadow">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Batch #</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Serial #</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Manufacturer</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Recall Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Recall Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reason</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($batches as $batch)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm">
                            <div class="font-medium">{{ $batch->inventory->product_name ?? 'N/A' }}</div>
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
                            {{ $batch->manufacturer->name ?? '-' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            @if($batch->recall_date)
                                <span class="text-red-600 font-semibold">{{ $batch->recall_date->format('Y-m-d') }}</span>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs rounded-full bg-{{ $batch->recall_color }}-100 text-{{ $batch->recall_color }}-800">
                                {{ ucfirst($batch->recall_status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm">
                            @if($batch->recall_reason)
                                <div class="max-w-xs truncate" title="{{ $batch->recall_reason }}">
                                    {{ Str::limit($batch->recall_reason, 50) }}
                                </div>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            @if($batch->recall_status === 'active')
                                <button onclick="resolveRecall({{ $batch->id }})" class="text-green-600 hover:text-green-900">
                                    Resolve
                                </button>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-6 py-4 text-center text-gray-500">
                            No recalled batches found
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<script>
function resolveRecall(id) {
    const notes = prompt('Resolution notes (optional):');
    if (notes !== null) {
        fetch(`/batches/${id}/resolve-recall`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ resolution_notes: notes })
        }).then(() => location.reload());
    }
}
</script>
@endsection
