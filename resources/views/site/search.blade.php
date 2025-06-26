@extends('layouts.default')

@section('content')

@include('site._nav')

<div id="root">

    @isset($product)
    @include('site/_product')
    @else
    Chargement…
    @endisset

</div>

<script>
    var __INITIAL_STATE__ = @json($filters ?? []); // Celle-ci est déjà corrigée, parfait !


    @isset($product)
        var PRODUCT = @json($product); // <-- Modifiez ici
    @else
        var PRODUCT = {}; // Ajout d'une valeur par défaut si $product n'est pas défini
    @endisset

    @isset($mob_nat_selections, $user_selections)
        var SELECTIONS = {
            "mySelections": @json($my_selections),
            "mobNatSelections": @json($mob_nat_selections),
            "userSelections": @json($user_selections)
        };
    @else
        var SELECTIONS = {}; // Ajout d'une valeur par défaut si les sélections ne sont pas définies
    @endisset

    @isset($selection_detail)
        var SELECTION_DETAIL = @json($selection_detail); // <-- Modifiez ici
    @else
        var SELECTION_DETAIL = {}; // Ajout d'une valeur par défaut
    @endisset

    @isset($currentUser)
        var CURRENT_USER = @json($currentUser); // <-- Modifiez ici
    @else
        var CURRENT_USER = {}; // Ajout d'une valeur par défaut
    @endisset

    @if (session('status'))
        var SESSION_STATUS = @json(session('status')); // <-- Modifiez ici
    @else
        var SESSION_STATUS = null; // Valeur par défaut si pas de statut de session
    @endif

</script>

@stop
