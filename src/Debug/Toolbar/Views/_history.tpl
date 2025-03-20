<table>
    <thead>
        <tr>
            <th>Action</th>
            <th>Date et heure</th>
            <th>Statut</th>
            <th>Méthode</th>
            <th>URL</th>
            <th>Content-Type</th>
            <th>Requête AJAX?</th>
        </tr>
    </thead>
    <tbody>
    {files}
        <tr data-active="{active}">
            <td class="debug-bar-width70p">
            	<button class="blitzphp-history-load" data-time="{time}">Chargé</button>
            </td>
            <td class="debug-bar-width190p">{datetime}</td>
            <td>{status}</td>
            <td>{method}</td>
            <td>{url}</td>
            <td>{contentType}</td>
            <td>{isAJAX}</td>
        </tr>
    {/files}
    </tbody>
</table>
