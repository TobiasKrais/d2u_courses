<h2>Changelog</h2>
<p>3.6.1-DEV:</p>
<ul>
	<li>Backend: CSRF-Schutz fuer Speichern-, Loesch- und Statusaktionen der Kursverwaltung ergaenzt.</li>
	<li>Module 26-1 und 26-4 verwenden fuer die optionale News-Anbindung jetzt den neuen Namespace <code>TobiasKrais\D2UNews</code>.</li>
	<li>Kompatibilitaet zu d2u_news ab Version 1.2.0 als Paketkonflikt hinterlegt.</li>
</ul>
<p>3.6.0:</p>
<ul>
	<li>Wichtige Hinweise
		<ul>
			<li>Die Klassen stehen jetzt im Namespace <code>TobiasKrais\D2UCourses</code> zur Verfügung. Der bisherige Namespace <code>D2U_Courses</code>
				und die alten globalen Klassennamen sind nur noch als deprecated Übergangsschicht vorhanden und werden mit Version 4.0.0 entfernt.</li>
			<li>Die deprecated Übergangsschicht wird über <code>lib/deprecated_classes.php</code> bereitgestellt.</li>
			<li>Folgende bisherigen Klassen werden dort exakt als Übergangsschicht bereitgestellt:
				<ul>
					<li><code>D2U_Courses\Cart</code> wird zu <code>TobiasKrais\D2UCourses\Cart</code>.</li>
					<li><code>D2U_Courses\Category</code> wird zu <code>TobiasKrais\D2UCourses\Category</code>.</li>
					<li><code>D2U_Courses\Course</code> wird zu <code>TobiasKrais\D2UCourses\Course</code>.</li>
					<li><code>D2U_Courses\CustomerBooking</code> wird zu <code>TobiasKrais\D2UCourses\CustomerBooking</code>.</li>
					<li><code>D2U_Courses\FrontendHelper</code> wird zu <code>TobiasKrais\D2UCourses\FrontendHelper</code>.</li>
					<li><code>D2U_Courses\KuferSync</code> wird zu <code>TobiasKrais\D2UCourses\KuferSync</code>.</li>
					<li><code>D2U_Courses\Location</code> wird zu <code>TobiasKrais\D2UCourses\Location</code>.</li>
					<li><code>D2U_Courses\LocationCategory</code> wird zu <code>TobiasKrais\D2UCourses\LocationCategory</code>.</li>
					<li><code>D2U_Courses\ScheduleCategory</code> wird zu <code>TobiasKrais\D2UCourses\ScheduleCategory</code>.</li>
					<li><code>D2U_Courses\TargetGroup</code> wird zu <code>TobiasKrais\D2UCourses\TargetGroup</code>.</li>
					<li><code>d2u_courses_frontend_helper</code> wird zu <code>TobiasKrais\D2UCourses\FrontendHelper</code>.</li>
					<li><code>d2u_courses_lang_helper</code> wird zu <code>TobiasKrais\D2UCourses\LangHelper</code>.</li>
					<li><code>D2UCoursesModules</code> wird zu <code>TobiasKrais\D2UCourses\Module</code>.</li>
				</ul>
			</li>
		</ul>
	</li>
	<li>Neue Module 26-4 bis 26-6 als Bootstrap-5-Varianten der bestehenden Beispielmodule hinzugefügt.</li>
	<li>Module 26-1 bis 26-3 als "(BS4, deprecated)" markiert. Die BS4-Varianten werden im nächsten Major Release entfernt.</li>
	<li>Farbfelder im gesamten Addon um Light-/Dark-Mode-Paare erweitert, inklusive Kategorien und Frontend-Ausgabe der Beispielmodule.</li>
	<li>Plugin-Versionen der Erweiterungs-Shims auf 3.6.0 angehoben.</li>
	<li>Bugfix: Prioritäten werden bei Kategorien sowie bei Termin- und Zielgruppen nach dem Speichern wieder stabil neu durchnummeriert, auch wenn in der Datenbank bereits doppelte Werte vorhanden sind.</li>
	<li>Backend-Listen im Addon und in den Plugins sortierbar gemacht und Standardsortierungen von SQL-Queries auf <code>rex_list</code>-<code>defaultSort</code> umgestellt.</li>
	<li>Die Priorität von Kategorien sowie von Termin- und Zielgruppen kann in den Backend-Listen jetzt direkt per Hoch-/Runter-Buttons geändert werden.</li>
	<li>Bugfix: Module wurden beim Update nicht korrekt aktualisiert.</li>
	<li>Bugfix: Es war möglich einen Teilnehmer mehr als die maximale Teilnehmeranzahl anzumelden.</li>
	<li>Buchungs Plugin: PHP Warnung im CSV Export verhindert.</li>
