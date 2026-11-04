@extends('layouts.public')

@section('title', 'Créditos de imágenes | Rodamiento GDM')

@section('content')
<section class="container page-section narrow">
    <h1 class="page-title">Créditos de imágenes</h1>
    <p>Esta foto se tomó de internet con licencia libre. Las demás provienen del catálogo de Rodamiento GDM.</p>
    <ul class="credit-list">
        @foreach ($credits as $credit)
            @php($match = preg_match('/^CC ([A-Z-]+) ([\d.]+)/i', (string) $credit['license'], $parts))
            <li>
                <strong>{{ $credit['label'] }}</strong><br>
                Autor: {{ $credit['author'] }} ·
                @if ($match)
                    Licencia: <a href="https://creativecommons.org/licenses/{{ strtolower($parts[1]) }}/{{ $parts[2] }}/" rel="noopener license">{{ $credit['license'] }}</a>
                @else
                    Licencia: {{ $credit['license'] }}
                @endif
                @if ($credit['url'])
                    · <a href="{{ $credit['url'] }}" rel="noopener">Ver original</a>
                @endif
            </li>
        @endforeach
    </ul>
    <p>SKF y su logo son marcas de su titular. Se muestran para indicar la marca de las rolineras que vende Rodamiento GDM.</p>
</section>
@endsection
