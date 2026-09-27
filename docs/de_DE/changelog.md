# Änderungsprotokoll für das Plugin „import2calendar“

>**WICHTIG**
Wenn keine Informationen zum Update vorhanden sind, bedeutet dies, dass es sich ausschließlich um eine Aktualisierung der Dokumentation, der Übersetzung oder des Textes handelt.

# 27.09.2026 Beta 1.5.0
- Start und Ende benutzerdefinierter Ereignisse minengenau einstellbar (0 bis 360 Min.)
- Die Aktualisierung von Kalendern ist wesentlich ressourcenschonender, insbesondere bei umfangreichen Kalendern
- Ein fehlerhafter Kalender verhindert nicht mehr die Aktualisierung der nachfolgenden Kalender
- Präzisere Fehlermeldungen in den Protokollen
- Korrektur von fehlenden Notizen und Orten, wenn diese ein Komma oder ein Semikolon enthalten
- Korrektur der monatlichen Wiederholungen vom Typ „2. Montag“ oder „letzter Freitag“, die in den Befehlen für die kommenden Tage fehlten
- Behebung des Problems, dass der Import bei bestimmten wiederkehrenden Ereignissen abgebrochen wurde
- Korrektur von alten Ereignissen ohne Enddatum, die fälschlicherweise gespeichert wurden
- Korrektur von falsch erkannten Zeitzonen (Australien, Südamerika, Mexiko …), die den Import blockierten oder die Zeiten verschoben
- Eine unbekannte Zeitzone unterbricht den Import nicht mehr: Stattdessen wird die Zeitzone von Jeedom verwendet
- Behebung eines Problems, bei dem bei jeder Aktualisierung auf dem italienischen Jeedom ein Kalender doppelt erstellt wurde; eine Meldung weist auf die zu löschenden Duplikate hin

# 25.02.2026 Beta 1.4.8
- Behebung eines Fehlers bei der Datumsüberprüfung

# 05.02.2026 Beta 1.4.7
- Berechtigung zum Ändern des Kalendernamens

# 04.10.2025 Stable 1.4.2
- Fehler bei doppelten Anführungszeichen im Namen des Ereignisses behoben

# 16.06.2025 Beta 1.4.1
- Fehlerbehebung bei Cron

# 23.05.2025 Stable 1.4.0
- Jeedom-Version mindestens 4.4
- Debian-Version mindestens 11

# 07.05.2025 Beta 1.3.6
- Herunterladen der iCal-Datei vor der Bearbeitung

# 25.04.2025 Beta 1.3.5
- Hinzufügen eines Timeouts für Anfragen
- Hinzufügen eines Wiederholungsversuchs für Anfragen
- Korrektur des Buchungskalenders

# 09.04.2025 Beta 1.3.4
- Kleine Korrekturen

# 05.04.2025 Beta 1.3.3
- Behebung eines Fehlers in ical2calendar bei Terminen von Tag -1 bis Tag +7

# 06.03.2025 Beta 1.2.8
- Fehlerbehebung bei ical2calendar
- Hinzufügen der Befehle j-1 sowie j+2 bis j+7
- Wiederkehrende Vorkommen verarbeiten, die sich nach einem Wochentag und nicht nach einem Datum richten

# 21.02.2025 Beta 1.2.7
- Zeitzonenanpassung
- Befehl „Aktualisieren“ hinzufügen

# 21.02.2025 Beta 1.2.6
- Korrektur von „exclude date“ und „include date“
- Korrektur cmd heute, falls alle zwei Wochen eine Veranstaltung stattfindet

# 11.02.2025 Beta 1.2.5
- Erstellung der Befehle „heute“ und „morgen“ im Kalender-Plugin für Ihre iCal-Termine.
- Korrektur der Tagesdaten
- Weitere kleinere Korrekturen

# 03.02.2025 Beta & Stable 1.2.0
- Korrektur bei falsch formatierter Zeitzone

# 30.01.2025 Beta 1.1.9
- Korrektur bei wiederkehrenden Ereignissen im wöchentlichen Rhythmus aus einem Infomaniak-Kalender

