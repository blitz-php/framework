<table>
    <thead>
        <tr>
            <th class="debug-bar-width6r">Durée</th>
            <th>Nom de l'évenement</th>
            <th>Nombre d'exécution</th>
        </tr>
    </thead>
    <tbody>
    {events}
        <tr>
            <td class="narrow">{ duration } ms</td>
            <td>{event}</td>
            <td>{count}</td>
        </tr>
    {/events}
    </tbody>
</table>
