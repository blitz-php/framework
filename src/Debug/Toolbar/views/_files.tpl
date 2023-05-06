<table>
    <tbody>
        <tr>
            <td colspan="2" style="font-weight: bold; color:#DD4814">User Files ( {countUserFiles} )</td>
        </tr>
    {userFiles}
        <tr>
            <td>{name}</td>
            <td>{path}</td>
        </tr>
    {/userFiles}
        <tr>
            <td colspan="2" style="font-weight: bold; color:#DD4814">Vendor Files ( {countVendorFiles} )</td>
        </tr>
    {vendorFiles}
        <tr class="muted">
            <td class="debug-bar-width20e">{name}</td>
            <td>{path}</td>
        </tr>
    {/vendorFiles}
        <tr>
            <td colspan="2" style="font-weight: bold; color:#DD4814">System Files ( {countCoreFiles} )</td>
        </tr>
    {coreFiles}
        <tr class="muted">
            <td class="debug-bar-width20e">{name}</td>
            <td>{path}</td>
        </tr>
    {/coreFiles}
    </tbody>
</table>
