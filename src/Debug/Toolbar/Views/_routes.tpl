<h3>Configurations</h3>

<table>
    <tbody>
        <tr>
            <td>Routage auto:</td>
            <td>{autoRoute}</td>
        </tr>
    </tbody>
</table>

<h3>Route assortis</h3>

<table>
    <tbody>
    {matchedRoute}
        <tr>
            <td>Répertoire:</td>
            <td>{directory}</td>
        </tr>
        <tr>
            <td>Contrôleur:</td>
            <td>{controller}</td>
        </tr>
        <tr>
            <td>Méthode:</td>
            <td>{method}</td>
        </tr>
        <tr>
            <td>Paramètres:</td>
            <td>{paramCount} / {truePCount}</td>
        </tr>
        {params}
            <tr class="route-params-item">
                <td>{name}</td>
                <td>{value}</td>
            </tr>
        {/params}
    {/matchedRoute}
    </tbody>
</table>


<h3>Routes définies</h3>

<table>
    <thead>
        <tr>
            <th>Méthode</th>
            <th>Route</th>
            <th>Nom</th>
            <th>Gestionnaire</th>
        </tr>
    </thead>
    <tbody>
    {routes}
        <tr>
            <td>{method}</td>
            <td data-debugbar-route="{method}">{route}</td>
            <td>{name}</td>
            <td>{handler}</td>
        </tr>
    {/routes}
    </tbody>
</table>