# 24.01.2025 Stable 1.1.8
- Korrektur von **others** bei den Aktien

# 09.01.2025 Beta 1.1.7
- Korrektur des aktualisierten Kalenders mit ausgeschlossenen Daten

# 07.01.2024 Beta 1.1.6
- Korrektur der Endzeit für den gesamten Tag

# 06.11.2024 Stable 1.1.5
- Hinzufügen der Schulferien in den französischen Überseegebieten

# 31.10.2024 Stable 1.1.4
- Frequenzkorrektur (siehe Dokumentation)

# 21.10.2024 Stable 1.1.3
- Korrektur: Wenn das Ereignis einen Alarm enthält, wurden der Titel und die Beschreibung geändert.
- Korrektur beim Import von Webcal-Links.

# 16.10.2024 Stable 1.1.2
- Korrektur beim Abruf von Ereignissen über mehrere Jahre hinweg

# 07.10.2024 Stable 1.1.1
- Korrektur, falls im Namen des Ereignisses ein Komma enthalten ist

# 03.10.2024 Stable 1.1.0
- Korrektur beim Löschen oder Verschieben eines Ereignisses in einem wiederkehrenden Terminblock

# 01.10.2024 Beta 1.0.9
- Korrekturen bei PHP-Warnungen
- Versionsnummer des Plugins hinzugefügt
- Korrektur bezüglich des Datums der Veranstaltung

# 17.08.2024 Stable 1.0.8
- Übersetzung ins Englische, Deutsche, Spanische, Italienische und Portugiesische. Danke @mips

# 06.05.2024 Stable 1.0.7
- Fehler bei „setTime“ beheben.

# 06.05.2024 Beta 1.0.6
- Möglichkeit hinzugefügt, Start- und Endzeit eines Ereignisses festzulegen.

# 01.05.2024 Beta 1.0.5
- Berücksichtigung ausgeschlossener Daten bei wiederkehrenden Terminen
- Berücksichtigung geänderter Daten bei wiederkehrenden Terminen

# 29.04.2024 Stable 1.0.0
- Umwandlung von Emojis in HTML (sichtbar im Namen der Veranstaltung und in der Beschreibung (JeeMate v3))
- Umrechnung von Zeitzonen in das Windows-Format (im Stil von „Romance Standard Time“)
- Hinzufügen von Optionen für Aktionen (alle, andere, Ereignis)
- Standortberücksichtigung (in JeeMate v3 sichtbar)
- Möglichkeit, einen geänderten Start- und Endzeitpunkt festzulegen.

# 25.04.2024 Beta 0.8.0
- Entfernen von Emojis aus dem Namen des Ereignisses (MySQL-Fehler 22007)

# 25.04.2024 Stable 0.7.0
- Versuche, wieder auf das Jeedom-Dokument zuzugreifen

# 01.04.2024 Beta 0.6.0
- Schaltfläche „Dokumentation“ und „Changelog“ hinzugefügt
- Hinzufügen einer Schaltfläche zu Discord (im JeeMate-Discord, 1 eigener Chatraum)

# 30.03.2024 Stable 0.5.0
- Erste stabile Version

# 29.03.2024 Beta 0.5.0
- Farbkorrektur des Textes in „Dark“ + kleine optische Anpassung
- individuelle Farbe, Groß- und Kleinschreibung nicht beachten
- Ereignisse mit wiederkehrendem Charakter: Wenn das letzte Ereignis der Reihe älter als 3 Tage ist, wird die Reihe nicht angezeigt.

# 28.03.2024 Beta 0.4.0
- Zeitzonenanpassung
- Berücksichtigung der Beschreibungen (in JeeMate sichtbar)
- Verwaltung wiederkehrender Termine
- Verwaltung des Endes einer Wiederholungsreihe, nach Datum oder nach Anzahl der Wiederholungen
- Spezifische Farbverwaltung für bestimmte Veranstaltungen

# 12.03.2024 Beta 0.3.0
- Korrektur zur Kompatibilität mit Jeedom 4.3
- Standardmäßig festgelegte Text- und Hintergrundfarbe

# 11.03.2024 Beta 0.1.0
- erste Beta-Version
