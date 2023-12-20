# Veranstaltungs- / Kursverwaltung Addon für Redaxo 5

Kurse oder Veranstaltungen für Redaxo 5 mit optionaler Buchungsoption und Beispielmodulen.

## Installation

1. Addon über den Redaxo Installer installieren. Nur die benötigten Plugins installieren.
2. Einstellungen festlegen. Es muss ein Artikel für die Kursausgabe festgelegt werden. Auf diesen Artikel basierend werden die URLs der Veranstaltungen und Kategorien festgelegt. Dort sollte auch ein Block mit dem Beispielmodul "26-1 D2U Veranstaltungen - Ausgabe Veranstaltungen" eingefügt werden. Ebenso muss ein Artikel für den Warenkorb angelegt werden. Dort sollte auch ein Block mit dem Beispielmodul "26-2 D2U Veranstaltungen - Warenkorb" eingefügt werden. Die Beispielmodule können nach Bedarf angepasst werden.
3. Kategorien anlegen.
4. Nun kann mit der Eingabe der Kurse / Veranstaltungen begonnen werden.

## Plugins

### customer_bookings: speichert die Anmeldedaten

Diese Plugin wird nur benötigt, wenn die Daten der Teilnehmer gespeichert werden sollen. Dann können Teilnehmerlisten exportiert werden. Auch ein CSV Export für die Schweizerische NDS Datenbank (Freiwilliger Schulsport) kann erstellt werden. Teilnehmerdaten werden nur gespeichert, wenn in den Veranstaltungen im Feld "Anmeldung möglich?" der Wert "Ja, für jeden Teilnehmer Details erfragen" ausgewählt wird. Welche Details erfragt werden sollen, kann im Modul eingestellt werden. Sobald diese Option in einer Veranstaltung aktiviert ist, wird die Anzahl Anmeldungen aus der Teilnehmerliste berechnet und kann nicht mehr manuell eingegeben werden. Dabei gibt es allerdings eine Ausnahme: wenn der Kurs von KuferSQL importiert wurde, gibt Kufer die Anmeldezahlen vor und nicht die Teilnehmerdatenbank.
Wird ein Kurs gelöscht, zu dem es Teilnehmerdaten gibt, werden die Daten der Teilnehmer ebenfalls gelöscht.

### kufer_sync: importiert eine von Kufer SQL generierte XML Datei

Diese Plugin wird nur benötigt, wenn die VHS Kursverwaltung Kufer SQL genutzt wird. Die Kufer Software kann einen XML Export erstellen (Lizenz erforderlich, nicht im Basisprogramm enthalten) der mit diesem Plugin eingelesen werden kann. Dies ist auch auch automatisch per CronJob möglich. Für die importierten Kurse werden Anmeldungen nicht per Mail versandt, sondern als XML Dateien in ein gesichertes Verzeichnis gelegt, die Kufer SQL eingelesen werden kann.

### locations: Verwaltung der Veranstaltungsorte

Mit diesem Plugin können einem Kurs ein Veranstaltungsort zugewiesen werden. Veranstaltungsorte lassen sich wiederrum in Ortskategorien zusammenfassen. Ein Beispiel: es werden Kurse in 2 Städten angeboten. In Stadt A gibt es 4 Veranstaltungsorte, in Stadt B 3. Also legt man 2 "Ortskategorien" an: "Stadt A" und "Stadt B". Danach werden die Veranstaltungsorte ("Örtlichkeiten" genannt) angelegt und den Ortskategorien zugeordnet.

In den Einstellungen ist es nötig, einen Artikel für die Örtlichkeiten festzulegen. Auf diesen Artikel basierend werden die URLs der Ortskategorien und Örtlichkeiten generiert. Dies kann der gleiche Artikel wie für die Kursausgabe sein. In dem Artikel sollte das Beispielmodul "26-1 D2U Veranstaltungen - Ausgabe Veranstaltungen" eingefügt werden.

### schedule_categories: Verwaltung von Terminkategorien

Terminkategorien können z.B. Sommerferien, Osterferien, aber auch Wochenendkurse oder ähnliches sein.

