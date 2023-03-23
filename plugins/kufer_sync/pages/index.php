<?php

// import
if ('kufer_sync' === rex_request('import', 'string')) {
    echo "<fieldset style='padding: 1em; border: 1px solid #dfe3e9;'>";
    D2U_Courses\KuferSync::sync();
    echo '</fieldset><br>';
}
if ('' !== trim((string) \rex_config::get('d2u_courses', 'kufer_sync_xml_url', ''))) {
    echo "<fieldset style=' padding: 1em; border: 1px solid #dfe3e9;'>";
    echo '<p>Der Kufer Import aktualisiert die durch Kufer importierten Kurse wie folgt:</p>
		<ul>
			<li>Noch nicht vorhandene Kurse werden neu angelegt.</li>
			<li>Alte Kurse, die auch im Import vorhanden sind, werden aktualisiert.</li>
			<li>Alte Kurse, die nicht im Import vorhanden sind, werden gelöscht.</li>
		</ul>';

    echo '<a href="'. rex_url::currentBackendPage(['import' => 'kufer_sync'], false) .'"><button class="btn btn-save">Import starten</button></a>';
    echo '</fieldset>';
} else {
    echo rex_view::error('In den <a href="'. rex_url::backendPage('d2u_courses/settings') .'">Einstellungen</a> ist noch keine URL des Kufer Exports angegeben.');
}