</ul>
<p>3.5.1:</p>
<ul>
	<li>Bugfix: Wenn das Buchungsdaten Plugin aktiviert ist, werden die Anzahl der Teilnehmer und Warteliste berechnet. Diese Felder werden somit ausgeblendet.</li>
	<li>Modul 26-1 "Ausgabe Veranstaltungen": Ausgabe Hinweis, wenn gar keine Angebote verfügbar sind.</li>
</ul>
<p>3.5.0:</p>
<ul>
	<li>Vorbereitung auf R6: Folgende Klassen werden ab Version 4 dieses Addons umbenannt. Schon jetzt stehen die neuen Klassen für die Übergangszeit zur Verfügung:
		<ul>
			<li><code>d2u_courses_frontend_helper</code> wird zu <code>TobiasKrais\D2UCourses\FrontendHelper</code>.</li>
			<li><code>D2U_Courses\Cart</code> wird zu <code>TobiasKrais\D2UCourses\Cart</code>.</li>
			<li><code>D2U_Courses\Category</code> wird zu <code>TobiasKrais\D2UCourses\Category</code>.</li>
			<li><code>D2U_Courses\Course</code> wird zu <code>TobiasKrais\D2UCourses\Course</code>.</li>
			<li><code>D2U_Courses\CustomerBooking</code> wird zu <code>TobiasKrais\D2UCourses\CustomerBooking</code>.</li>
			<li><code>D2U_Courses\KuferSync</code> wird zu <code>TobiasKrais\D2UCourses\KuferSync</code>.</li>
			<li><code>D2U_Courses\Location</code> wird zu <code>TobiasKrais\D2UCourses\Location</code>.</li>
			<li><code>D2U_Courses\LocationCategory</code> wird zu <code>TobiasKrais\D2UCourses\LocationCategory</code>.</li>
			<li><code>D2U_Courses\ScheduleCategory</code> wird zu <code>TobiasKrais\D2UCourses\ScheduleCategory</code>.</li>
			<li><code>D2U_Courses\TargetGroup</code> wird zu <code>TobiasKrais\D2UCourses\TargetGroup</code>.</li>
		</ul>
		Folgende interne Klassen wurden wurden ebenfalls umbenannt. Hier gibt es keine Übergangszeit, da sie nicht öffentlich sind:
		<ul>
			<li><code>d2u_courses_lang_helper</code> wird zu <code>TobiasKrais\D2UCourses\LangHelper</code>.</li>
			<li><code>D2UCoursesModules</code> wird zu <code>TobiasKrais\D2UCourses\Module</code>.</li>
			<li><code>kufer_sync_cronjob</code> wird zu <code>TobiasKrais\D2UCourses\KuferSyncCronjob</code>.</li>
		</ul>
	</li>
	<li>Feld Preis Hinweise hinzugefügt.</li>
	<li>Anpassungen Kufer Sync.</li>
	<li>Support für Geolocation 1.x entfernt. Bitte auf Geolocation 2.x updaten.</li>
	<li>Bugfix: Veranstaltung löschen ohne installiertes Buchungsdaten Plugin nicht mehr möglich.</li>
	<li>Bugfix Buchungsdaten Plugin: Export für Schweizer Bundesamt für Sport BASPO korrigiert.</li>
	<li>Bugfix Buchungsdaten Plugin: Geschlecht wurde bei Änderungen nicht korrekt gespeichert.</li>
	<li>Modul 26-1 "Ausgabe Veranstaltungen": Roter Button bei ausgebucht wird nun auch angezeigt, wenn die maximale Teilnehmeranzahl erreicht ist.</li>
	<li>Modul 26-1 "Ausgabe Veranstaltungen": Unterstützt nur noch Geolocation Addon Version 2.x (Wegfall Unterstützung Version 1.x).</li>
	<li>Modul 26-2 "Warenkorb": Bei Kufer Anmeldungen werden die Anzahl Teilnehmer / Personen auf der Warteliste automatisiert hochgezählt.</li>
	<li>Alle Module auf neuen Namespace und Klassennamen angepasst.</li>
	<li>Anpassungen für verbesserte Barrierefreiheit.</li>
