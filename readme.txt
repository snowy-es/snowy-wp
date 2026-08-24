=== Snowy — datos meteorológicos en vivo ===
Contributors: snowyes
Tags: meteorologia, tiempo, estaciones, aemet, avisos
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 2.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Shortcodes y bloques con datos en vivo de la red de estaciones de Snowy: extremos del día, rachas, avisos de AEMET y fichas de estación.

== Description ==

Publica en tu web datos meteorológicos medidos, no predicciones. El plugin
consulta la red de estaciones de Snowy y pinta cinco piezas, disponibles como
shortcode y como bloque de Gutenberg:

* Avisos de AEMET vigentes para hoy, mañana y pasado.
* Extremos del día: estaciones más cálidas y más frías.
* Tabla completa de estaciones con temperatura, máxima, mínima y humedad.
* Ranking de rachas de viento.
* Ficha de una estación concreta, para incrustar dentro de un artículo.
* Calidad del aire: índice europeo, PM2,5, PM10, ozono y NO₂.
* Polen: gramíneas, olivo, abedul, aliso, artemisa y ambrosía, con su nivel.

Se puede filtrar por región y los datos se cachean para no golpear la API en
cada visita. Si la API no responde, los widgets no pintan nada en vez de romper
la página.

= Hace falta una clave de API =

El plugin es libre, pero los datos no son públicos. Para usarlo necesitas una
clave de acceso a la API de Snowy. Solicítala en hola@snowy.es contando qué vas
a publicar y qué datos necesitas.

== Installation ==

1. Sube la carpeta del plugin a `/wp-content/plugins/`.
2. Actívalo en Plugins > Instalados.
3. Ve a Ajustes > Snowy e introduce tu clave de API.
4. Elige región si solo quieres una parte de la red.

Como alternativa a guardar la clave en la base de datos, se puede definir en
`wp-config.php`, y entonces tiene prioridad sobre la de los ajustes:

`define( 'SNOWY_API_KEY', 'sk_live_...' );`

== Frequently Asked Questions ==

= ¿La clave se ve desde el navegador del visitante? =

No. Las llamadas salen desde PHP con `wp_remote_get`, es decir, desde el
servidor. La clave no llega nunca al cliente.

= ¿Qué pasa si la API no responde? =

Los widgets devuelven cadena vacía y la página se sirve igual. Un fallo de la
API no puede tumbar tu web.

= ¿Puedo congelar los datos dentro de un artículo? =

Sí. Con `modo="snapshot"` los datos se guardan la primera vez que se renderiza
y a partir de ahí se pintan esos, con una nota indicando la fecha. Es lo que
evita que un artículo de hace un mes muestre la temperatura de hoy.

== Changelog ==

= 2.1.0 =
* Nuevos widgets de calidad del aire y de polen.
* El modo snapshot ya no anuncia "datos congelados" cuando no ha congelado nada,
  ni congela respuestas vacías o previsualizaciones.
* Los bloques exponen congelar, filtrar por estaciones y título, que antes solo
  estaban disponibles desde el shortcode.
* Los widgets aguantan una caída de la API sirviendo la última lectura buena.
* Blindaje de estilos frente al tema anfitrión y tablas desplazables en móvil.

= 2.0.0 =
* Primera versión distribuible: configuración por región, ajustes en el admin y
  traducciones.
* La clave de API se puede guardar desde Ajustes > Snowy, sin tocar wp-config.

= 1.0.0 =
* Versión interna con los cinco shortcodes y sus bloques.
