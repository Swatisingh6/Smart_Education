@extends('layouts.app')

@section('content')
<div class="space-y-8 animate-fade-in">
    <!-- Page Header -->
    <div>
        <h1 class="text-3xl font-extrabold tracking-tight text-white uppercase bg-gradient-to-r from-sky-400 via-teal-400 to-indigo-400 bg-clip-text text-transparent">
            {{ __('messages.area_wise_title') ?? 'Area-wise Dropout Analysis' }}
        </h1>
        <p class="text-xs text-slate-400 font-medium tracking-wide uppercase mt-1">
            {{ __('messages.area_wise_subtitle') ?? 'Geographic distribution of dropout patterns, comparing Urban vs. Rural locales and tracking district metrics.' }}
        </p>
    </div>

    <!-- Urban vs Rural Deep Dive -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Rural vs Urban comparative card -->
        <div class="glass-card p-6 rounded-2xl hover:border-white/10 transition duration-300 lg:col-span-1 flex flex-col justify-between space-y-6">
            <div>
                <h3 class="text-sm font-extrabold text-white uppercase tracking-wider mb-2">
                    {{ __('messages.regional_vulnerabilities') ?? 'Regional Vulnerabilities' }}
                </h3>
                <p class="text-xs text-slate-400 leading-relaxed mt-2">
                    Rural settings face structural issues like transport scarcity and inadequate sanitation, whereas urban dropouts correlate with immediate child labor demands and migration.
                </p>
            </div>
            
            <div class="space-y-4">
                <div class="p-4 bg-slate-950/70 border border-white/5 rounded-xl transition duration-300 hover:border-white/10">
                    <span class="text-[10px] font-extrabold text-sky-400 uppercase tracking-widest block">{{ __('messages.urban_schools') ?? 'Urban Schools' }}</span>
                    @php
                        $urbanTotal = isset($areaData) ? $areaData->where('area_type', 'Urban')->sum('count') : 0;
                        $urbanDropouts = isset($areaData) ? ($areaData->where('area_type', 'Urban')->where('status', 'Dropped Out')->first()?->count ?? 0) : 0;
                        $urbanRate = $urbanTotal > 0 ? round(($urbanDropouts / $urbanTotal) * 100, 1) : 0;

                        $ruralTotal = isset($areaData) ? $areaData->where('area_type', 'Rural')->sum('count') : 0;
                        $ruralDropouts = isset($areaData) ? ($areaData->where('area_type', 'Rural')->where('status', 'Dropped Out')->first()?->count ?? 0) : 0;
                        $ruralRate = $ruralTotal > 0 ? round(($ruralDropouts / $ruralTotal) * 100, 1) : 0;
                    @endphp
                    <div class="flex items-baseline justify-between mt-1">
                        <span class="text-2xl font-black text-white tracking-tight">{{ $urbanRate }}%</span>
                        <span class="text-[10px] text-slate-500 font-bold">{{ number_format($urbanDropouts) }} of {{ number_format($urbanTotal) }} students</span>
                    </div>
                    <div class="mt-3 flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-sky-450 animate-ping"></span>
                        <p class="text-[10px] text-slate-400 font-medium">Primary driver: Poverty & Economic labor</p>
                    </div>
                </div>

                <div class="p-4 bg-slate-950/70 border border-white/5 rounded-xl transition duration-300 hover:border-white/10">
                    <span class="text-[10px] font-extrabold text-amber-400 uppercase tracking-widest block">{{ __('messages.rural_schools') ?? 'Rural Schools' }}</span>
                    <div class="flex items-baseline justify-between mt-1">
                        <span class="text-2xl font-black text-white tracking-tight">{{ $ruralRate }}%</span>
                        <span class="text-[10px] text-slate-500 font-bold">{{ number_format($ruralDropouts) }} of {{ number_format($ruralTotal) }} students</span>
                    </div>
                    <div class="mt-3 flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-450 animate-ping"></span>
                        <p class="text-[10px] text-slate-400 font-medium">Primary driver: School distance & Sanitation</p>
                    </div>
                </div>
            </div>

            <div class="text-[11px] bg-slate-950/60 p-3 rounded-xl border border-white/5 text-slate-450 flex items-start gap-2.5">
                <i class="fa-solid fa-lightbulb text-teal-400 mt-0.5 text-xs"></i>
                <div>
                    <span class="font-extrabold text-slate-300 uppercase tracking-wider text-[9px] block mb-0.5">Policy Suggestion</span>
                    Focus transport subsidies and clean water sanitation campaigns in Rural zones.
                </div>
            </div>
        </div>

        <!-- Regional reasons charts -->
        <div class="glass-card p-6 rounded-2xl hover:border-white/10 transition duration-300 lg:col-span-2 flex flex-col justify-between">
            <h3 class="text-sm font-extrabold text-white uppercase tracking-wider mb-4">Reason Comparison: Urban vs. Rural Dropouts</h3>
            <div class="h-80 w-full relative">
                <canvas id="areaReasonChart"></canvas>
            </div>
        </div>
    </div>

    <!-- District Leaderboard Grid -->
    <div class="glass-card p-6 rounded-2xl space-y-6 hover:border-white/10 transition duration-300">
        <div>
            <h3 class="text-sm font-extrabold text-white uppercase tracking-wider">
                {{ __('messages.district_leaderboard') ?? 'District Dropout Leaderboard' }}
            </h3>
            <p class="text-xs text-slate-400 font-medium tracking-wide uppercase mt-1">
                Socio-educational vulnerability and safety margins compared across jurisdictions.
            </p>
        </div>

        <!-- Grid of districts with glow effects -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($districtData as $district => $data)
                <div class="p-5 bg-slate-950/70 border border-white/5 rounded-2xl relative overflow-hidden group hover:border-white/10 hover:-translate-y-1 transition duration-300">
                    <!-- Colored top indicator -->
                    @if($data['rate'] >= 22)
                        <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-rose-500 to-pink-500 shadow-[0_1px_6px_rgba(244,63,94,0.4)]"></div>
                        <span class="absolute top-3 right-4 bg-rose-500/10 text-rose-450 border border-rose-500/20 text-[9px] px-2.5 py-0.5 rounded-lg font-bold uppercase tracking-wider animate-pulse">High Risk</span>
                    @elseif($data['rate'] >= 15)
                        <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-amber-500 to-orange-500 shadow-[0_1px_6px_rgba(245,158,11,0.4)]"></div>
                        <span class="absolute top-3 right-4 bg-amber-500/10 text-amber-450 border border-amber-500/20 text-[9px] px-2.5 py-0.5 rounded-lg font-bold uppercase tracking-wider">Moderate</span>
                    @else
                        <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-emerald-500 to-teal-500 shadow-[0_1px_6px_rgba(16,185,129,0.4)]"></div>
                        <span class="absolute top-3 right-4 bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 text-[9px] px-2.5 py-0.5 rounded-lg font-bold uppercase tracking-wider">Stable</span>
                    @endif

                    <div class="space-y-4 mt-2">
                        <div>
                            <span class="text-[9px] text-slate-500 uppercase tracking-widest font-extrabold block mb-0.5">District Area</span>
                            <h4 class="text-base font-bold text-white group-hover:text-teal-400 transition duration-200">{{ $district }}</h4>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <span class="text-[9px] text-slate-500 block uppercase font-extrabold tracking-wider">Total Stud.</span>
                                <span class="text-sm font-bold text-slate-300">{{ number_format($data['total']) }}</span>
                            </div>
                            <div>
                                <span class="text-[9px] text-slate-500 block uppercase font-extrabold tracking-wider">Dropouts</span>
                                <span class="text-sm font-bold text-rose-450">{{ number_format($data['dropouts']) }}</span>
                            </div>
                        </div>

                        <div class="space-y-1.5">
                            <div class="flex justify-between text-xs font-bold text-slate-300">
                                <span>Dropout Rate</span>
                                <span class="text-slate-100">{{ $data['rate'] }}%</span>
                            </div>
                            <div class="w-full bg-slate-900 h-2 rounded-full overflow-hidden border border-white/5">
                                <div class="h-full rounded-full bg-gradient-to-r {{ $data['rate'] >= 22 ? 'from-rose-500 to-pink-500 shadow-[0_0_8px_rgba(244,63,94,0.3)]' : ($data['rate'] >= 15 ? 'from-amber-500 to-orange-500 shadow-[0_0_8px_rgba(245,158,11,0.3)]' : 'from-emerald-500 to-teal-500 shadow-[0_0_8px_rgba(20,184,166,0.3)]') }}" 
                                     style="width: {{ $data['rate'] }}%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