</ul>
<p>3.4.1:</p>
<ul>
	<li>Modul 26-1 "Ausgabe Veranstaltungen": Auswahl der Preisstufe wird ins Anmeldeformular übernommen.</li>
	<li>Modul 26-2 "Warenkorb": Link bei Fehlermeldung entfernt.</li>
	<li>Modul 26-2 "Warenkorb": Mindestalter in Anmeldeformular aufgehoben.</li>
	<li>Modul 26-2 "Warenkorb": Altersabfrage mit Begrenzung auf aussgewählte Kategorien funktionierte nicht korrekt.</li>
	<li>Bugfix: In den Einstellungen wurden die ausgewählten MultiNewsletter Gruppen nach dem Speichern nicht korrekt angezeigt.</li>
	<li>Buchungsdaten Plugin: Buchungen können nun geklont werden.</li>
	<li>Buchungsdaten Plugin: Felder bezahlt, Warteliste und interne Bemerkungen hinzugefügt.</li>
	<li>Bugfix Buchungsdaten Plugin: Beim manuellen hinzufügen von Buchungen konnte nicht gespeichert werden wenn der Kurs kein gehaltsabhängiges Preismodell hatte.</li>
	<li>Bugfix Buchungsdaten Plugin: Export erzwingt nun UTF-8 als Format.</li>
	<li>Bugfix Buchungsdaten Plugin: Warnung im Warenkorb entfernt.</li>
	<li>Bugfix Kufer Import Plugin: Fehlerausgabe beim Import verbessert.</li>
</ul>
<p>3.4.0:</p>
<ul>
	<li>Plugin Buchungsdaten hinzugefügt. Dieses Plugin ermöglicht die Erfassung und den Export von Teilnehmerdaten.</li>
	<li>Methode Course::existCoursesForCart() hinzugefügt, um zu prüfen ob überhaupt Veranstaltungen mit Anmeldeoption existieren.</li>
	<li>locations Plugin: Weiterleitungsoption in den Einstellungen hinzugefügt, wenn nur ein Ort in einer Ortskategorie vorhanden ist.</li>
	<li>Option für eine gewerbliche Anmeldung in den Einstellungen hinzugefügt.</li>
	<li>Modul 26-1 "Ausgabe Veranstaltungen": automatische Weiterleitung bei einem Kurs in einer Kategorie hatte nicht funktioniert und Warnung beseitigt.</li>
	<li>Modul 26-2 "Warenkorb": Bugfix: Löschen des zweiten Teilnehmers hatte komplette Veranstaltung aus Warenkorb gelöscht.</li>
	<li>Modul 26-2 "Warenkorb": Weitere optionale Felder für Teilnehmerdaten hinzugefügt.</li>
	<li>Bugfix: Ausgabe im JSON Format kodiert nun einfache Anführungszeichen korrekt.</li>
	<li>Bugfix: wenn ein Artikellink entfernt wurde, gab es beim Speichern einen Fehler.</li>
</ul>
<p>3.3.3:</p>
<ul>
	<li>README / Hilfe hinzugefügt.</li>
	<li>Modul 26-1 "Ausgabe Veranstaltungen": Kann nun auch Karten des Geolocation Addon Version 2 nutzen.</li>
</ul>
<p>3.3.2:</p>
<ul>
	<li>Bugfix: Speichern von Kursen endete wegen fehlender Wertumwandlung in Fehler.</li>
</ul>
<p>3.3.1:</p>
<ul>
	<li>Bugfix: Verzeichniserstellung für Kufer Anmeldungen mit PHP < 8.1 war nicht möglich.</li>
	<li>Bugfix: Aufruf der Einstellungen bei Neuinstallation führte zu einem Fehler.</li>
	<li>Bugfix locations Plugin: Fehler beim Speichern behoben.</li>
