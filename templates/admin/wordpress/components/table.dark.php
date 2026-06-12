<table class="wp-list-table widefat fixed striped posts">
    <thead>
        <tr>
            @foreach($columns as $slug => $label)
                <th scope="col" id="{{ $slug }}" class="manage-column column-{{ $slug }}">
                    {{ $label }}
                </th>
            @endforeach
        </tr>
    </thead>
    <tbody id="the-list">
        @foreach($items as $item)
            <tr>
                @foreach($columns as $slug => $label)
                    <td class="{{ $slug }} column-{{ $slug }}">
                        {{ $item[$slug] ?? '' }}
                    </td>
                @endforeach
            </tr>
        @endforeach
    </tbody>
</table>
