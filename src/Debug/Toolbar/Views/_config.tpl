<p class="debug-bar-alignRight">
	<a href="https://github.com/blitz-php/framework" target="_blank" >Lisez la docs de BlitzPHP...</a>
</p>

<table>
	<tbody>
		<tr>
			<td>Version BlitzPHP:</td>
			<td>{ blitzVersion }</td>
		</tr>
		<tr>
			<td>Version PHP:</td>
			<td>{ phpVersion }</td>
		</tr>
		<tr>
			<td>Serveur:</td>
			<td>{ serverVersion }</td>
		</tr>
		<tr>
			<td>OS:</td>
			<td>{ os }</td>
		</tr>
		<tr>
			<td>PHP SAPI:</td>
			<td>{ phpSAPI }</td>
		</tr>
		<tr>
			<td>Environement:</td>
			<td>{ environment }</td>
		</tr>
		<tr>
			<td>URL de base:</td>
			<td>
				{ if $baseURL == '' }
					<div class="warning">
						Le $baseURL doit toujours être défini manuellement pour empêcher la personnification d'URL possible des parties externes.
					</div>
				{ else }
					{ baseURL }
				{ endif }
			</td>
		</tr>
		<tr>
			<td>Document Root:</td>
			<td>{ documentRoot }</td>
		</tr>
		<tr>
			<td>Timezone:</td>
			<td>{ timezone }</td>
		</tr>
		<tr>
			<td>Locale utilisée:</td>
			<td>{ locale }</td>
		</tr>
	</tbody>
</table>