</ul>
<p>3.3.0:</p>
<ul>
	<li>PHP-CS-Fixer Code Verbesserungen.</li>
	<li>.github Verzeichnis aus Installer Action ausgeschlossen.</li>
	<li>rexstan Anpassungen</li>
	<li>Modul 26-1 "Ausgabe Veranstaltungen": Geolocation 1.x als Kartenaddon wird ab sofort unterstützt.</li>
	<li>Modul 26-1 "Ausgabe Veranstaltungen": "Zum Warenkorb hinzufügen" statt Grau in der Farbe der Veranstaltung.</li>
	<li>Modul 26-2 "Warenkorb": Fehlermeldung, wenn ein Newsletter aus dem MultiNewsletter angeboten wurde behoben.</li>
	<li>Modul 26-2 "Warenkorb": Feld für Firma hinzugefügt. In den Einstellungen kann festgelegt werden, dass Bestellungen von Firmen immer die Zahlungsoption Überweisung anbietet, auch wenn die Option im entsprechenden Feld nicht gewählt ist.</li>
	<li>Modul 26-2 "Warenkorb": IBAN Prüfung und Warnhinweis auf mögliche Kosten, wenn bei Zahlungsoption "SEPA Lastschrift" eine Kontonummer außerhalb des SEPA Raumes eingegeben wird.</li>
	<li>Modul 26-2 "Warenkorb": Eingabefeld für Land hinzugefügt und Postleitzahlfeld angepasst.</li>
	<li>Modul 26-2 "Warenkorb": E-Mail Verifizierungsfeld hinzugefügt.</li>
	<li>Modul 26-2 "Warenkorb": Wenn Altersabfrage aktiviert ist, wird zusätzlich nach einer Notfallnummer gefragt.</li>
</ul>
<p>3.2.4:</p>
<ul>
	<li>Bugfix: wenn nur Teilnehmerzahl erfragt wurde, war in Bestätigungsmail die Anzahl und der Gesamtpreis nicht korrekt angezeigt.</li>
	<li>Bugfix: Installation ohne vorheriges Update schlug fehl.</li>
	<li>Modul 26-1 "Ausgabe Veranstaltungen": nun auch 4 Boxen nebeneinander möglich.</li>
	<li>Modul 26-3 "Ausgabe Veranstaltungen einer Kategorie in Boxen": nun auch 4 Boxen nebeneinander möglich.</li>
</ul>
<p>3.2.3:</p>
<ul>
	<li>Anpassungen an Publish Github Release to Redaxo.</li>
	<li>Unterstützt nur noch URL Addon >= 2.0.</li>
	<li>Bugfix: Anzeige der Preisstufenbeschreibung bei gleichen Preisen nicht immer korrekt.</li>
	<li>Bugfix: Beim Löschen von Artikeln und Medien die vom Addon verlinkt werden wurde der Name der verlinkenden Quelle in der Warnmeldung nicht immer korrekt angegeben.</li>
	<li>Modul 26-1 "Ausgabe Veranstaltungen": Anzeige der Preisstufenbeschreibung bei gleichen Preisen nicht immer korrekt.</li>
	<li>Modul 26-2 "Warenkorb": Anzeige der Preisstufenbeschreibung bei gleichen Preisen nicht immer korrekt.</li>
</ul>
<p>3.2.2:</p>
<ul>
	<li>Methode d2u_courses_frontend_helper::getMetaTags() entfernt, da das URL Addon eine bessere Funktion anbietet.
		Ebenso die Methoden getMetaAlternateHreflangTags(), getMetaDescriptionTag(), getCanonicalTag und getTitleTag() der aller Klassen, die diese Methoden angeboten hatten.</li>
	<li>Bei der Kategorieeingabe konnten Kinder- und Enkelkategorien sich selbst als Elternkategorie zuweisen, was zu einer Weiterleitungsschleife geführt hatte.</li>
	<li>Einkommensbasiertes Preismodell hinzugefügt.</li>
	<li>install.php und update.php modernisiert und vereinfacht.</li>
	<li>Modul 26-1 "Ausgabe Veranstaltungen": Einkommensbasiertes Preismodell hinzugefügt.</li>
	<li>Modul 26-2 "Warenkorb": Fatal Error bei bestimmter Kombination von Einstellungen im Modul behoben, einkommensbasiertes Preismodell hinzugefügt und Anpassungen für Mobilgeräte.</li>
