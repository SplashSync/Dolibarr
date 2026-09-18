---
lang: de
permalink: start/install
title: Das Splash-Modul installieren
description: Laden Sie das Modul herunter, installieren Sie es über die Dolibarr-Oberfläche oder manuell und aktivieren Sie es.
updated: 2026-09-18
translation:
    from:        fr
    source_hash: 377a3cd1
    mode:        llm
---

### Voraussetzungen

- Dolibarr **14** oder neuer
- PHP **7.4** oder neuer
- Ein aktives Splash Sync-Konto

### Modul herunterladen

Laden Sie die neueste Version des Moduls direkt von dieser Seite herunter: Es ist die Datei
`module_splash-x.y.z.zip`.

Sie benötigen eine ältere Version? Alle veröffentlichten Versionen finden Sie auf der
[GitHub-Versionsseite](https://github.com/SplashSync/Dolibarr/releases).

> [!NOTE]
> Für die Installation über die Oberfläche entpacken Sie das Archiv nicht: Dolibarr erwartet die
> `.zip`-Datei unverändert.

### Über die Dolibarr-Oberfläche installieren

Die einfachste Methode, ganz ohne Serverzugriff.

1. Gehen Sie zu **Einstellungen > Module/Anwendungen**.
2. Öffnen Sie den Reiter **Externes Modul hinzufügen**.
3. Wählen Sie die Datei `module_splash-x.y.z.zip` aus und laden Sie sie hoch.

Dolibarr entpackt das Archiv und installiert das Modul in seinem Ordner `custom`.

> [!WARNING]
> Die Installation über die Oberfläche kann blockiert sein:
> - **Datei zu groß**: Das Archiv ist fast 2 MB groß, was dem Standardlimit von PHP entspricht. Die
>   maximale Größe hängt von Ihrem Hoster (PHP-Einstellungen `upload_max_filesize` und
>   `post_max_size`) und von Dolibarr ab (**Einstellungen > Sicherheit**, Reiter **Dateien**); das
>   geltende Limit wird neben dem Upload-Feld angezeigt;
> - **Installation externer Module deaktiviert**: Die Datei `installmodules.lock` liegt im
>   Datenordner von Dolibarr; bitten Sie Ihren Administrator, sie zu löschen;
> - **Alternatives Stammverzeichnis nicht definiert**: Der Ordner `custom` ist nicht in der Datei
>   `conf.php` von Dolibarr eingetragen.
>
> In allen Fällen können Sie stattdessen die manuelle Installation verwenden.

### Oder manuell installieren

1. Entpacken Sie das Archiv.
2. Kopieren Sie den Ordner `splash` in den Ordner `htdocs/custom` von Dolibarr, sodass
   `htdocs/custom/splash` entsteht.

### Modul aktivieren

Unter **Einstellungen > Module/Anwendungen** finden Sie das Splash-Modul in der Familie
**Schnittstellen zu externen Systemen**: Aktivieren Sie es.

![Splash-Modul in der Dolibarr-Modulliste](../assets/img/screenshot_1.png)

Das Modul ist bereit: Jetzt muss es nur noch konfiguriert werden.
