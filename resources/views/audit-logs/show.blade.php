@extends('layouts.app')

@section('content')
<div class="py-6">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 md:px-8">
        <div class="mb-6">
            <a href="{{ route('audit-logs.index') }}" class="text-indigo-600 hover:text-indigo-900">
                ← Back to Audit Logs
            </a>
        </div>

        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
            <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                <h3 class="text-lg leading-6 font-medium text-gray-900">
                    Audit Log Details
                </h3>
                <p class="mt-1 max-w-2xl text-sm text-gray-500">
                    ID: {{ $log->id }} | {{ $log->created_at->format('F j, Y \a\t g:i A') }}
                </p>
            </div>
            <div class="px-4 py-5 sm:p-6">
                <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                    <div class="sm:col-span-1">
                        <dt class="text-sm font-medium text-gray-500">User</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $log->user_name }}</dd>
                    </div>

                    <div class="sm:col-span-1">
                        <dt class="text-sm font-medium text-gray-500">Action</dt>
                        <dd class="mt-1">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                @if(in_array($log->action, ['create', 'sale_completed'])) bg-green-100 text-green-800
                                @elseif(in_array($log->action, ['update'])) bg-blue-100 text-blue-800
                                @elseif(in_array($log->action, ['delete'])) bg-red-100 text-red-800
                                @else bg-gray-100 text-gray-800
                                @endif">
                                {{ ucfirst(str_replace('_', ' ', $log->action)) }}
                            </span>
                        </dd>
                    </div>

                    <div class="sm:col-span-1">
                        <dt class="text-sm font-medium text-gray-500">Module</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ ucfirst(str_replace('_', ' ', $log->module)) }}</dd>
                    </div>

                    <div class="sm:col-span-1">
                        <dt class="text-sm font-medium text-gray-500">Target</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            @if($log->target_type)
                                {{ class_basename($log->target_type) }} #{{ $log->target_id }}
                            @else
                                N/A
                            @endif
                        </dd>
                    </div>

                    <div class="sm:col-span-2">
                        <dt class="text-sm font-medium text-gray-500">Description</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $log->description }}</dd>
                    </div>

                    <div class="sm:col-span-1">
                        <dt class="text-sm font-medium text-gray-500">IP Address</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $log->ip_address }}</dd>
                    </div>

                    <div class="sm:col-span-1">
                        <dt class="text-sm font-medium text-gray-500">User Agent</dt>
                        <dd class="mt-1 text-sm text-gray-900 truncate">{{ $log->user_agent }}</dd>
                    </div>

                    @if($log->old_values)
                    <div class="sm:col-span-2">
                        <dt class="text-sm font-medium text-gray-500 mb-2">Old Values (Before)</dt>
                        <dd class="mt-1">
                            <pre class="bg-gray-50 p-4 rounded-md text-xs overflow-x-auto">{{ json_encode($log->old_values, JSON_PRETTY_PRINT) }}</pre>
                        </dd>
                    </div>
                    @endif

                    @if($log->new_values)
                    <div class="sm:col-span-2">
                        <dt class="text-sm font-medium text-gray-500 mb-2">New Values (After)</dt>
                        <dd class="mt-1">
                            <pre class="bg-gray-50 p-4 rounded-md text-xs overflow-x-auto">{{ json_encode($log->new_values, JSON_PRETTY_PRINT) }}</pre>
                        </dd>
                    </div>
                    @endif
                </dl>
            </div>
        </div>
    </div>
</div>
@endsection