</ul>
<p>3.2.1:</p>
<ul>
	<li>Modul 26-1 "Ausgabe Veranstaltungen" Usability: In Liste wird "ausgebucht" angezeigt, wenn Veranstaltung ausgebucht ist.</li>
	<li>Modul 26-1 "Ausgabe Veranstaltungen": es steht nun auch eine OpenStreetMap Karte zur Verfügung.</li>
	<li>Modul 26-2 "Warenkorb": Es können nun mehrere Newsletter bestellt werden. Die angebotenen Newsletter können in den Einstellungen angepasst werden.</li>
	<li>Modul 26-2 "Warenkorb": Es kann die Frage nach dem Geschlecht der Kursteilnehmer deaktiviert werden.</li>
	<li>Modul 26-2 "Warenkorb": PHP Warnung entfernt.</li>
	<li>locations Plugin: Redaxo Nutzer für die Rechtevergabe sind nun alphabetisch sortiert.</li>
	<li>locations /kufer_sync Plugin: Zuordnung zu Kufer Ort ID möglich.</li>
	<li>In den Einstellungen gibt es neu ein Textfeld für einen Text in der Bestätigungsmail.</li>
	<li>Bestätigungsmail enthält nun auch den Preis, das Datum und die Uhrzeit der Veranstaltung.</li>
	<li>Bugfix: in einigen Fällen wurde der Redaxo Artikel in den Einstellungen nicht korrekt gespeichert.</li>
	<li>Bugfix: in einigen Fällen konnten mehrere gleiche Kategorien nebeneinander ausgegeben werden.</li>
	<li>Erste FAQs hinzugefügt.</li>
</ul>
<p>3.2.0:</p>
<ul>
	<li>Bugfix beim Speichern von Kursen deren Gebühren über 999,- € lagen.</li>
	<li>Bugfix beim Update des Addons und aktiviertem Autoupdate der Beispielmodule gingen die Moduldaten verloren.</li>
	<li>Kurse können nun im JSON+LD Format für das Kurskarussell in der Google Suche oder für Events in Google Maps ausgegeben werden. Für die Nutzung von Events wird das locations Plugin installiert und aktiviert sein.</li>
	<li>Modul 26-1 "Ausgabe Veranstaltungen": Bugfix. Kursdetailansicht zeigt nun korrektes Enddatum an.</li>
	<li>Modul 26-1 "Ausgabe Veranstaltungen": Bugfix. Plugin wurde nicht immer korrekt auf Vorhandensein geprüft.</li>
	<li>Modul 26-1 "Ausgabe Veranstaltungen": Gibt Kurs nun auch im JSON+LD Format für Google aus.</li>
	<li>Modul 26-2 "Warenkorb": Anrede wird abgefragt.</li>
