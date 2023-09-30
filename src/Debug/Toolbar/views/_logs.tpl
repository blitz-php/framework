{ if $logs == [] }
<p>Rien n'a été enregistré. Si vous attendiez des éléments enregistrés, assurez-vous que le fichier app/Config/log.php a le seuil correct.</p>
{ else }
<table>
    <thead>
        <tr>
            <th>Gravité</th>
            <th>Message</th>
        </tr>
    </thead>
    <tbody>
    {logs}
        <tr>
            <td>{level}</td>
            <td>{msg}</td>
        </tr>
    {/logs}
    </tbody>
</table>
{ endif }
