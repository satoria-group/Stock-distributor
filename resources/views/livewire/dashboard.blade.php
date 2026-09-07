<div>
    <div class="flex flex-wrap items-center gap-3 mb-6">
        <select wire:model.live="distributorId" class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand">
            <option value="">Semua Distributor</option>
            @foreach ($distributors as $d)
                <option value="{{ $d->id }}">{{ $d->name }}</option>
            @endforeach
        </select>
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari produk..."
               class="w-64 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand focus:border-brand">
        @if ($latestDate)
            <span class="text-xs text-gray-500 font-mono">Data terkini: {{ \Illuminate\Support\Carbon::parse($latestDate)->format('d M Y') }}</span>
        @endif
    </div>

    @if (! $latestDate)
        <div class="bg-white border border-[#e7e9e3] rounded-xl p-10 text-center text-gray-400 text-sm">
            Belum ada data stock. Silakan upload stock harian dulu.
        </div>
    @else
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
            <div class="bg-white border border-[#e7e9e3] rounded-xl p-4">
                <div class="text-[11px] font-mono uppercase tracking-wide text-gray-500">Distributor Aktif</div>
                <div class="text-2xl font-bold mt-1 tabular-nums">{{ $kpi['distributors'] }}</div>
            </div>
            <div class="bg-white border border-[#e7e9e3] rounded-xl p-4">
                <div class="text-[11px] font-mono uppercase tracking-wide text-gray-500">SKU Termonitor</div>
                <div class="text-2xl font-bold mt-1 tabular-nums">{{ $kpi['skus'] }}</div>
            </div>
            <div class="bg-white border border-[#e7e9e3] rounded-xl p-4">
                <div class="text-[11px] font-mono uppercase tracking-wide text-gray-500">Item Belum Ter-mapping</div>
                <div class="text-2xl font-bold mt-1 tabular-nums {{ $kpi['unmapped'] > 0 ? 'text-amber-600' : '' }}">{{ $kpi['unmapped'] }}</div>
            </div>
            <div class="bg-white border border-[#e7e9e3] rounded-xl p-4">
                <div class="text-[11px] font-mono uppercase tracking-wide text-gray-500">Batch Mendekati/Lewat ED</div>
                <div class="text-2xl font-bold mt-1 tabular-nums {{ $kpi['expiring_soon'] > 0 ? 'text-red-600' : '' }}">{{ $kpi['expiring_soon'] }}</div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
            <div class="lg:col-span-2 space-y-5">
                <div class="bg-white border border-[#e7e9e3] rounded-xl p-5">
                    <h3 class="text-sm font-semibold mb-3">Stock Terkini per Distributor × Produk</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="text-[11px] uppercase tracking-wide text-gray-500 font-mono border-b border-[#e7e9e3]">
                                <tr>
                                    <th class="text-left py-2">Distributor</th>
                                    <th class="text-left py-2">Produk</th>
                                    <th class="text-right py-2">Qty</th>
                                    <th class="text-right py-2">Δ vs sebelumnya</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#e7e9e3]">
                                @forelse ($stockTable->take(30) as $r)
                                    <tr>
                                        <td class="py-2">{{ $r->entry->distributor->name }}</td>
                                        <td class="py-2">
                                            {{ $r->entry->distributorItem->item_name }}
                                            @unless ($r->entry->distributorItem->isMapped())
                                                <span class="ml-1 text-[10px] font-mono uppercase bg-amber-100 text-amber-700 rounded-full px-1.5 py-0.5">belum ter-mapping</span>
                                            @endunless
                                        </td>
                                        <td class="py-2 text-right tabular-nums">{{ number_format($r->entry->quantity, 0) }}</td>
                                        <td class="py-2 text-right tabular-nums">
                                            @if ($r->delta_pct === null)
                                                <span class="text-gray-400">—</span>
                                            @elseif ($r->delta_pct > 0)
                                                <span class="text-brand">▲ {{ $r->delta_pct }}%</span>
                                            @elseif ($r->delta_pct < 0)
                                                <span class="text-red-600">▼ {{ abs($r->delta_pct) }}%</span>
                                            @else
                                                <span class="text-gray-400">0%</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="py-6 text-center text-gray-400">Tidak ada data.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="bg-white border border-[#e7e9e3] rounded-xl p-5">
                    <h3 class="text-sm font-semibold mb-3">Tren Total Stock ({{ count($trend['labels']) }} snapshot terakhir)</h3>
                    <canvas id="trend-chart" height="90"></canvas>
                </div>
            </div>

            <div class="space-y-5">
                <div class="bg-white border border-[#e7e9e3] rounded-xl p-5">
                    <h3 class="text-sm font-semibold mb-3">Alert Kadaluarsa</h3>
                    <div class="space-y-2">
                        @forelse ($expiryAlerts as $r)
                            @php
                                $status = $r->expiryStatus();
                                $badgeClass = match($status) {
                                    'expired' => 'bg-red-100 text-red-700',
                                    'critical' => 'bg-red-50 text-red-600',
                                    'warning' => 'bg-amber-100 text-amber-700',
                                    default => 'bg-brand-soft text-brand-dark',
                                };
                                $days = $r->daysToExpiry();
                            @endphp
                            <div class="flex items-center justify-between text-sm">
                                <div class="min-w-0">
                                    <div class="truncate">{{ $r->distributorItem->item_name }}</div>
                                    <div class="text-xs text-gray-400 font-mono">{{ $r->batch_no ?: '—' }}</div>
                                </div>
                                <span class="text-xs font-mono uppercase {{ $badgeClass }} rounded-full px-2 py-0.5 shrink-0">
                                    {{ $days < 0 ? 'lewat '.abs($days).'h' : $days.' hari' }}
                                </span>
                            </div>
                        @empty
                            <p class="text-sm text-gray-400">Tidak ada batch dengan ED tercatat.</p>
                        @endforelse
                    </div>
                </div>

                <div class="bg-white border border-[#e7e9e3] rounded-xl p-5">
                    <h3 class="text-sm font-semibold mb-3">Top Movers</h3>
                    <div class="space-y-2 text-sm">
                        @forelse ($topMovers as $r)
                            <div class="flex items-center justify-between">
                                <span class="truncate">{{ $r->entry->distributorItem->item_name }}</span>
                                <span class="{{ $r->delta_pct > 0 ? 'text-brand' : 'text-red-600' }} font-mono text-xs shrink-0">
                                    {{ $r->delta_pct > 0 ? '▲' : '▼' }} {{ abs($r->delta_pct) }}%
                                </span>
                            </div>
                        @empty
                            <p class="text-gray-400">Belum ada perbandingan snapshot.</p>
                        @endforelse
                    </div>
                </div>

                <div class="bg-white border border-[#e7e9e3] rounded-xl p-5">
                    <h3 class="text-sm font-semibold mb-3">Slow Moving (stagnan)</h3>
                    <div class="space-y-2 text-sm">
                        @forelse ($slowMovers as $r)
                            <div class="flex items-center justify-between">
                                <span class="truncate">{{ $r->entry->distributorItem->item_name }}</span>
                                <span class="text-gray-400 font-mono text-xs shrink-0">{{ number_format($r->entry->quantity, 0) }}</span>
                            </div>
                        @empty
                            <p class="text-gray-400">Tidak ada data.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    @endif

    @script
    <script>
        let trendData = @js($trend);
        let chartInstance = null;

        function renderChart() {
            const canvas = document.getElementById('trend-chart');
            if (!canvas || !window.Chart) return;
            if (chartInstance) chartInstance.destroy();
            chartInstance = new window.Chart(canvas, {
                type: 'line',
                data: {
                    labels: trendData.labels,
                    datasets: [{
                        label: 'Total Qty',
                        data: trendData.values,
                        borderColor: '#0d6d5f',
                        backgroundColor: 'rgba(13,109,95,0.12)',
                        fill: true,
                        tension: 0.3,
                        pointRadius: 3,
                    }],
                },
                options: {
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true } },
                },
            });
        }

        renderChart();

        // Re-draw after any Livewire update on this component (filter change, etc.)
        // so the trend chart stays in sync with fresh server-computed data.
        let dashboardComponentId = @this.__instance?.id ?? null;
        if (window.Livewire && dashboardComponentId) {
            window.Livewire.hook('morph.updated', ({ component }) => {
                if (component?.id === dashboardComponentId) {
                    trendData = @js($trend);
                    renderChart();
                }
            });
        }
    </script>
    @endscript
</div>