</ul>
<p>3.1.0:</p>
<ul>
	<li>Benötigt Redaxo >= 5.10, da die neue Klasse rex_version verwendet wird.</li>
	<li>Klonen von Kategorien möglich.</li>
	<li>Aktualisiert beim Speichern automatisch den search_it index.</li>
	<li>Bugfix: beim Reinstallieren des Addons wurden die Einstellungen wie lange ein Kurs angezeigt werden soll überschrieben.</li>
	<li>Bugfix: Fehler beim Speichern von Örtlichkeiten und deren Kategorien behoben.</li>
	<li>Bugfix kufer_import Plugin: Import berücksichtigt nun auch Einstellungen wie lange Kurse angezeigt werden.</li>
	<li>Elternkategorien können nun Großeltern- und Urgroßelternkategorien haben. Damit ist eine Kategorietiefe von 4 möglich.</li>
	<li>Modul 26-1 "D2U Veranstaltungen - Ausgabe Veranstaltungen" leitet Offlinekurse auf die Fehlerseite weiter.</li>
	<li>Modul 26-1 "D2U Veranstaltungen - Ausgabe Veranstaltungen" gibt im Plugin target_groups nun auch die Beschreibung einer Kategorie aus.</li>
	<li>Modul 26-1 "D2U Veranstaltungen - Ausgabe Veranstaltungen" hatte unter bestimmten Umständen Backendseite ins Frontend weitergeleitet.</li>
	<li>Modul 26-1 "D2U Veranstaltungen - Ausgabe Veranstaltungen" kann optional auf der Startseite News aus dem D2U News Addon und Linkboxen aus dem D2U Linkbox Addon einbinden.</li>
	<li>Modul 26-1 "D2U Veranstaltungen - Ausgabe Veranstaltungen" Veranstaltungslisten zeigen nun einheitlich das Datum mit Uhrzeit, bzw. als Ersatz die Kurzbeschreibung.</li>
	<li>Modul 26-2 "D2U Veranstaltungen - Warenkorb" kann jetzt die Teilnahmebedingungen anzeigen ohne dass ein AGB Artikel festgelegt wurde.</li>
	<li>Modul 26-2 "D2U Veranstaltungen - Warenkorb" kann jetzt statt dem Geburtsdatum nach dem Alter zu Veranstaltungsbeginn fragen.</li>
	<li>Modul 26-2 "D2U Veranstaltungen - Warenkorb" kann jetzt Wurzelkategorien auswählen, in denen Teilnehmer nach dem Alter gefragt werden.</li>
	<li>Modul 26-3 "D2U Veranstaltungen - Ausgabe Veranstaltungen einer Kategorie in Boxen" hatte unter bestimmten Umständen Backendseite ins Frontend weitergeleitet.</li>
	<li>Backend: Beim online stellen einer Veranstaltung in der Veranstaltungsliste gab es beim Aufruf im Frontend einen Fatal Error, da der URL cache nicht neu generiert wurde.</li>
</ul>
<p>3.0.6:</p>
<ul>
	<li>Backend: Einstellungen und Setup Tabs rechts eingeordnet um sie vom Inhalt besser zu unterscheiden.</li>
	<li>Für jeden Kurs Option hinzugefügt, dass im Warenkorb nur nach Anzahl Teilnehmer gefragt wird, anstatt Teilnehmerdetails.</li>
	<li>Weitere Anpassungen an URL Addon Version 2.x.</li>
	<li>Bugfix: das Löschen eines Bildes im Medienpool wurde unter Umständen mit der Begründung verhindert, dass das Bild in Benutzung sei, obwohl das nicht der Fall war.</li>
	<li>Modul 26-1 "D2U Veranstaltungen - Ausgabe Veranstaltungen" mit neuer Option: Kursliste als Kacheln darstellen.</li>
	<li>Modul 26-1 "D2U Veranstaltungen - Ausgabe Veranstaltungen" fehlten in der Kursdetailansicht zwei div's.</li>
	<li>Modul 26-3 "D2U Veranstaltungen - Ausgabe Veranstaltungen einer Kategorie in Boxen" hinzugefügt.</li>
</ul>
<p>3.0.5:</p>
<ul>
	<li>Fehler beim Speichern eines Kurses wenn noch keine Kategorie angelegt wurde behoben.</li>
	<li>Anpassungen an URL Addon Version 2.x.</li>
	<li>Listen im Backend werden jetzt nicht mehr in Seiten unterteilt.</li>
	<li>YRewrite Multidomain support.</li>
	<li>Modul 26-1 mit verbesserter Ausgabe des Buchungsstatus.</li>
	<li>Modul 26-2 Warenkorb: Beim Speichern des Warenkorbs wird nun auf die Kursseite weitergeleitet.</li>
	<li>Konvertierung der Datenbanktabellen zu utf8mb4.</li>
	<li>Option zur Eingabe einer Ferienpass Nummer in den Einstellungen / Warenkorb hinzugefügt.</li>
	<li>PHP Warning im Warenkorb entfernt.</li>
	<li>Bugfix schedule_categories Plugin: schedule_category::getAllParents() gibt nun auch Eltern mit Kindern zurück.</li>
