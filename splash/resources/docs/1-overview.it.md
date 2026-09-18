---
lang: it
permalink: overview
title: Collegate Dolibarr a tutte le vostre applicazioni
description: Sincronizzate automaticamente soggetti terzi, prodotti, scorte, ordini e fatture tra Dolibarr e le vostre altre applicazioni con Splash Sync.
updated: 2026-09-18
translation:
    from:        fr
    source_hash: 0c35bfb2
    mode:        llm
---

Negozio online, punto vendita, CRM: ognuna delle vostre applicazioni contiene una parte della
vostra attività. Il modulo Splash fa di **Dolibarr il punto centrale** di tutti questi dati e li
mantiene sempre sincronizzati, senza doverli reinserire né esportare a mano. :rocket:

### Perché Splash per Dolibarr?

#### :shopping_cart: Tutte le vostre vendite in un unico posto

Gli ordini e le fatture del vostro negozio online, del vostro punto vendita o di qualsiasi altra
applicazione collegata arrivano direttamente in Dolibarr, qualunque sia il canale di vendita.

#### :package: Scorte esatte, ovunque

Una scorta aggiornata in Dolibarr lo è anche su tutti i vostri canali di vendita: basta ordini di
prodotti esauriti. Gestite una scorta globale, oppure le scorte di ogni magazzino separatamente.

#### :busts_in_silhouette: Un'unica scheda cliente, sempre aggiornata

Grazie al Linker di Splash, i profili di uno stesso cliente distribuiti nelle vostre applicazioni
vengono identificati e unificati. Una modifica fatta in un punto si applica ovunque, dal CRM al
negozio.

#### :bar_chart: Una gestione finanziaria che si compila da sola

Ordini, fatture, note di credito e pagamenti vengono importati automaticamente, con le aliquote IVA
e i conti bancari corretti. Il vostro controllo finanziario diventa più semplice... e senza sforzo.

### Cosa viene sincronizzato

| Oggetto Dolibarr | Cosa è supportato |
|---|---|
| Soggetti terzi | Clienti e potenziali clienti, codici cliente e contabile, indirizzo |
| Contatti | Contatti e indirizzi di consegna |
| Prodotti | Catalogo, prezzi e multi-prezzi, scorte, immagini, varianti, traduzioni |
| Ordini clienti | Righe, stati, indirizzo di consegna, numero e PDF della fattura collegata |
| Fatture clienti | Righe, IVA, pagamenti |
| Note di credito clienti | Righe, IVA, rimborsi |

### Pensato per l'uso reale

- :zap: **Importazione senza intoppi**: ordini «ospite» associati a un cliente predefinito, clienti
  riconosciuti dal loro indirizzo e-mail, prodotti identificati dal loro codice (SKU).
- :receipt: **IVA sotto controllo**: i codici IVA vengono riconosciuti e, in loro assenza, ricavati
  dall'aliquota, secondo il vostro dizionario Dolibarr.
- :house: **Indirizzi puliti**: gli indirizzi scritti su più righe vengono suddivisi
  automaticamente per le applicazioni che li attendono in più campi.
- :globe_with_meridians: **Multilingue**: nomi e descrizioni dei prodotti sincronizzati in tutte le
  vostre lingue.
- :lock: **Le vostre regole si applicano**: il modulo agisce per conto di un utente dedicato e
  rispetta la politica dei permessi di Dolibarr.

### Compatibilità

- Dolibarr **da 14 a 24**
- PHP **7.4** o successivo
- Un account Splash Sync attivo

> [!TIP]
> Installazione e configurazione richiedono solo pochi minuti: seguite la sezione **Per iniziare**
> di questa documentazione.

### Libero e aperto ai contributi

Il modulo è open source e il suo codice è pubblico su
[github.com/SplashSync/Dolibarr](https://github.com/SplashSync/Dolibarr): tutti i contributi sono
benvenuti!
