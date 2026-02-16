@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Surgery Usage Records</h1>
            <p class="text-sm text-gray-600">Track items used in surgical procedures</p>
        </div>
        <a href="{{ route('surgery-usage.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition-colors">
            Record Surgery
        </a>
    </div>

    <div class="bg-white shadow sm:rounded-md">
        <ul class="divide-y divide-gray-200">
            @foreach($usages as $usage)
            <li>
                <a href="{{ route('surgery-usage.show', $usage->id) }}" class="block hover:bg-gray-50 transition duration-150 ease-in-out">
                    <div class="px-4 py-4 sm:px-6">
                        <div class="flex items-center justify-between">
                            <div class="text-sm font-medium text-indigo-600 truncate">
                                {{ $usage->surgery_date->format('M d, Y') }} - {{ $usage->patient_name ?? 'Unknown Patient' }}
                            </div>
                            <div class="ml-2 flex-shrink-0 flex">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                    {{ $usage->items->count() }} items
                                </span>
                            </div>
                        </div>
                        <div class="mt-2 sm:flex sm:justify-between">
                            <div class="sm:flex">
                                <p class="flex items-center text-sm text-gray-500">
                                    Surgeon: {{ $usage->surgeon_name ?? 'N/A' }}
                                </p>
                                <p class="mt-2 flex items-center text-sm text-gray-500 sm:mt-0 sm:ml-6">
                                    Facility: {{ $usage->facility_name ?? 'N/A' }}
                                </p>
                            </div>
                            <div class="mt-2 flex items-center text-sm text-gray-500 sm:mt-0">
                                Set: {{ $usage->setLocation ? $usage->setLocation->name : 'Main Store' }}
                            </div>
                        </div>
                    </div>
                </a>
            </li>
            @endforeach
            
            @if($usages->isEmpty())
            <li class="px-4 py-8 text-center text-gray-500">
                No surgery records found. Record one to get started.
            </li>
            @endif
        </ul>
    </div>

    <div class="mt-4">
        {{ $usages->links() }}
    </div>
</div>
@endsection
