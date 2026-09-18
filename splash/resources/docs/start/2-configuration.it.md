---
lang: it
permalink: start/configure
title: Configurare il modulo Splash
description: Collegate il modulo al vostro account Splash e definite i suoi parametri predefiniti.
updated: 2026-09-18
translation:
    from:        fr
    source_hash: 9558c324
    mode:        llm
---

Una volta attivato, il modulo deve essere collegato al vostro account Splash e ricevere alcuni
valori predefiniti. Bastano pochi minuti :stopwatch:

Per aprire la sua configurazione, cliccate sull'icona delle impostazioni del modulo Splash in
**Impostazioni > Moduli/Applicazioni**.

### Collegatevi al vostro account Splash

Iniziate creando le chiavi di accesso del vostro server: nel vostro spazio Splash, andate su
**Server** > **Aggiungi un server**, poi annotate l'identificativo del server e la sua chiave di
crittografia.

![Aggiunta di un server nello spazio Splash](../assets/img/screenshot_2.png)

Inserite poi queste due chiavi nel blocco **Parametri principali** della configurazione del
modulo.

> [!IMPORTANT]
> Copiate le chiavi così come sono, senza spazi in più né caratteri dimenticati: un solo carattere
> errato impedisce qualsiasi connessione.

![Chiavi Splash nella configurazione del modulo](../assets/img/screenshot_3.png)

### Definite i parametri predefiniti

Il blocco **Parametri locali** raccoglie i valori utilizzati ogni volta che un oggetto viene creato
o modificato senza un valore esplicito.

![Parametri predefiniti del modulo](../assets/img/screenshot_4.png)

#### Lingua predefinita

La lingua utilizzata dal modulo per comunicare con il server Splash.

#### Utente predefinito

L'utente a nome del quale il modulo esegue tutte le sue azioni.

> [!TIP]
> Create un utente dedicato a Splash: il modulo applica la politica dei permessi di Dolibarr,
> quindi questo utente deve avere i permessi su tutti gli oggetti che volete sincronizzare.

#### Magazzino, conto bancario e metodo di pagamento predefiniti

I valori utilizzati quando l'altra applicazione non ne fornisce alcuno.

### Verificate i risultati degli autotest

A ogni salvataggio della configurazione, il modulo verifica i vostri parametri e si assicura che
la comunicazione con Splash funzioni.

> [!WARNING]
> Tutti i test devono essere verdi: un autotest fallito significa che il server non può
> sincronizzare.

![Risultati degli autotest](../assets/img/screenshot_5.png)
