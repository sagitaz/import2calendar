# Registro delle modifiche del plugin import2calendar

>**IMPORTANTE**
Se non sono disponibili informazioni sull'aggiornamento, significa che si tratta esclusivamente di un aggiornamento della documentazione, della traduzione o del testo.

# 27/09/2026 Beta 1.5.0
- Inizio e fine degli eventi personalizzati regolabili al minuto (da 0 a 360 min)
- L'aggiornamento dei calendari richiede molte meno risorse, soprattutto nel caso di calendari di grandi dimensioni
- Un calendario errato non impedisce più l'aggiornamento dei calendari successivi
- Messaggi di errore più precisi nei log
- Correzione dei voti e dei luoghi mancanti quando contengono una virgola o un punto e virgola
- Correzione delle ricorrenze mensili del tipo «secondo lunedì» o «ultimo venerdì», assenti dai comandi relativi ai giorni futuri
- Risoluzione del problema che causava l'interruzione dell'importazione in caso di determinati eventi ricorrenti
- Correzione di eventi passati senza data di fine conservati erroneamente
- Correzione dei fusi orari non riconosciuti correttamente (Australia, Sudamerica, Messico...) che bloccavano l'importazione o causavano uno sfasamento degli orari
- Un fuso orario sconosciuto non interrompe più l'importazione: al suo posto viene utilizzato quello di Jeedom
- Correzione di un calendario creato in doppio ad ogni aggiornamento su Jeedom in italiano; un messaggio segnala i duplicati da eliminare

# 25/02/2026 Beta 1.4.8
- Correzione di un bug relativo alla verifica della data

# 05/02/2026 Beta 1.4.7
- Autorizzazione a modificare il nome dell'agenda

# 04/10/2025 Stabile 1.4.2
- Correzione di un errore relativo alle virgolette doppie nel nome dell'evento

# 16/06/2025 beta 1.4.1
- Correzione di un bug in Cron

# 23/05/2025 Versione stabile 1.4.0
- Versione Jeedom 4.4 o superiore
- Versione Debian almeno 11

# 07/05/2025 Beta 1.3.6
- Scarica il file iCal prima dell'elaborazione

# 25/04/2025 Beta 1.3.5
- Aggiunta di un timeout per le richieste
- Aggiunta di un tentativo di ripetizione per le richieste
- Correzione calendario Booking

# 09/04/2025 Beta 1.3.4
- Piccole correzioni

# 05/04/2025 Beta 1.3.3
- Correzione del bug di ical2calendar relativo agli eventi da j-1 a j+7

# 06/03/2025 Beta 1.2.8
- Correzione bug ical2calendar
- Aggiunta dei comandi j-1 e da j+2 a j+7
- Elaborare le occorrenze che si ripetono in base al giorno e non alla data

# 21/02/2025 Beta 1.2.7
- Correzione del fuso orario
- Aggiunta del comando "Aggiorna"

# 21/02/2025 Beta 1.2.6
- Correzione delle date di esclusione e di inclusione
- Correzione cmd oggi se evento ogni 2 settimane

# 11/02/2025 Beta 1.2.5
- Creazione dei comandi "oggi" e "domani" nel plugin Agenda per gli eventi del tuo iCal.
- Correzione delle date giornaliere
- Altre piccole correzioni

# 03/02/2025 Beta e Stabile 1.2.0
- Correzione se il fuso orario è formattato in modo errato

# 30/01/2025 Beta 1.1.9
- Correzione relativa a un evento ricorrente settimanale proveniente da un calendario Infomaniak

# 24/01/2025 Versione stabile 1.1.8
- Correzione di **altri** nelle azioni

# 09/01/2025 Beta 1.1.7
- Correzione dell'aggiornamento del calendario con date escluse

# 07/01/2024 Beta 1.1.6
- Correzione dell'ora di fine giornata intera

# 06/11/2024 Stabile 1.1.5
- Aggiunta delle vacanze scolastiche nei DOM e TOM

# 31/10/2024 Versione stabile 1.1.4
- Correzione della frequenza (vedi documentazione)

# 21/10/2024 Stabile 1.1.3
- Correzione: se l'evento include un allarme, il titolo e la descrizione venivano modificati.
- Correzione dell'importazione dei link webcal.

# 16/10/2024 Stabile 1.1.2
- Correzione relativa al recupero degli eventi relativi a diversi anni

# 07/10/2024 Stabile 1.1.1
- Correzione se nel nome dell'evento è presente una virgola

# 03/10/2024 Versione stabile 1.1.0
- Correzione in caso di eliminazione o spostamento di un evento presente in una ricorrenza

# 01/10/2024 Beta 1.0.9
- Correzioni degli avvisi PHP
- Aggiunta del numero di versione del plugin
- Correzione relativa all'aggiornamento dell'evento

# 17/08/2024 Stabile 1.0.8
- Traduzione in inglese, tedesco, spagnolo, italiano e portoghese. Grazie @mips

# 06/05/2024 Stabile 1.0.7
- Correggi l'errore setTime.

# 06/05/2024 Beta 1.0.6
- Aggiunta la possibilità di impostare manualmente l'ora di inizio e di fine di un evento.

# 01/05/2024 Beta 1.0.5
- Considerazione delle date escluse nelle ricorrenze
- Considerazione delle date modificate nelle ricorrenze

# 29/04/2024 Versione stabile 1.0.0
- Conversione degli emoji in HTML (visibili nel nome dell'evento e nella descrizione (JeeMate v3))
- Conversione dei fusi orari nel formato Windows (tipo Romance Standard Time)
- Aggiunta di opzioni per le azioni (tutte, altre, evento)
- Considerazione della posizione (visibile in JeeMate v3)
- Possibilità di configurare un orario di inizio e di fine modificato.

# 25/04/2024 Beta 0.8.0
- Rimozione degli emoji dal nome dell'evento (errore MySQL 22007)

# 25/04/2024 Versione stabile 0.7.0
- Prova a tornare alla documentazione di Jeedom

# 01/04/2024 Beta 0.6.0
- Aggiunta del pulsante "Documentazione" e "Changelog"
- Aggiunta di un pulsante per accedere a Discord (su Discord JeeMate, 1 sala dedicata)

# 30/03/2024 Stabile 0.5.0
- prima versione stabile

# 29/03/2024 Beta 0.5.0
- correzione del colore del testo nell'input in modalità scura + piccola modifica visiva
- colore personalizzato, non tenere conto delle maiuscole e delle minuscole
- eventi ricorrenti: se l'ultimo evento della serie risale a più di 3 giorni fa, la serie non viene visualizzata.

# 28/03/2024 Beta 0.4.0
- Correzione del fuso orario
- Inserimento delle descrizioni (visibili in JeeMate)
- Gestione delle ricorrenze
- Gestione della fine della ricorrenza, per data o per numero di ripetizioni
- Gestione dei colori specifica per determinati eventi

# 12/03/2024 Beta 0.3.0
- Correzione per la compatibilità con Jeedom 4.3
- Colore del testo e dello sfondo predefiniti

# 11/03/2024 Beta 0.1.0
- prima versione beta
