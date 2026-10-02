<div class="space-y-3">
    @if (session('status'))
        <x-alert tone="success">{{ session('status') }}</x-alert>
    @endif
    @if ($errors->any())
        <x-alert tone="danger">
            <ul class="space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </x-alert>
    @endif
    @if (session('temporary_passwords'))
        <x-alert tone="warning">
            <p class="font-medium">Accès créés. Ces mots de passe ne seront plus affichés.</p>
            <ul class="mt-2 space-y-1">
                @foreach (session('temporary_passwords') as $password)
                    <li>{{ $password }}</li>
                @endforeach
            </ul>
        </x-alert>
    @endif
</div>