In den Einstellungen ist es nötig, dass ein Artikel für dieses Plugin festzulegen. Auf diesen Artikel basierend werden die URLs der Terminkategorien generiert. Dies kann ebenfalls der gleiche Artikel wie für die Kursausgabe sein. In dem Artikel sollte das Beispielmodul "26-1 D2U Veranstaltungen - Ausgabe Veranstaltungen" eingefügt werden.

### target_groups: Verwaltung von Zielgruppen

Zielgruppen können z.B. Kinder, Jugendliche oder Erwachsene sein.

In den Einstellungen ist es auch hier nötig, dass ein Artikel festgelegt wird. Auf diesen Artikel basierend werden die URLs der Zielgruppen generiert. Dies kann wieder der gleiche Artikel wie für die Kursausgabe sein. In dem Artikel sollte das Beispielmodul "26-1 D2U Veranstaltungen - Ausgabe Veranstaltungen" eingefügt werden.

## Beispielmodule

Die Beispielmodule basieren auf Bootstrap 4 und bieten die komplette Ausgabe inklusive der Plugins.

- "26-1 D2U Veranstaltungen - Ausgabe Veranstaltungen". Dieses Beispielmodul stellt die komplette Kursausgabe zur Verfügung und sollte in dem Artikel ausgegeben werden, der in den Einstellungen unter "Redaxo Artikel Kurse" festgelegt wurde. Das Beispielmodul bietet die Möglichkeit, Kurse nicht nur über die Kurskategorien aufzufinden, sondern auch Kurse zu einer bestimmten Zielgruppen, z.B. den Kinder oder über Terminkategorie, z.B. den Sommerferien, und nicht zu letzt auch über Örtlichkeiten zu finden. Auch die Ausgabe der Kurse selbst, ggf. mit Karte, sofern das Plugin "locations" installiert ist, befindet sich in diesem Modul. Die Veranstaltungen / Kurse können im JSON+LD Format für die Google Suche ausgegeben werden. Veranstaltungen benötigen hierzu ein aktiviertes "locations" Plugin. Für Kurse ist das nicht nötig.
- "26-2 D2U Veranstaltungen - Warenkorb". Dieses Beispielmodul stellt einen kompletten Warenkorb zur Verfügung und sollte in dem Artikel installiert werden, der in den Einstellungen als Warenkorb Artikel festgelegt wurde. Wenn das Addon MultiNewsletter installiert ist, kann im Bestellvorgang auch ein Newsletter abonniert werden. Wenn das kufer_sync Plugin installiert ist, werden für importierte Kurse Anmeldungen in Kuder XML generiert. Für alle Kurse, die nicht über das Plugin importiert werden, wird eine normale Anmeldemail generiert.
- "26-3 D2U Veranstaltungen - Ausgabe Veranstaltungen einer Kategorie in Boxen". Dieses Beispielmodul sollte in keinem der in den Einstellungen festgelegten Artikel ausgegeben werden. Es kann in einem beliebigen anderen Artikel ausgegeben werden und gibt nur Kategorien aus.

## FAQs

- Warum wird ein Kurs nicht angezeigt? Eventuell muss das Angebot online gestellt sein. Oder das Startdatum des Angebots liegt in der Vergangenheit. Dieses Verhalten kann in den Einstellungen unter "Kurse anzeigen bis ..." angepasst werden.
- Warum wird eine Kategorie nicht angezeigt? Kategorien werden nur angezeigt, wenn auch ein Kurs darin enthalten ist, der zur Zeit online ist.
- Wie kann ich das Warenkorb Symbol in meinem Template einbinden? Hier ein einfacher Beispielcode:

``` php
echo '<a href="'. rex_getUrl((int) rex_config::get('d2u_courses', 'article_id_shopping_cart')) .'" class="cart_link">';
echo '<div id="cart_symbol" class="desktop-inner">';
echo '<img src="'. rex_url::addonAssets('d2u_courses', 'cart_only.png') .'" alt="'. rex_article::get(rex_config::get('d2u_courses', 'article_id_shopping_cart', 0))->getName() .'">';
if(count(\D2U_Courses\Cart::getCourseIDs()) > 0) {
    echo '<div id="cart_info">'. count(\D2U_Courses\Cart::getCourseIDs()) .'</div>';
}
echo '</div>';
echo '</a>';
```