</ul>
<p>3.0.4:</p>
<ul>
	<li>schedule_categories Plugin sortiert nun auch die Elternkategorien nach Namen.</li>
	<li>schedule_categories Plugin beherrscht nun auch Prioritäten.</li>
	<li>target_groups Plugin beherrscht nun auch Prioritäten.</li>
	<li>Bugfix: Prioritäten wurden beim Löschen nicht reorganisiert.</li>
	<li>Wenn Kategorien im Frontend nach Priorität sortiert angezeigt werden, werden sie nun auch im Backend und bei den Zielgruppen Kind Kategorien so sortiert.</li>
	<li>Bugfix kufer_sync Plugin: Beim target_groups Plugin wurden nicht alle Zielgruppen korrekt zugeordnet.</li>
	<li>Beim Löschen werden jetzt auch die Zuordnungen zu Kategorien, usw. gelöscht.</li>
	<li>Listennamen der Kategorien und Terminkategorien im Backend verbessert: Elternkategorien werden dem Namen vorangestellt.</li>
</ul>
<p>3.0.3:</p>
<ul>
	<li>Suche in Modul 26-1 bei aktiviertem locations Plugin: Karte nutzt nun Google Maps API Key aus D2U Helper Addon.</li>
	<li>locations Plugin: Bei der Eingabe einer Adresse gibt es jetzt die Möglichkeit eine Adresse direkt zu geocodieren wenn im D2U Helper Addon ein Google Maps API Key mit Zugriff auf die Geocoding API hinterlegt ist.
		Geocodierte Adressen werden auf der Karte schneller geladen und belasten das Budget des Google Kontos weniger.</li>
	<li>Bugfix locations Plugin: Der Klasse für die Ortskategorie hatten die Methoden für die Meta Tags gefehlt.</li>
	<li>Einstellung: Option zur Dauer der Anzeige der Kurse eingefügt: bis Anfang / Ende erster Tag, bis Anfang / Ende letzter Tag.</li>
	<li>Bugfix target_groups Plugin: Zielgruppenkinder URLs wurden mit rex_getUrl() nicht korrekt gebaut.</li>
	<li>Bugfix target_groups Plugin: Zielgruppenkinder können selbst keine Kinder mehr haben.</li>
</ul>
<p>3.0.2:</p>
<ul>
	<li>Bugfix: Deaktiviertes Addon zu deinstallieren führte zu fatal error.</li>
	<li>In den Einstellungen gibt es jetzt eine Option, eigene Übersetzungen in SProg dauerhaft zu erhalten.</li>
	<li>Warenkorb Modul: Anpassung an MultiNewsletter &gt; 3.2.0.</li>
	<li>Ausgabe Modul: Detailverbesserungen in der Darstellung wenn nur die Beschreibung ausgefüllt ist.</li>
	<li>Bugfix: CronJob wird - wenn installiert - nicht immer richtig aktiviert.</li>
	<li>Bugfix: Import aus Redaxo 4 Datenbank verbessert.</li>
	<li>Kategorie kann nicht mehr sich selbst als Elternkategorie haben.</li>
	<li>Warenkorb Modul: CSS für Pflichtfelder intuitiver gestaltet.</li>
	<li>Warenkorb Modul: Wenn Kufer Sync Plugin deaktiviert ist, werden statistische Angaben ausgeblendet.</li>
	<li>Warenkorb Modul: Wenn keine Zahlungsoptionen ausgewählt sind, werden Zahlungsangaben nicht abgefragt.</li>
</ul>
<p>3.0.1:</p>
<ul>
	<li>Design Verbesserungen im Warenkorb Beispielmodul.</li>
	<li>Fehlerbehebung Zahlungsart bei Kufer Anmeldung.</li>
	<li>Bei der Anmeldung Minderjähriger kann nun gefragt werden, ob diese alleine nach Hause gehen dürfen.</li>
	<li>Verfügbare Zahlungsarten können in Einstellungen nach Bedarf ausgewählt werden.</li>
	<li>Priorität Feld für Kategorien hinzugefügt.</li>
	<li>Suche in Modul 26-1 integriert und Bugfix Suchfunktion.</li>
</ul>
<p>3.0.0:</p>
<ul>
	<li>Redaxo 5 Migration</li>
</ul>