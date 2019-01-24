# d2u_courses
Veranstaltungs- / Kursverwaltung Addon für Redaxo 5

Beschreibung

Verwalte eure Kurse und Veranstaltungen. Plugin für Synchronisation mit der Kufer SQL Software vorhanden.

Verwaltung von Kursen oder Veranstaltungen. Veranstaltungen können eingeteilt werden nach:
- Kategorien und Unterkategorien
- Zielgruppen (per Plugin)
- Terminkategorien (per Plugin)
- Orten und Ortskategorien (per Plugin)
Für Kurse, Kategorien, Orte, … werden sprechende URLs generiert (braucht dafür YRewrite und URL Addon).
Ein Warenkorb mit E-Mailanmeldung kann zum Buchen genutzt werden.
Wenn das MultiNewsletter Addon installiert ist, kann im Buchungsvorgang ein Newsletter bestellt werden.

Außerdem gibt es ein Plugin für die Synchronisation mit der Software Kufer SQL. Die Kufer
Software kann einen XML Export erstellen (Lizenz erforderlich, nicht im Basisprogramm enthalten)
der mit diesem Plugin eingelesen werden kann – auch automatisch per CronJob. Für die importierten
Kurse werden Anmeldungen nicht per Mail versandt, sondern als XML Dateien in ein gesichertes
Verzeichnis gelegt, die Kufer SQL wieder kann.
