<table>
    <thead>
        <tr>
            <th>NOMBRE</th>
            <th>STATUS INTERESES</th>
            <th>INTERESES</th>
            <th>STATUS TALENTOS</th>
            <th>TALENTOS</th>
            <th>STATUS TEMPERAMENTOS</th>
            <th>TEMPERAMENTOS</th>
        </tr>
    </thead>
    <tbody>
    @foreach($personas as $p)
        <tr>
            <td>{{ $p->nombres }} {{ $p->apellido_paterno }} {{ $p->apellido_materno }}</td>
            <td>{{ $p->status_int }}</td>
            <td>{{ $p->link_intereses }}</td>
            <td></td>
            <td></td>
            <td>{{ $p->status_temp }}</td>
            <td>{{ $p->link_temperamentos }}</td>
        </tr>
      @endforeach
    </tbody>
</table>