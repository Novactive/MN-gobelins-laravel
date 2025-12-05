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

    function loadFilters() {
        fetch('/api/filters')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network error: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    __INITIAL_STATE__ = data.data;
                    window.dispatchEvent(new CustomEvent('filtersLoaded', {
                        detail: { filters: data.data }
                    }));
                    if (window.app && window.app.forceUpdate) {
                        window.app.forceUpdate();
                    }
                    if (window.ReactDOM && window.ReactDOM.render) {
                        window.dispatchEvent(new Event('filtersUpdated'));
                    }
                    
                } else {
                    console.error('Error loading filters:', data.error);
                }
            })
            .catch(error => {
                console.error('AJAX Error:', error);
            });
    }
    document.addEventListener('DOMContentLoaded', function() {
        loadFilters();
    });

    @isset($product)
        var PRODUCT = {!! json_encode($product) !!};
    @endisset

    @isset($mob_nat_selections, $user_selections)
        var SELECTIONS = {
            "mySelections": @json($my_selections ?? []),
            "mobNatSelections": @json($mob_nat_selections ?? []),
            "userSelections": @json($user_selections ?? [])
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