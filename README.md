# Snowy para WordPress

Shortcodes y bloques de Gutenberg que publican **datos meteorológicos medidos**
—no predicciones— de la red de estaciones de [Snowy](https://snowy.es) en
cualquier WordPress.

## Qué pinta

| Shortcode | Bloque | Qué muestra |
|---|---|---|
| `[snowy_avisos]` | Snowy · Avisos de AEMET | Avisos vigentes para hoy, mañana y pasado |
| `[snowy_extremos limite="8"]` | Snowy · Extremos del día | Estaciones más cálidas y más frías |
| `[snowy_estaciones]` | Snowy · Estaciones | Tabla con temperatura, máxima, mínima y humedad |
| `[snowy_viento limite="8"]` | Snowy · Rachas de viento | Ranking de rachas máximas |
| `[snowy_estacion id="9115X"]` | Snowy · Ficha de estación | Tarjeta para incrustar en un artículo |

Los bloques aparecen escribiendo `/snowy` en el editor y **se previsualizan
dentro del editor**: `ServerSideRender` llama al mismo `render_callback` que
usa el shortcode, así que la lógica vive en un solo sitio.

## Hace falta una clave de API

El plugin es software libre bajo GPLv2. **Los datos no son públicos.**

Para que muestre algo necesitas una clave de acceso a la API de Snowy, con su
cuota y sus permisos. Es lo que sostiene la red: mantener las estaciones,
ingerir, validar y servir el dato cuesta dinero.

> **Solicita tu clave escribiendo a [hola@snowy.es](mailto:hola@snowy.es)**,
> contando qué vas a publicar y qué datos necesitas.

Medios locales, ayuntamientos, proyectos meteorológicos y desarrolladores: no
muerde nadie, escribe y lo hablamos.

## Instalación

1. Descarga el `.zip` de la [última release](https://github.com/snowy-es/snowy-wp/releases).
2. Plugins > Añadir nuevo > Subir plugin.
3. Actívalo y ve a **Ajustes > Snowy** para introducir la clave.
4. Elige región si solo quieres una parte de la red. Vacío = red completa.

La clave también se puede definir por entorno, y entonces manda sobre la de los
ajustes:

```php
define( 'SNOWY_API_KEY', 'sk_live_...' );
```

Es la opción recomendada si despliegas por CI y no quieres la clave en la base
de datos.

## Cómo se comporta

- **La clave nunca llega al navegador.** Las llamadas salen de PHP con
  `wp_remote_get`. Si algún día estos datos se pidieran con JavaScript desde el
  cliente, habría que quitar la clave: sería pública.
- **Si la API no responde, se degrada.** Los widgets devuelven cadena vacía y la
  página se sirve igual. Un fallo nuestro no puede tumbar tu web.
- **Los datos se cachean** diez minutos las estaciones y quince los avisos, con
  transients. Al guardar los ajustes la caché se vacía.
- **`modo="snapshot"`** congela los datos dentro de un post la primera vez que
  se renderiza, con una nota de la fecha. Un artículo de hace un mes no debe
  mostrar la temperatura de hoy.

## Atribución

Cada widget indica de dónde viene el dato, y los avisos atribuyen a AEMET como
fuente que manda. **Eso no se puede desactivar**: es la condición de uso.

Las estaciones de red no oficial se etiquetan como "estación de aficionado",
nunca por su nombre comercial.

## Desarrollo

```
snowy-wp.php          bootstrap, constantes y carga
includes/options.php  opciones y resolución de la clave
includes/api.php      cliente HTTP, caché y tolerancia a fallos
includes/render.php   helpers de formato, atribución y snapshots
includes/shortcodes.php
includes/blocks.php   registro de bloques sobre los mismos callbacks
includes/settings.php Ajustes > Snowy
includes/admin.php    página de ayuda con los shortcodes y los ids
```

## Licencia

GPLv2 o posterior. Ver [LICENSE](LICENSE).
