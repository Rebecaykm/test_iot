<x-guest-layout>
    <div class="pt-4 bg-gray-100 dark:bg-gray-900 text-xs">
        <div class="min-h-screen flex flex-col items-center pt-6 sm:pt-0">
            <div class="w-full px-3">
                <div class="grid grid-cols-1 gap-4">
                    <table>
                        <thead>
                            <tr>
                                <th class="px-4 py-2">Product</th>
                                <th class="px-4 py-2">Work Center</th>
                                <th class="px-4 py-2">SNP</th>
                                @foreach ($hourlyRange as $hour)
                                    <th class="px-4 py-2">{{ $hour }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($report as $row)
                                <tr>
                                    <td class="border">{{ $row['product'] }}</td>
                                    <td class="border">{{ $row['workCenterDescription'] }}</td>
                                    <td class="border">{{ $row['SNP'] }}</td>
                                    @foreach ($hourlyRange as $hour)
                                        <td class="border font-mono">
                                            @if(isset($row['hourlyData'][$hour]))
                                                {{ $row['hourlyData'][$hour]['sequences'] }}
                                            @else
                                                -
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
</x-guest-layout>