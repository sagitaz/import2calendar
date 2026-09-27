# Plugin „import2calendar“

Das Plugin dient dazu, einen Kalender im iCal-Format in das offizielle Jeedom-Kalender-Plugin (calendar) zu importieren. Dieses muss daher installiert und konfiguriert sein.
## Voraussetzungen
- Kalender-Plugin
- Plugin „import2calendar“

## Achtung
**Es sind keine Änderungen am iCal möglich. Die Daten werden aus dem iCal abgerufen und an das Jeedom-Kalender-Plugin gesendet. Nehmen Sie keine Änderungen am im Kalender-Plugin erstellten Kalender vor, da diese beim nächsten Update Ihres iCal gelöscht würden.**


# <u>Konfiguration</u>

## Gerät anlegen
Fügen Sie zunächst ein Gerät hinzu und wählen Sie einen Namen dafür aus
### Importparameter
- **ical**: Geben Sie die URL der zu konvertierenden iCal-Datei an.
- **Festgelegte Startzeit**: Wählen Sie eine Startzeit für alle Ereignisse im Kalender aus. Standardmäßig werden die im iCal gespeicherten Startzeiten der Ereignisse übernommen.
- **Festgelegte Endzeit**: Wählen Sie eine Endzeit für alle Ereignisse im Kalender aus. Standardmäßig werden die im iCal gespeicherten Endzeiten der Ereignisse verwendet.
- **ical auto**: Französische Feiertage und gesetzliche Feiertage. Wenn diese Option ausgewählt ist, geben Sie im Feld „ical“ nichts ein.
- **cron**: Wählen Sie die gewünschte Aktualisierungszeit für den Kalender aus.

### Anzeigeeinstellungen
- **Symbol**: Das Symbol, das jedem Ereignis zugewiesen wird.
- **Hintergrundfarbe**: Standard-Hintergrundfarbe für jedes Ereignis.
- **Textfarbe**: Standard-Textfarbe für jedes Ereignis.

### Anpassung von Ereignissen
Hier können Sie bestimmte Ereignisse in Ihrem iCal individuell anpassen.

- Hintergrundfarbe und Textfarbe.

Hier sind standardmäßig keine Änderungen vorgenommen worden. Mit diesen Optionen können Sie die Start- und Endzeit ändern, um beispielsweise geplante Aktionen vorwegzunehmen.
- Startzeit: Die Startzeit der Veranstaltung beginnt X Stunden vorher.
- Endzeit: Das Ereignis endet X Stunden später.

### Maßnahmen zu Beginn und am Ende
Für alle Termine in Ihrem Kalender werden die hier definierten Aktionen hinzugefügt.
Sie können die Aktionen per Drag & Drop neu anordnen.


![Aktionskonfigurationen](../images/import2calendar_screenshot03.png)

Im Feld **Name** können Sie das Ereignis angeben, für das die Aktion vorgesehen ist.
- Akzeptiert einen Teilnamen
- Ignoriert Groß- und Kleinschreibung

**1** und **2** – Leer lassen oder **all** eingeben, damit die Aktion zu allen Terminen im Kalender hinzugefügt wird.
**4** – Geben Sie **„others“** ein, damit die Aktion zu allen Terminen im Kalender hinzugefügt wird, mit Ausnahme derjenigen, für die eine benutzerdefinierte Aktion vorgesehen ist.
**3** und **5** – Geben Sie den **Namen des Ereignisses** ein, damit die Aktion nur für diese Ereignisse hinzugefügt wird.


Sie können nun auf **Speichern** klicken.
Der entsprechende Kalender wird im Kalender-Plugin erstellt.

Beispiel:

Hier sehen wir die Termine im Kalender-Plugin; außerdem ist zu erkennen, dass ich die Farbe angepasst habe.

![Terminkalender](../images/Agenda-exemple.png)

Die Konfiguration im Plugin „import2calendar“

![Farben](../images/personnalisation-couleurs.png)

![Maßnahmen](../images/personnalisation-actions.png)

Wir schauen noch einmal im Terminkalender nach, um die Maßnahmen zu überprüfen
![Prüfplan](../images/import2calendarActions.gif)

## Bearbeiten eines Geräts
Wenn Sie eine der folgenden Optionen ändern:
- Symbol
- Hintergrundfarbe
- Textfarbe
- Erste Schritte
- Abschlussaktionen

Die Termine werden im Kalender geändert.

## Ereignisverwaltung
Bei jeder Sicherung oder jedes Mal, wenn der festgelegte Cron-Job die iCal-Datei auswertet, wird ein Ereignis, das nicht mehr in der iCal-Datei enthalten ist, aus dem Kalender gelöscht.
Veranstaltungen, die länger als drei Tage zurückliegen, werden nicht importiert und nach und nach gelöscht.

## Vorkommen
Die in Ihrer iCal-Datei definierten Regeln werden in das Jeedom-Agenda-Format konvertiert. Ich habe nicht alle Möglichkeiten getestet. Sollten einige davon nicht funktionieren, fügen Sie bitte die entsprechende Zeile aus dem Import2Calendar-Protokoll bei: **event options** (stellen Sie Ihre Protokolle auf „Warning“ oder „Debug“ ein).


Die in der Instanz enthaltenen Ereignisse bleiben im Kalender sichtbar, solange die Instanz gültig ist.

Beispiel: 1 Ereignis alle 5 Tage vom 01.03.2024 bis zum 24.11.2024. Alle Ereignisse sind im Kalender bis zum 27.11.2024 (3 Tage nach Ende des Ereignisses) sichtbar.

## Kalender-Plugin
In Ihrem Kalender werden die Informationen zu den bevorstehenden Veranstaltungen hinzugefügt.
Sie erhalten somit 9 neue Befehle:
- gestern
- heute
- morgen
- übermorgen
- Tag 3
- Tag 4
- Tag 5
- Tag 6
- Tag 7

## ical <-> Jeedom
Bei bestimmten Einträgen ist eine Konvertierung in das Jeedom-Format nicht möglich; daher müssen Sie Ihre Kalender entsprechend anpassen.
Das ist zum Beispiel hier der Fall:
- Dienstag und Mittwoch alle drei Wochen
Damit die Daten in Jeedom übertragen werden, müssen Sie Folgendes erstellen:
- jeden Dienstag alle drei Wochen
- Mittwochs alle drei Wochen

# <u>JeeMate</u>
- Die Beschreibung und der Ort werden im in JeeMate importierten Kalender angezeigt.

# <u>Achtung</u>
- Der Name des erstellten Termins entspricht dem Namen des Geräts + „-ical“ (er kann nun geändert werden)
- Der Raum wird identisch sein

# <u>Support</u>
- Jeedom-Community
- Discord JeeMate

# <u>Hilfeanfrage</u>
Um mir die Arbeit beim Debuggen eines Konvertierungsfehlers zu erleichtern, bitte ich Sie, einen Testkalender zu erstellen, der ausschließlich das problematische Ereignis enthält, und mir Zugriff auf diesen iCal zu gewähren.


# <u>Vielen Dank</u>
Das Plugin und der Support sind kostenlos. Wenn Sie mir dennoch gerne einen Kaffee oder Babywindeln spendieren möchten, bedanke ich mich schon im Voraus.

[![ko-fi](https://ko-fi.com/img/githubbutton_sm.svg)](https://ko-fi.com/C1C61AKVV7)
