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
    @isset($filters)
    var __INITIAL_STATE__ = {!! $filters->toJson() !!};
    @else
    var __INITIAL_STATE__ = {
        productTypes: [],
        styles: [],
        authors: [],
        authors_offsets: {},
        periods: [],
        materials: [],
        productionOrigins: [],
        dimensions: {
            max_height_or_thickness: 0,
            max_depth_or_width: 0,
            max_length_or_diameter: 0
        }
    };
    @endisset


        @isset($product)
            var PRODUCT = {!! json_encode($product) !!};
        @endisset

        @isset($mob_nat_selections, $user_selections)
            var SELECTIONS = {
                "mySelections": @json($my_selections),
                "mobNatSelections": @json($mob_nat_selections),
                "userSelections": @json($user_selections)
            };
        @endisset

        @isset($selection_detail)
            var SELECTION_DETAIL = {!! json_encode($selection_detail) !!};
        @endisset

        @isset($currentUser)
            var CURRENT_USER = {!! json_encode($currentUser) !!};
        @endisset

        @if (session('status'))
            var SESSION_STATUS = {!! json_encode(session('status')) !!};
        @endif

</script>

@stop