<!-- Charts Config -->
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const areaReasonCtx = document.getElementById('areaReasonChart').getContext('2d');
        
        // Compile data from PHP
        @php
            $urbanReasons = $areaReasons->get('Urban', collect());
            $ruralReasons = $areaReasons->get('Rural', collect());
            
            // Get unique reasons
            $allReasons = $urbanReasons->pluck('dropout_reason')->concat($ruralReasons->pluck('dropout_reason'))->unique()->values()->toArray();
            
            $urbanCounts = [];
            $ruralCounts = [];
            
            foreach ($allReasons as $r) {
                $urbanCounts[] = $urbanReasons->where('dropout_reason', $r)->first()?->count ?? 0;
                $ruralCounts[] = $ruralReasons->where('dropout_reason', $r)->first()?->count ?? 0;
            }
        @endphp

        // Custom Glowing Gradients for Horizontal Bars
        const gradSky = areaReasonCtx.createLinearGradient(0, 0, 400, 0);
        gradSky.addColorStop(0, 'rgba(56, 189, 248, 0.85)'); // Sky blue
        gradSky.addColorStop(1, 'rgba(56, 189, 248, 0.15)');

        const gradAmber = areaReasonCtx.createLinearGradient(0, 0, 400, 0);
        gradAmber.addColorStop(0, 'rgba(251, 191, 36, 0.85)'); // Amber/Gold
        gradAmber.addColorStop(1, 'rgba(251, 191, 36, 0.15)');

        new Chart(areaReasonCtx, {
            type: 'bar',
            data: {
                labels: {!! json_encode($allReasons) !!},
                datasets: [
                    {
                        label: 'Urban Schools',
                        data: {!! json_encode($urbanCounts) !!},
                        backgroundColor: gradSky,
                        borderColor: '#38bdf8',
                        borderWidth: 1,
                        borderRadius: 4
                    },
                    {
                        label: 'Rural Schools',
                        data: {!! json_encode($ruralCounts) !!},
                        backgroundColor: gradAmber,
                        borderColor: '#fbbf24',
                        borderWidth: 1,
                        borderRadius: 4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y', // Horizontal bars
                scales: {
                    x: {
                        grid: { color: 'rgba(255, 255, 255, 0.03)' },
                        ticks: { color: '#94a3b8', font: { size: 9, weight: 'bold' } }
                    },
                    y: {
                        grid: { display: false },
                        ticks: { color: '#e2e8f0', font: { size: 9, weight: 'bold' } }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            color: '#94a3b8',
                            font: { size: 9, weight: 'bold' },
                            usePointStyle: true,
                            pointStyle: 'circle',
                            padding: 15
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.95)',
                        cornerRadius: 8,
                        borderColor: 'rgba(255, 255, 255, 0.08)',
                        borderWidth: 1,
                        padding: 12,
                        titleColor: '#fff',
                        titleFont: { size: 10, weight: 'bold' },
                        bodyColor: '#cbd5e1',
                        bodyFont: { size: 10 },
                        callbacks: {
                            label: function(context) {
                                return `${context.dataset.label}: ${context.parsed.x} dropouts`;
                            }
                        }
                    }
                }
            }
        });
    });
</script>
@endsection

