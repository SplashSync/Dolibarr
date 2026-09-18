---
lang: es
permalink: overview
title: Conecte Dolibarr con todas sus aplicaciones
description: Sincronice automáticamente terceros, productos, stocks, pedidos y facturas entre Dolibarr y sus demás aplicaciones con Splash Sync.
updated: 2026-09-18
translation:
    from:        fr
    source_hash: 0c35bfb2
    mode:        llm
---

Tienda online, punto de venta, CRM: cada una de sus aplicaciones contiene una parte de su
actividad. El módulo Splash convierte **Dolibarr en el centro** de todos estos datos y los mantiene
sincronizados en todo momento, sin volver a introducirlos ni exportarlos a mano. :rocket:

### ¿Por qué Splash para Dolibarr?

#### :shopping_cart: Todas sus ventas en un solo lugar

Los pedidos y facturas de su tienda online, de su punto de venta o de cualquier otra aplicación
conectada llegan directamente a Dolibarr, sea cual sea el canal de venta.

#### :package: Stocks exactos, en todas partes

Un stock actualizado en Dolibarr se actualiza también en todos sus canales de venta: se acabaron
los pedidos de productos agotados. Gestione un stock global o el stock de cada almacén por
separado.

#### :busts_in_silhouette: Una sola ficha de cliente, siempre al día

Gracias al Linker de Splash, los perfiles de un mismo cliente repartidos entre sus aplicaciones se
identifican y se fusionan. Un cambio hecho en un lugar se aplica en todas partes, del CRM a la
tienda.

#### :bar_chart: Una gestión financiera que se completa sola

Pedidos, facturas, abonos y pagos se importan automáticamente, con los tipos de IVA y las cuentas
bancarias correctos. Su seguimiento financiero se vuelve más sencillo... y sin esfuerzo.

### Qué se sincroniza

| Objeto de Dolibarr | Qué se admite |
|---|---|
| Terceros | Clientes y clientes potenciales, códigos de cliente y contable, dirección |
| Contactos | Contactos y direcciones de entrega |
| Productos | Catálogo, precios y multiprecios, stocks, imágenes, variantes, traducciones |
| Pedidos de clientes | Líneas, estados, dirección de entrega, número y PDF de la factura asociada |
| Facturas de clientes | Líneas, IVA, pagos |
| Abonos de clientes | Líneas, IVA, reembolsos |

### Pensado para el uso real

- :zap: **Importación sin fricciones**: pedidos «invitado» asociados a un cliente por defecto,
  clientes reconocidos por su correo electrónico, productos identificados por su referencia (SKU).
- :receipt: **IVA bajo control**: los códigos de IVA se reconocen y, en su defecto, se deducen del
  tipo, según su diccionario de Dolibarr.
- :house: **Direcciones limpias**: las direcciones escritas en varias líneas se dividen
  automáticamente para las aplicaciones que las esperan en varios campos.
- :globe_with_meridians: **Multilingüe**: nombres y descripciones de productos sincronizados en
  todos sus idiomas.
- :lock: **Sus reglas se aplican**: el módulo actúa en nombre de un usuario dedicado y respeta la
  política de permisos de Dolibarr.

### Compatibilidad

- Dolibarr **14 a 24**
- PHP **7.4** o superior
- Una cuenta Splash Sync activa

> [!TIP]
> La instalación y la configuración solo llevan unos minutos: siga la sección **Primeros pasos**
> de esta documentación.

### Libre y abierto a las contribuciones

El módulo es de código abierto y su código es público en
[github.com/SplashSync/Dolibarr](https://github.com/SplashSync/Dolibarr): ¡todas las
contribuciones son bienvenidas!
