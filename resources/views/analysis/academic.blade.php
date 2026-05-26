@extends('layouts.app')

@section('content')
<div class="space-y-8 animate-fade-in">
    <!-- Page Header -->
    <div>
        <h1 class="text-3xl font-extrabold tracking-tight text-white uppercase bg-gradient-to-r from-teal-400 via-sky-400 to-indigo-400 bg-clip-text text-transparent">
            {{ __('messages.academic_title') ?? 'Academic & Age-wise Analysis' }}
        </h1>
        <p class="text-xs text-slate-400 font-medium tracking-wide uppercase mt-1">
            {{ __('messages.academic_subtitle') ?? 'Analyzing educational milestones and age-based trends to isolate key dropout stages.' }}
        </p>
    </div>

    <!-- Section 1: Standard-wise Trend -->
    <div class="glass-card p-6 rounded-2xl space-y-6 hover:border-white/10 transition duration-300">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 border-b border-white/5 pb-3">
            <h3 class="text-sm font-extrabold text-white uppercase tracking-wider">
                {{ __('messages.class_trendline') ?? 'Class-wise Dropout Trendline (Standard 1 to 12)' }}
            </h3>
            <span class="text-[9px] bg-rose-500/10 text-rose-450 border border-rose-500/20 px-2.5 py-0.5 rounded-lg font-bold uppercase tracking-wider animate-pulse">
                Critical Transition Period: Classes 8-10
            </span>
        </div>
        <div class="h-80 w-full relative">
            <canvas id="standardTrendChart"></canvas>
        </div>
    </div>

    <!-- Section 2: Age Distribution & School Levels -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Age wise dropouts -->
        <div class="glass-card p-6 rounded-2xl hover:border-white/10 transition duration-300 flex flex-col justify-between">
            <h3 class="text-sm font-extrabold text-white uppercase tracking-wider mb-4">
                {{ __('messages.age_distribution') ?? 'Dropout Distribution by Age Group' }}
            </h3>
            <div class="h-72 w-full relative">
                <canvas id="ageDistributionChart"></canvas>
            </div>
        </div>

        <!-- School Level Transition reasons -->
        <div class="glass-card p-6 rounded-2xl hover:border-white/10 transition duration-300 flex flex-col justify-between">
            <h3 class="text-sm font-extrabold text-white uppercase tracking-wider mb-4">
                {{ __('messages.transitional_shifts') ?? 'Transitional Shift in Dropout Factors' }}
            </h3>
            
            <div class="space-y-4 flex-1 overflow-y-auto mt-2 max-h-[300px] pr-1">
                @foreach(['Primary (1-5)' => ['color' => 'border-teal-500/10 bg-teal-500/5', 'text' => 'text-teal-400'],
                          'Middle (6-8)' => ['color' => 'border-sky-500/10 bg-sky-500/5', 'text' => 'text-sky-400'],
                          'Secondary (9-10)' => ['color' => 'border-amber-500/10 bg-amber-500/5', 'text' => 'text-amber-450'],
                          'Sr. Secondary (11-12)' => ['color' => 'border-rose-500/10 bg-rose-500/5', 'text' => 'text-rose-450']] as $lvl => $meta)
                    @php
                        $reasons = $transitionReasons->get($lvl, collect());
                    @endphp
                    <div class="p-4 rounded-xl border {{ $meta['color'] }} space-y-3 bg-slate-950/40 hover:bg-slate-950/60 hover:border-white/10 transition-all duration-300">
                        <div class="flex justify-between items-center">
                            <span class="font-extrabold text-xs uppercase tracking-widest {{ $meta['text'] }}">{{ $lvl }} Stage</span>
                            <span class="text-[9px] text-slate-500 font-extrabold uppercase tracking-wider">Top Drivers</span>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            @forelse($reasons->take(3) as $r)
                                <span class="bg-slate-900/60 text-slate-300 border border-white/5 px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider flex items-center gap-1.5 transition duration-150 hover:border-teal-500/30">
                                    {{ $r->dropout_reason }}
                                    <span class="text-slate-450 font-black">({{ $r->count }})</span>
                                </span>
                            @empty
                                <span class="text-[10px] text-slate-600 font-bold uppercase tracking-wider">No records found.</span>
                            @endforelse
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        // --- 1. Standard-wise Trendline ---
        const standardCtx = document.getElementById('standardTrendChart').getContext('2d');
        
        // Ensure keys from 1 to 12 exist, mapping 0 if missing
        const standardData = [];
        for (let i = 1; i <= 12; i++) {
            const data = {!! json_encode($standardStats) !!}[i];
            standardData.push(data ? data.rate : 0);
        }

        // Custom linear gradient for glowing area fill
        const gradTeal = standardCtx.createLinearGradient(0, 0, 0, 300);
        gradTeal.addColorStop(0, 'rgba(20, 184, 166, 0.45)'); // Teal
        gradTeal.addColorStop(1, 'rgba(20, 184, 166, 0.00)');

        new Chart(standardCtx, {
            type: 'line',
            data: {
                labels: ['Class 1', 'Class 2', 'Class 3', 'Class 4', 'Class 5', 'Class 6', 'Class 7', 'Class 8', 'Class 9', 'Class 10', 'Class 11', 'Class 12'],
                datasets: [{
                    label: 'Dropout Rate (%)',
                    data: standardData,
                    borderColor: '#14b8a6', // Teal
                    backgroundColor: gradTeal,
                    fill: true,
                    tension: 0.4,
                    borderWidth: 3,
                    pointBackgroundColor: '#14b8a6',
                    pointBorderColor: '#090d16',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 7,
                    pointHoverBorderWidth: 3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: '#94a3b8', font: { size: 9, weight: 'bold' } }
                    },
                    y: {
                        grid: { color: 'rgba(255, 255, 255, 0.03)' },
                        ticks: {
                            color: '#94a3b8',
                            font: { size: 9 },
                            callback: function(value) { return value + '%'; }
                        }
                    }
                },
                plugins: {
                    legend: { display: false },
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
                                return `Dropout Rate: ${context.parsed.y}%`;
                            }
                        }
                    }
                }
            }
        });

        // --- 2. Age-wise Distribution ---
        const ageCtx = document.getElementById('ageDistributionChart').getContext('2d');
        const ages = {!! json_encode($ageStats->keys()) !!};
        const ageRates = {!! json_encode($ageStats->pluck('rate')) !!};

        const gradIndigo = ageCtx.createLinearGradient(0, 0, 0, 300);
        gradIndigo.addColorStop(0, 'rgba(99, 102, 241, 0.85)'); // Indigo
        gradIndigo.addColorStop(1, 'rgba(99, 102, 241, 0.15)');

        new Chart(ageCtx, {
            type: 'bar',
            data: {
                labels: ages.map(a => a + ' Years'),
                datasets: [{
                    label: 'Dropout Rate (%)',
                    data: ageRates,
                    backgroundColor: gradIndigo,
                    borderColor: '#6366f1',
                    borderWidth: 1,
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: '#94a3b8', font: { size: 9, weight: 'bold' } }
                    },
                    y: {
                        grid: { color: 'rgba(255, 255, 255, 0.03)' },
                        ticks: {
                            color: '#94a3b8',
                            font: { size: 9 },
                            callback: function(value) { return value + '%'; }
                        }
                    }
                },
                plugins: {
                    legend: { display: false },
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
                                return `Dropout Rate: ${context.parsed.y}%`;
                            }
                        }
                    }
                }
            }
        });
    });
</script>
@endsection

