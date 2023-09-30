<table>
    <thead>
        <tr>
            <th class="debug-bar-width6r">Durée</th>
            <th>Requête</th>
            <th>Lignes affectées</th>
        </tr>
    </thead>
    <tbody>
    {queries}
        <tr>
            <td class="narrow">{duration}</td>
            <td>{! sql !}</td>
            <td>{affected_rows}</td>
        </tr>
    {/queries}
    </tbody>
</table>
