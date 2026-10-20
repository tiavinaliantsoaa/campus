@extends('layouts.app')
@section('title', 'Bulletin de notes')
@section('breadcrumb', 'Bulletin de notes')
@section('content')
    <x-page-header title="Bulletin de notes" :subtitle="$year->name" />

    @if (auth()->user()->hasPermission('academic-years.manage'))
        <x-card>
            <div class="border-b border-line px-5 py-4">
                <h2 class="font-semibold">Créer un semestre</h2>
            </div>
            <form method="POST" action="{{ route('bulletins.semesters.store') }}" class="grid gap-4 p-5 md:grid-cols-4">
                @csrf
                <x-field name="name" label="Intitulé" :value="old('name')" required />
                <x-field name="starts_on" type="date" label="Début" :value="old('starts_on')" required />
                <x-field name="ends_on" type="date" label="Fin" :value="old('ends_on')" required />
                <div class="flex items-end"><x-button>Créer le semestre</x-button></div>
            </form>
        </x-card>
    @endif

    <x-card>
        <form method="GET" action="{{ route('bulletins.index') }}" class="grid gap-4 p-5 md:grid-cols-3">
            <label class="space-y-1 text-sm"><span class="font-medium">Semestre</span>
                <select name="semester" class="w-full rounded-lg border border-line bg-white px-3 py-2" required>
                    <option value="">Choisir un semestre</option>
                    @foreach ($semesters as $semester)
                        <option value="{{ $semester->id }}" @selected($selectedSemester?->id === $semester->id)>{{ $semester->name }} · {{ $semester->starts_on->format('d/m/Y') }}–{{ $semester->ends_on->format('d/m/Y') }}</option>
                    @endforeach
                </select>
            </label>
            <label class="space-y-1 text-sm"><span class="font-medium">Groupe</span>
                <select name="group" class="w-full rounded-lg border border-line bg-white px-3 py-2" required>
                    <option value="">Choisir un groupe</option>
                    @foreach ($groups as $group)
                        <option value="{{ $group->id }}" @selected($selectedGroup?->id === $group->id)>{{ $group->name }}</option>
                    @endforeach
                </select>
            </label>
            <div class="flex items-end"><x-button type="submit" variant="secondary">Afficher</x-button></div>
        </form>
    </x-card>

    @if ($selectedSemester && $selectedGroup)
        <x-card>
            <div class="border-b border-line px-5 py-4">
                <h2 class="font-semibold">Modules prévus · {{ $selectedGroup->level->name }}</h2>
                <p class="mt-1 text-sm text-ink/55">Référence : {{ config('bulletins.source') }}. Les crédits absents du classeur restent non renseignés.</p>
                <p class="mt-1 text-sm text-ink/55">Une évaluation apparaît dans la colonne « Notes saisies » lorsque le nom de sa matière correspond à l’intitulé du module.</p>
                @if ($selectedGroup->level->code === 'B3')
                    <p class="mt-1 text-sm text-ink/55">La feuille B3 ne répartit pas ses modules par semestre et contient des parcours optionnels ; cette liste couvre l’année sans attribuer un parcours au groupe.</p>
                @endif
            </div>
            @if ($modules->isEmpty())
                <p class="px-5 py-8 text-sm text-ink/55">Aucun module du classeur ne correspond à ce niveau et à ce semestre.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead class="border-b border-line bg-canvas"><tr>
                            <th class="px-5 py-3 font-semibold">Unité d’enseignement</th>
                            <th class="px-4 py-3 font-semibold">Code</th>
                            <th class="px-4 py-3 font-semibold">Module</th>
                            <th class="px-4 py-3 font-semibold">Heures</th>
                            <th class="px-4 py-3 font-semibold">Crédits</th>
                            <th class="px-4 py-3 font-semibold">Évaluation prévue</th>
                            <th class="px-4 py-3 font-semibold">Notes saisies</th>
                        </tr></thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($modules as $module)
                                @php
                                    $linkedAssessments = $assessments->filter(
                                        fn ($assessment) => mb_strtolower(trim($assessment->subject->name)) === mb_strtolower($module['title'])
                                    );
                                @endphp
                                <tr>
                                    <td class="px-5 py-3 text-ink/65">{{ $module['unit'] ?? '—' }}</td>
                                    <td class="px-4 py-3">{{ $module['code'] ?? '—' }}</td>
                                    <th class="px-4 py-3 font-medium">{{ $module['title'] }}</th>
                                    <td class="px-4 py-3">{{ $module['hours'] }}</td>
                                    <td class="px-4 py-3">{{ $module['credits'] ?? '—' }}</td>
                                    <td class="px-4 py-3">{{ $module['evaluation'] ?? '—' }}</td>
                                    <td class="px-4 py-3">
                                        @forelse ($linkedAssessments as $assessment)
                                            <a class="block text-campus hover:underline" href="{{ route('assessments.entry', $assessment) }}">{{ $assessment->title }}</a>
                                        @empty
                                            <span class="text-ink/45">Aucune</span>
                                        @endforelse
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-card>
        <x-card>
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-5 py-4">
                <h2 class="font-semibold">{{ $selectedGroup->name }} · {{ $selectedSemester->name }}</h2>
                @if (auth()->user()->hasPermission('grades.enter'))
                    <x-button :href="route('assessments.create')">Nouvelle évaluation</x-button>
                @endif
            </div>
            <p class="border-b border-line px-5 py-3 text-sm text-ink/55">Notes enregistrées par évaluation. La maquette décrit les modules et certaines pondérations d’évaluation, mais ne définit pas la moyenne générale, l’arrondi ni les règles de validation.</p>
            @if ($enrollments->isEmpty())
                <p class="px-5 py-8 text-sm text-ink/55">Aucun étudiant accessible dans ce groupe.</p>
            @else
                @if ($assessments->isEmpty())
                    <p class="px-5 py-4 text-sm text-ink/55">Aucune évaluation pour ce semestre et ce groupe.</p>
                @endif
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead class="border-b border-line bg-canvas">
                            <tr>
                                <th class="sticky left-0 bg-canvas px-5 py-3 font-semibold">Étudiant</th>
                                @foreach ($assessments as $assessment)
                                    <th class="min-w-40 px-4 py-3 font-semibold">
                                        <a class="text-campus hover:underline" href="{{ route('assessments.entry', $assessment) }}">{{ $assessment->subject->name }}</a>
                                        <span class="block font-normal text-ink/55">{{ $assessment->title }} · /{{ $assessment->max_score }} · coeff. {{ $assessment->coefficient }}</span>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($enrollments as $enrollment)
                                <tr>
                                    <th class="sticky left-0 bg-white px-5 py-3 font-medium">
                                        <a class="text-campus hover:underline" href="{{ route('bulletins.index', ['semester' => $selectedSemester->id, 'group' => $selectedGroup->id, 'student' => $enrollment->student_id]) }}">{{ $enrollment->student->full_name }}</a>
                                    </th>
                                    @foreach ($assessments as $assessment)
                                        @php($grade = $assessment->grades->firstWhere('student_id', $enrollment->student_id))
                                        <td class="px-4 py-3">{{ $grade?->score === null ? 'Manquante' : $grade->score }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-card>
        @if ($selectedStudent)
            <x-card>
                <div class="border-b border-line px-5 py-4">
                    <h2 class="font-semibold">Détail des notes · {{ $selectedStudent->full_name }}</h2>
                    <p class="mt-1 text-sm text-ink/55">{{ $selectedSemester->name }} · {{ $selectedGroup->name }}</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead class="border-b border-line bg-canvas"><tr>
                            <th class="px-5 py-3 font-semibold">Module</th>
                            <th class="px-4 py-3 font-semibold">Évaluation</th>
                            <th class="px-4 py-3 font-semibold">Note</th>
                            <th class="px-4 py-3 font-semibold">Statut</th>
                        </tr></thead>
                        <tbody class="divide-y divide-line">
                            @forelse ($assessments as $assessment)
                                @php($grade = $assessment->grades->firstWhere('student_id', $selectedStudent->id))
                                <tr>
                                    <td class="px-5 py-3">{{ $assessment->subject->name }}</td>
                                    <td class="px-4 py-3">{{ $assessment->title }} · coeff. {{ $assessment->coefficient }}</td>
                                    <td class="px-4 py-3">{{ $grade?->score === null ? 'Manquante' : $grade->score.' / '.$assessment->max_score }}</td>
                                    <td class="px-4 py-3">{{ $assessment->status->label() }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-5 py-8 text-ink/55">Aucune évaluation saisie pour ce semestre.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-card>
        @endif
    @elseif ($semesters->isEmpty())
        <x-card><p class="px-5 py-8 text-sm text-ink/55">Aucun semestre créé pour cette année.</p></x-card>
    @endif
@endsection
