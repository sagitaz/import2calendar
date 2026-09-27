# Plugin import2calendar

Il plugin serve a importare un calendario in formato iCal nel plugin Agenda ufficiale di Jeedom (calendar); quest'ultimo deve quindi essere installato e configurato.
## Requisiti
- Plugin Agenda
- Plugin import2calendar

## Attenzione
**Non è possibile apportare modifiche al file iCal; le informazioni vengono recuperate dal file iCal per essere inviate al plugin Agenda di Jeedom. Non apportate alcuna modifica all'agenda creata nel plugin Agenda, poiché verrebbero eliminate al prossimo aggiornamento del vostro file iCal.**


# <u>Configurazione</u>

## Crea un dispositivo
Inizia aggiungendo un dispositivo e scegliendone il nome
### Impostazioni di importazione
- **ical**: specificare l'URL del file ical da convertire.
- **Ora di inizio forzata**: scegli un'ora di inizio per tutti gli eventi del calendario. Per impostazione predefinita, saranno le ore di inizio dell'evento salvato nel file iCal.
- **ora di fine forzata**: scegliere un'ora di fine evento per tutti gli eventi del calendario. Per impostazione predefinita, saranno le ore di fine dell'evento registrate nel file iCal.
- **ical auto**: vacanze francesi e giorni festivi. Se selezionato, non inserire nulla nel campo ical.
- **cron**: scegliere l'intervallo di aggiornamento desiderato per il calendario.

### Impostazioni di visualizzazione
- **icona**: l'icona che verrà applicata a ogni evento.
- **colore di sfondo**: colore di sfondo predefinito per ogni evento.
- **colore del testo**: colore predefinito del testo per ogni evento.

### Personalizzazione degli eventi
Qui puoi scegliere di personalizzare alcuni eventi del tuo calendario iCal.

- Colore dello sfondo e colore del testo.

Qui, per impostazione predefinita, non viene modificato nulla; queste opzioni consentono di modificare l'ora di inizio e di fine, ad esempio per anticipare le azioni programmate.
- Ora di inizio: l'evento avrà inizio X ore prima.
- Ora di fine: l'evento terminerà X ore dopo.

### Azioni di inizio e fine
A tutti gli eventi del tuo calendario verranno aggiunte le azioni definite qui.
È possibile riorganizzare le azioni tramite trascinamento.


![Configurazioni delle azioni](../images/import2calendar_screenshot03.png)

Nel campo **nome** è possibile indicare l'evento per il quale è prevista l'azione.
- Accetta un nome parziale
- Non tiene conto delle maiuscole

**1** e **2** - Lasciare vuoto o inserire **all** affinché l'azione venga aggiunta a tutti gli eventi dell'agenda.
**4** - Selezionare **altri** affinché l'azione venga aggiunta a tutti gli eventi dell'agenda, tranne quelli per i quali è prevista un'azione personalizzata.
**3** e **5** - Inserire il **nome dell'evento** affinché l'azione venga aggiunta solo per questi.


Ora puoi cliccare su **Salva**.
L'agenda corrispondente verrà creata nel plugin agenda.

Esempio:

Qui vediamo gli eventi nel plugin Agenda; si nota inoltre che ho personalizzato anche il colore.

![Agenda](../images/Agenda-exemple.png)

Configurazione nel plugin import2calendar

![Colori](../images/personnalisation-couleurs.png)

![Azioni](../images/personnalisation-actions.png)

Torniamo all'agenda per verificare le azioni
![Calendario delle verifiche](../images/import2calendarActions.gif)

## Modifica di un dispositivo
Se si modifica una delle seguenti opzioni:
- icona
- colore di sfondo
- colore del testo
- azioni iniziali
- azioni di chiusura

Gli eventi verranno modificati nel calendario

## Gestione degli eventi
Ad ogni salvataggio o ogni volta che il cron impostato analizza il file iCal, se un evento non è più presente nel file iCal, viene eliminato dal calendario.
Gli eventi passati risalenti a più di 3 giorni fa non vengono importati e verranno eliminati man mano.

## Occorrenze
Le regole definite nel tuo ical vengono convertite nel formato Jeedom Agenda. Non ho testato tutte le possibilità; se alcune non dovessero funzionare, ti prego di allegare la riga del log di import2calendar: **event options** (imposta i tuoi log su warning o debug).


Gli eventi presenti nell'occorrenza rimangono visibili sul calendario finché l'occorrenza è valida.

Esempio: 1 evento ogni 5 giorni dal 01-03-2024 al 24-11-2024. Tutti gli eventi sono visibili sul calendario fino al 27-11-2024 (3 giorni dopo la fine della ricorrenza).

## Plugin Agenda
Nel vostro calendario verranno aggiunti gli avvisi relativi ai prossimi eventi.
Avrete quindi 9 nuovi comandi:
- ieri
- oggi
- domani
- dopodomani
- giorno 3
- giorno 4
- giorno 5
- giorno 6
- giorno 7

## ical <-> Jeedom
In alcuni casi non sarà possibile convertire i dati nel formato Jeedom, pertanto è necessario adattare i vostri calendari.
È il caso, ad esempio, di questo:
- martedì e mercoledì ogni tre settimane
Affinché i dati vengano trasmessi a Jeedom, è necessario creare:
- martedì ogni tre settimane
- mercoledì ogni 3 settimane

# <u>JeeMate</u>
- la descrizione e il luogo saranno visibili nel calendario importato in JeeMate.

# <u>Attenzione</u>
- il nome dell'agenda creata è lo stesso di quello dell'apparecchio + "-ical" (ora è possibile modificarlo   )
- la stanza sarà identica

# <u>Assistenza</u>
- Comunità Jeedom
- Discord JeeMate

# <u>Richiesta di assistenza</u>
Per semplificarmi il lavoro durante il debug di un errore di conversione, vi chiedo di creare un calendario di prova contenente solo l'evento che presenta il problema e di fornirmi l'accesso a questo file iCal.


# <u>Ringraziamenti</u>
Il plugin e l'assistenza sono gratuiti, ma se volete offrirmi un caffè o dei pannolini per bambini, vi ringrazio in anticipo.

[![ko-fi](https://ko-fi.com/img/githubbutton_sm.svg)](https://ko-fi.com/C1C61AKVV7)
