<?php
// import
if(rex_request('import', 'string') == "kufer_sync") {
	print "<fieldset style='padding: 1em; border: 1px solid #dfe3e9;'>";
	D2U_Courses\KuferSync::sync();
	print "</fieldset><br>";
}
if(trim(\rex_config::get('d2u_courses', 'kufer_sync_xml_url', '')) != '') {
	print "<fieldset style=' padding: 1em; border: 1px solid #dfe3e9;'>";
	print '<p>Der Kufer Import aktualisiert die durch Kufer importierten Kurse wie folgt:</p> 
		<ul>
			<li>Noch nicht vorhandene Kurse werden neu angelegt.</li>
			<li>Alte Kurse, die auch im Import vorhanden sind, werden aktualisiert.</li>
			<li>Alte Kurse, die nicht im Import vorhanden sind, werden gelöscht.</li>
		</ul>';
	
	print '<a href="'. rex_url::currentBackendPage(["import" => "kufer_sync"], false) .'"><button class="btn btn-save">Import starten</button></a>';
	print "</fieldset>";
}
else {
	print rex_view::error('In den <a href="'. rex_url::backendPage('d2u_courses/settings') .'">Einstellungen</a> ist noch keine URL des Kufer Exports angegeben.');
}