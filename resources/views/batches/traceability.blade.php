@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-2xl font-bold mb-6">🔍 Implant Traceability Search</h1>

        <!-- Search Form -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <form method="GET" action="{{ route('batches.traceability') }}">
                <div class="flex space-x-4">
                    <div class="flex-1">
                        <label class="block text-sm font-medium mb-2">Serial Number</label>
                        <input type="text" name="serial_number" value="{{ $serialNumber ?? '' }}" 
                               placeholder="Enter serial number..." 
                               class="w-full border rounded px-4 py-2 text-lg" required>
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="bg-blue-500 text-white px-6 py-2 rounded hover:bg-blue-600">
                            Search
                        </button>
                    </div>
                </div>
            </form>
        </div>

        @if(isset($error))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                {{ $error }}
            </div>
        @endif

        @if(isset($batch))
            <!-- Traceability Chain -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-semibold mb-4">Traceability Chain</h2>

                <!-- Product Info -->
                <div class="mb-6 p-4 bg-blue-50 rounded">
                    <h3 class="font-semibold text-blue-900 mb-2">Product Information</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <span class="text-sm text-gray-600">Product:</span>
                            <div class="font-medium">{{ $chain['product']->product_name }}</div>
                        </div>
                        <div>
                            <span class="text-sm text-gray-600">Code:</span>
                            <div class="font-medium">{{ $chain['product']->code }}</div>
                        </div>
                        @if($chain['product']->manufacturer)
                            <div>
                                <span class="text-sm text-gray-600">Manufacturer:</span>
                                <div class="font-medium">{{ $chain['product']->manufacturer }}</div>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Batch Info -->
                <div class="mb-6 p-4 bg-green-50 rounded">
                    <h3 class="font-semibold text-green-900 mb-2">Batch Information</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <span class="text-sm text-gray-600">Batch Number:</span>
                            <div class="font-medium">{{ $batch->batch_number }}</div>
                        </div>
                        <div>
                            <span class="text-sm text-gray-600">Serial Number:</span>
                            <div class="font-medium font-mono bg-white px-2 py-1 rounded">{{ $batch->serial_number }}</div>
                        </div>
                        @if($batch->expiry_date)
                            <div>
                                <span class="text-sm text-gray-600">Expiry Date:</span>
                                <div class="font-medium {{ $batch->isExpired() ? 'text-red-600' : '' }}">
                                    {{ $batch->expiry_date->format('Y-m-d') }}
                                    @if($batch->isExpired())
                                        <span class="text-xs">(EXPIRED)</span>
                                    @endif
                                </div>
                            </div>
                        @endif
                        <div>
                            <span class="text-sm text-gray-600">Status:</span>
                            <div>
                                <span class="px-2 py-1 text-xs rounded-full bg-{{ $batch->status_color }}-100 text-{{ $batch->status_color }}-800">
                                    {{ ucfirst($batch->status) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Manufacturer Info -->
                @if($chain['manufacturer'])
                    <div class="mb-6 p-4 bg-purple-50 rounded">
                        <h3 class="font-semibold text-purple-900 mb-2">Manufacturer</h3>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <span class="text-sm text-gray-600">Name:</span>
                                <div class="font-medium">{{ $chain['manufacturer']->name }}</div>
                            </div>
                            @if($chain['manufacturer']->email)
                                <div>
                                    <span class="text-sm text-gray-600">Email:</span>
                                    <div class="font-medium">{{ $chain['manufacturer']->email }}</div>
                                </div>
                            @endif
                            @if($chain['manufacturer']->phone)
                                <div>
                                    <span class="text-sm text-gray-600">Phone:</span>
                                    <div class="font-medium">{{ $chain['manufacturer']->phone }}</div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                <!-- Location Info -->
                @if($chain['location'])
                    <div class="mb-6 p-4 bg-yellow-50 rounded">
                        <h3 class="font-semibold text-yellow-900 mb-2">Current/Last Location</h3>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <span class="text-sm text-gray-600">Location:</span>
                                <div class="font-medium">{{ $chain['location']->name }}</div>
                            </div>
                            <div>
                                <span class="text-sm text-gray-600">Type:</span>
                                <div class="font-medium">{{ ucfirst($chain['location']->type) }}</div>
                            </div>
                            @if($chain['location']->address)
                                <div class="col-span-2">
                                    <span class="text-sm text-gray-600">Address:</span>
                                    <div class="font-medium">{{ $chain['location']->address }}</div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                <!-- Customer Info (if sold) -->
                @if($chain['customer'])
                    <div class="mb-6 p-4 bg-indigo-50 rounded">
                        <h3 class="font-semibold text-indigo-900 mb-2">Sold To</h3>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <span class="text-sm text-gray-600">Customer:</span>
                                <div class="font-medium">{{ $chain['customer']->name }}</div>
                            </div>
                            <div>
                                <span class="text-sm text-gray-600">Sale Date:</span>
                                <div class="font-medium">{{ $batch->sold_date ? $batch->sold_date->format('Y-m-d') : 'N/A' }}</div>
                            </div>
                            @if($chain['customer']->email)
                                <div>
                                    <span class="text-sm text-gray-600">Email:</span>
                                    <div class="font-medium">{{ $chain['customer']->email }}</div>
                                </div>
                            @endif
                            @if($chain['customer']->phone)
                                <div>
                                    <span class="text-sm text-gray-600">Phone:</span>
                                    <div class="font-medium">{{ $chain['customer']->phone }}</div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                <!-- Recall Info (if recalled) -->
                @if($batch->isRecalled())
                    <div class="mb-6 p-4 bg-red-50 border-2 border-red-500 rounded">
                        <h3 class="font-semibold text-red-900 mb-2">⚠️ RECALL ALERT</h3>
                        <div class="space-y-2">
                            <div>
                                <span class="text-sm text-gray-600">Recall Status:</span>
                                <div class="font-medium">
                                    <span class="px-2 py-1 text-xs rounded-full bg-{{ $batch->recall_color }}-100 text-{{ $batch->recall_color }}-800">
                                        {{ ucfirst($batch->recall_status) }}
                                    </span>
                                </div>
                            </div>
                            @if($batch->recall_date)
                                <div>
                                    <span class="text-sm text-gray-600">Recall Date:</span>
                                    <div class="font-medium">{{ $batch->recall_date->format('Y-m-d') }}</div>
                                </div>
                            @endif
                            @if($batch->recall_reason)
                                <div>
                                    <span class="text-sm text-gray-600">Reason:</span>
                                    <div class="font-medium whitespace-pre-line">{{ $batch->recall_reason }}</div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                <!-- Timeline -->
                <div class="mt-6 p-4 bg-gray-50 rounded">
                    <h3 class="font-semibold mb-2">Timeline</h3>
                    <div class="space-y-2 text-sm">
                        <div class="flex items-center">
                            <div class="w-32 text-gray-600">Created:</div>
                            <div class="font-medium">{{ $batch->created_at->format('Y-m-d H:i') }}</div>
                        </div>
                        @if($batch->sold_date)
                            <div class="flex items-center">
                                <div class="w-32 text-gray-600">Sold:</div>
                                <div class="font-medium">{{ $batch->sold_date->format('Y-m-d') }}</div>
                            </div>
                        @endif
                        @if($batch->recall_date)
                            <div class="flex items-center">
                                <div class="w-32 text-gray-600">Recalled:</div>
                                <div class="font-medium text-red-600">{{ $batch->recall_date->format('Y-m-d') }}</div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
