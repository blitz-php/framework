<table>
    <tbody>
        <tr>
            <td colspan="2" style="font-weight: bold; color:#c2bb44">Fichiers de l'application ( {countUserFiles} )</td>
        </tr>
    {userFiles}
        <tr>
            <td>{name}</td>
            <td>{path}</td>
        </tr>
    {/userFiles}
        <tr>
            <td colspan="2" style="font-weight: bold; color:#c2bb44">Fichiers système ( {countCoreFiles} )</td>
        </tr>
    {coreFiles}
        <tr class="muted">
            <td class="debug-bar-width20e">{name}</td>
            <td>{path}</td>
        </tr>
    {/coreFiles}
        <tr>
            <td colspan="2" style="font-weight: bold; color:#c2bb44">Fichiers des composants BlitzPHP ( {countBlitzFiles} )</td>
        </tr>
    {blitzFiles}
        <tr class="muted">
            <td class="debug-bar-width20e">{name}</td>
            <td>{path}</td>
        </tr>
    {/blitzFiles}
        <tr>
            <td colspan="2" style="font-weight: bold; color:#c2bb44">Fichiers des packages ( {countVendorFiles} )</td>
        </tr>
    {vendorFiles}
        <tr class="muted">
            <td class="debug-bar-width20e">{name}</td>
            <td>{path}</td>
        </tr>
    {/vendorFiles}
    </tbody>
</table>
