---
lang: de
permalink: overview
title: Verbinden Sie Dolibarr mit all Ihren Anwendungen
description: Synchronisieren Sie Geschäftspartner, Produkte, Lagerbestände, Aufträge und Rechnungen automatisch zwischen Dolibarr und Ihren anderen Anwendungen mit Splash Sync.
updated: 2026-09-18
translation:
    from:        fr
    source_hash: 0c35bfb2
    mode:        llm
---

Onlineshop, Kasse, CRM: Jede Ihrer Anwendungen enthält einen Teil Ihres Geschäfts. Das
Splash-Modul macht **Dolibarr zur Zentrale** all dieser Daten und hält sie laufend synchron, ohne
doppelte Eingabe und ohne manuellen Export. :rocket:

### Warum Splash für Dolibarr?

#### :shopping_cart: Alle Ihre Verkäufe an einem Ort

Aufträge und Rechnungen aus Ihrem Onlineshop, Ihrer Kasse oder jeder anderen verbundenen
Anwendung landen direkt in Dolibarr, unabhängig vom Vertriebskanal.

#### :package: Korrekte Lagerbestände, überall

Ein in Dolibarr aktualisierter Bestand wird auch auf allen Ihren Vertriebskanälen aktualisiert:
Schluss mit Bestellungen für ausverkaufte Produkte. Verwalten Sie einen Gesamtbestand oder die
Bestände jedes Lagers einzeln.

#### :busts_in_silhouette: Ein Kundendatensatz, immer aktuell

Mit dem Splash Linker werden die Profile desselben Kunden aus Ihren verschiedenen Anwendungen
erkannt und zusammengeführt. Eine Änderung an einer Stelle wird überall übernommen, vom CRM bis
zum Shop.

#### :bar_chart: Eine Finanzverwaltung, die sich selbst füllt

Aufträge, Rechnungen, Gutschriften und Zahlungen werden automatisch importiert, mit den richtigen
Mehrwertsteuersätzen und den richtigen Bankkonten. Ihre Finanzübersicht wird einfacher... und
mühelos.

### Was synchronisiert wird

| Dolibarr-Objekt | Was unterstützt wird |
|---|---|
| Geschäftspartner | Kunden und Interessenten, Kunden- und Buchhaltungsnummern, Adresse |
| Kontakte | Kontakte und Lieferadressen |
| Produkte | Katalog, Preise und Mehrfachpreise, Lagerbestände, Bilder, Varianten, Übersetzungen |
| Kundenaufträge | Positionen, Status, Lieferadresse, Nummer und PDF der zugehörigen Rechnung |
| Kundenrechnungen | Positionen, MwSt., Zahlungen |
| Kundengutschriften | Positionen, MwSt., Erstattungen |

### Für den Praxiseinsatz gemacht

- :zap: **Reibungsloser Import**: Gastbestellungen werden einem Standardkunden zugeordnet, Kunden
  anhand ihrer E-Mail-Adresse erkannt, Produkte anhand ihrer Artikelnummer (SKU) identifiziert.
- :receipt: **MwSt. im Griff**: MwSt.-Codes werden erkannt und, falls sie fehlen, anhand des
  Steuersatzes ermittelt, gemäß Ihrem Dolibarr-Wörterbuch.
- :house: **Saubere Adressen**: Mehrzeilig erfasste Adressen werden automatisch aufgeteilt, für
  Anwendungen, die sie in mehreren Feldern erwarten.
- :globe_with_meridians: **Mehrsprachig**: Produktbezeichnungen und -beschreibungen werden in allen
  Ihren Sprachen synchronisiert.
- :lock: **Ihre Regeln gelten**: Das Modul handelt im Namen eines eigenen Benutzers und beachtet die
  Rechteverwaltung von Dolibarr.

### Kompatibilität

- Dolibarr **14 bis 24**
- PHP **7.4** oder neuer
- Ein aktives Splash Sync-Konto

> [!TIP]
> Installation und Konfiguration dauern nur wenige Minuten: Folgen Sie dem Abschnitt **Erste
> Schritte** dieser Dokumentation.

### Frei und offen für Beiträge

Das Modul ist Open Source und sein Code ist öffentlich auf
[github.com/SplashSync/Dolibarr](https://github.com/SplashSync/Dolibarr): Alle Beiträge sind
willkommen!
