@php
    $rows = array_values(array_filter($c['rows'], fn ($r) => $r['feature'] !== ''));
@endphp
<section id="{{ $anchor }}" class="sec bg-{{ $config['background'] }}">
    <div class="wrap">
        @include('sections._head')
        @if($rows)
            <div class="compare reveal">
                <table>
                    <thead>
                        <tr>
                            <th scope="col"></th>
                            <th scope="col" class="us">{{ $c['our_label'] }}</th>
                            <th scope="col">{{ $c['competitor_label'] }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rows as $row)
                            <tr>
                                <th scope="row">{{ $row['feature'] }}</th>
                                <td class="us"><span class="{{ $row['ours'] ? 'yes' : 'no' }}" aria-label="{{ $row['ours'] ? __('Yes') : __('No') }}">{{ $row['ours'] ? '✓' : '✕' }}</span></td>
                                <td><span class="{{ $row['competitor'] ? 'yes' : 'no' }}" aria-label="{{ $row['competitor'] ? __('Yes') : __('No') }}">{{ $row['competitor'] ? '✓' : '✕' }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</section>
