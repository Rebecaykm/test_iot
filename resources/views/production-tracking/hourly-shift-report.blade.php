<x-guest-layout>
    <div class="pt-4 bg-gray-100 dark:bg-gray-900 text-xs">
        <div class="min-h-screen flex flex-col items-center pt-6 sm:pt-0">
            <div class="w-full px-3">
                <div class="grid grid-cols-1 gap-4">
                    <div class="grid grid-cols-4 gap-4 mb-6">

    {{-- Planned --}}
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <div class="text-xs text-blue-600 uppercase">
            Planned Total
        </div>
        <div class="text-3xl font-bold text-blue-800">
            {{ number_format($totalPlanned) }}
        </div>
    </div>

    {{-- Expected --}}
    <div class="bg-amber-50 border border-amber-200 rounded-lg p-4">
        <div class="text-xs text-amber-600 uppercase">
            Should Have By Now
        </div>
        <div class="text-3xl font-bold text-amber-700">
            {{ number_format($expectedByNow) }}
        </div>
    </div>

    {{-- Actual --}}
    <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-4">
        <div class="text-xs text-emerald-600 uppercase">
            Completed
        </div>
        <div class="text-3xl font-bold text-emerald-700">
            {{ number_format($totalCompleted) }}
        </div>
    </div>

    {{-- Variance --}}
    <div class="
        rounded-lg p-4 border
        {{ $variance >= 0
            ? 'bg-green-50 border-green-200'
            : 'bg-red-50 border-red-200' }}
    ">
        <div class="text-xs uppercase">
            Variance
        </div>

        <div class="
            text-3xl font-bold
            {{ $variance >= 0
                ? 'text-green-700'
                : 'text-red-700' }}
        ">
            {{ $variance >= 0 ? '+' : '' }}{{ number_format($variance) }}
        </div>
    </div>
</div>

                    <div class="overflow-x-auto rounded-lg shadow border border-gray-200">
                        <table class="min-w-full text-sm text-center border-collapse">
                            <thead class="bg-slate-800 text-white sticky top-0 z-10">
                                <tr>
                                    <th class="px-4 py-3">Product</th>
                                    <th class="px-4 py-3">SNP</th>
                                    <th class="px-4 py-3">Seq/Hr</th>
                                    <th class="px-4 py-3">Seq. Planned</th>
                                    <th class="px-4 py-3">Seq. Completed</th>
                                    @foreach ($hourlyRange as $hour)
                                        <th
                                            class="px-3 py-3 min-w-[85px]
                                            @if((int) substr($hour,0,2) == (int) date('H'))
                                                bg-amber-300 text-black font-bold
                                            @endif"
                                        >
                                            {{ $hour }}
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>

                            <tbody>
                                @foreach ($report as $row)
                                    <tr class="hover:bg-slate-50 transition-colors">
                                        <td class="border px-3 py-2 font-medium text-left">
                                            {{ $row['product'] }}
                                        </td>

                                        <td class="border px-3 py-2">
                                            {{ $row['SNP'] }}
                                        </td>

                                        <td class="border px-3 py-2 font-semibold text-slate-700">
                                            {{ $row['sequencesPerHour'] }}
                                        </td>

                                        <td class="border px-3 py-2 font-semibold text-slate-700">
                                            {{ $row['plannedSequences'] }}
                                            <br>
                                            <span>{{ $row['plannedQty'] }} PC</span>
                                        </td>

                                        <td class="border px-3 py-2 font-semibold text-emerald-600">
                                            {{ $row['completedSequences'] }}
                                            <br>
                                            <span>{{ $row['completedQty'] }} PC</span>
                                        </td>

                                        @foreach ($hourlyRange as $hour)
                                            <td class="border p-1 align-middle">
                                                @if(isset($row['hourlyData'][$hour]) && $row['hourlyData'][$hour]['plannedSequences'] !== '')

                                                    <div class="rounded-md bg-gray-50 overflow-hidden">

                                                        {{-- Planned --}}
                                                        <div class="bg-slate-100 text-slate-700 font-semibold py-1 px-2">
                                                            {{ $row['hourlyData'][$hour]['plannedSequences'] }}
                                                        </div>

                                                        {{-- Completed --}}
                                                        <div class="bg-emerald-50 text-emerald-600 font-bold py-1 px-2 border-t">
                                                            {{ $row['hourlyData'][$hour]['completedSequences'] ?: '-' }}
                                                        </div>

                                                    </div>

                                                @else
                                                    <div class="text-gray-300 font-light">
                                                        —
                                                    </div>
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        const currentHour = new Date().getHours();
        const hourCells = document.querySelectorAll('th, td');

        console.log('Current Hour:', currentHour);
    </script>
</x-guest-layout>
