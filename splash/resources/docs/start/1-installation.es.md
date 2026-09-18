---
lang: es
permalink: start/install
title: Instalar el módulo Splash
description: Descargue el módulo, instálelo desde la interfaz de Dolibarr o manualmente y actívelo.
updated: 2026-09-18
translation:
    from:        fr
    source_hash: 377a3cd1
    mode:        llm
---

### Requisitos

- Dolibarr **14** o superior
- PHP **7.4** o superior
- Una cuenta Splash Sync activa

### Descargue el módulo

Descargue la última versión del módulo directamente desde esta página: es el archivo
`module_splash-x.y.z.zip`.

¿Necesita una versión anterior? Todas las versiones publicadas están disponibles en la
[página de versiones de GitHub](https://github.com/SplashSync/Dolibarr/releases).

> [!NOTE]
> Para instalar desde la interfaz, no descomprima el archivo: Dolibarr espera el archivo `.zip`
> tal cual.

### Instale desde la interfaz de Dolibarr

Es el método más sencillo, sin necesidad de acceder al servidor.

1. Vaya a **Configuración > Módulos**.
2. Abra la pestaña **Instalar módulo externo**.
3. Seleccione el archivo `module_splash-x.y.z.zip` y envíelo.

Dolibarr descomprime el archivo e instala el módulo en su carpeta `custom`.

> [!WARNING]
> La instalación desde la interfaz puede estar bloqueada:
> - **archivo demasiado grande**: el archivo pesa casi 2 MB, que es el límite por defecto de PHP.
>   El tamaño máximo depende de su proveedor de alojamiento (parámetros PHP `upload_max_filesize` y
>   `post_max_size`) y de Dolibarr (**Configuración > Seguridad**, pestaña **Archivos**); el límite
>   vigente se muestra junto al campo de envío;
> - **instalación de módulos externos desactivada**: el archivo `installmodules.lock` está presente
>   en la carpeta de datos de Dolibarr; pida a su administrador que lo elimine;
> - **carpeta raíz alternativa no definida**: la carpeta `custom` no está declarada en el archivo
>   `conf.php` de Dolibarr.
>
> En todos los casos, también puede recurrir a la instalación manual.

### O instale manualmente

1. Descomprima el archivo.
2. Copie la carpeta `splash` en la carpeta `htdocs/custom` de Dolibarr, para obtener
   `htdocs/custom/splash`.

### Active el módulo

En **Configuración > Módulos**, el módulo Splash se encuentra en la familia **Interfaces con
sistemas extrenos**: actívelo.

![Módulo Splash en la lista de módulos de Dolibarr](../assets/img/screenshot_1.png)

El módulo está listo: solo queda configurarlo.
