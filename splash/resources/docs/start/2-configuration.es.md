---
lang: es
permalink: start/configure
title: Configurar el módulo Splash
description: Conecte el módulo a su cuenta Splash y defina sus parámetros por defecto.
updated: 2026-09-18
translation:
    from:        fr
    source_hash: 9558c324
    mode:        llm
---

Una vez activado, el módulo debe conectarse a su cuenta Splash y recibir algunos valores por
defecto. Cuente unos minutos :stopwatch:

Para abrir su configuración, haga clic en el icono de ajustes del módulo Splash en
**Configuración > Módulos**.

### Conéctese a su cuenta Splash

Empiece por crear las claves de acceso de su servidor: en su espacio Splash, vaya a **Servidores** >
**Añadir un servidor** y anote el identificador del servidor y su clave de cifrado.

![Añadir un servidor en el espacio Splash](../assets/img/screenshot_2.png)

Introduzca después estas dos claves en el bloque **Parámetros principales** de la configuración del
módulo.

> [!IMPORTANT]
> Copie las claves tal cual, sin espacios de más ni caracteres olvidados: un solo carácter erróneo
> impide cualquier conexión.

![Claves Splash en la configuración del módulo](../assets/img/screenshot_3.png)

### Defina los parámetros por defecto

El bloque **Parámetros locales** agrupa los valores utilizados cada vez que se crea o modifica un
objeto sin un valor explícito.

![Parámetros por defecto del módulo](../assets/img/screenshot_4.png)

#### Idioma por defecto

El idioma que utiliza el módulo para comunicarse con el servidor Splash.

#### Usuario por defecto

El usuario en cuyo nombre el módulo ejecuta todas sus acciones.

> [!TIP]
> Cree un usuario dedicado a Splash: el módulo aplica la política de permisos de Dolibarr, por lo
> que este usuario debe tener permisos sobre todos los objetos que desee sincronizar.

#### Almacén, cuenta bancaria y forma de pago por defecto

Los valores utilizados cuando la otra aplicación no proporciona ninguno.

### Compruebe los resultados de las autopruebas

Cada vez que guarda la configuración, el módulo comprueba sus parámetros y verifica que la
comunicación con Splash funciona.

> [!WARNING]
> Todas las pruebas deben estar en verde: una autoprueba fallida significa que el servidor no puede
> sincronizar.

![Resultados de las autopruebas](../assets/img/screenshot_5.png)
