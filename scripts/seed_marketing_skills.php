<?php
declare(strict_types=1);

/**
 * Habilidades de marketing y creación de contenido.
 *
 * Las reglas de cumplimiento que aparecen aquí están tomadas de fuentes
 * primarias y se citan en docs/investigacion-marketing.md: la guía CAN-SPAM de
 * la FTC para correo, las Endorsement Guides de la FTC para patrocinios, y la
 * documentación de Google sobre contenido útil y E-E-A-T para buscadores.
 * Ninguna sustituye asesoría legal, y las habilidades lo dicen.
 *
 * @return array<int,array<string,mixed>>
 */
function marketingSkillDefinitions(): array
{
    return [

        // ------------------------------------------ Estrategia y contenido
        [
            'name'  => 'Escribir un brief de contenido',
            'cat'   => 'seo-y-contenido-organico',
            'short' => 'Convierte un tema en un encargo cerrado: intención, ángulo, fuentes obligatorias y criterio de terminado.',
            'tags'  => 'contenido, brief, seo',
            'body'  => <<<'MD'
## Objetivo

Dejar el encargo tan cerrado que escribir sea casi transcribir. Un brief flojo
no ahorra tiempo: lo traslada a las rondas de corrección.

## Instrucciones

Decides antes de escribir tres cosas que casi siempre se deciden tarde: para
quién es, qué pregunta responde y con qué pruebas se sostiene.

## Workflow

1. Escribe la consulta objetivo y la intención detrás: informarse, comparar, decidir o hacer.
2. Escribe la respuesta corta que la persona busca, en dos frases. Si no sale, el tema no está listo.
3. Define a quién va dirigido y qué sabe ya, para no explicarle lo que domina.
4. Lista las entidades y subtemas que el texto debe cubrir para ser completo.
5. Arma el esqueleto de encabezados con la pregunta que resuelve cada uno.
6. Nombra las fuentes obligatorias: dato, estudio, documentación o experiencia propia.
7. Define el criterio de terminado y la acción que debe provocar el texto.

## Reglas

- Una pieza, una intención. Si mezcla comparar y aprender, son dos piezas.
- No encargues una cifra sin decir de dónde debe salir.
- Si el tema exige experiencia de primera mano y nadie del equipo la tiene, dilo en el brief.
- No pidas una extensión en palabras: pide la cobertura que el tema necesita.

## Inputs

- tema: de qué trata
- consulta: la búsqueda o pregunta objetivo
- audiencia: a quién va dirigido y su nivel
- activos: datos, casos o citas disponibles

## Outputs

- brief: intención, respuesta corta, audiencia, entidades, esqueleto, fuentes
- criterio_terminado: qué debe cumplir para aprobarse
- riesgos: afirmaciones que necesitarán revisión

## Ejemplos

De «facturación electrónica» sale un brief con intención comparativa, respuesta
corta en dos frases, seis encabezados y dos fuentes obligatorias, más un aviso:
el apartado de plazos legales requiere revisión antes de publicar.
MD,
        ],
        [
            'name'  => 'Investigar palabras clave por intención',
            'cat'   => 'seo-y-contenido-organico',
            'short' => 'Agrupa consultas por lo que la persona quiere lograr, no por volumen, y dice qué formato pide cada grupo.',
            'tags'  => 'seo, investigación, intención',
            'body'  => <<<'MD'
## Objetivo

Saber qué escribir y en qué formato, en lugar de acumular una lista de términos
con volumen que nadie sabe cómo usar.

## Instrucciones

Agrupas por intención antes que por volumen. Dos consultas con el mismo tema y
distinta intención no van en la misma página.

## Workflow

1. Reúne las consultas semilla y sus variantes.
2. Clasifica cada una: informativa, comparativa, transaccional o de navegación.
3. Agrupa las que se responden con la misma página. Si una página no puede
   responder dos consultas sin contradecirse, van separadas.
4. Para cada grupo, di qué formato pide: guía, comparativa, plantilla, calculadora, ficha.
5. Marca los grupos donde ya existe contenido propio que conviene mejorar antes que duplicar.
6. Ordena por cercanía a la decisión de compra, no por volumen.

## Reglas

- El volumen ordena dentro de un grupo, nunca entre grupos.
- Si dos páginas propias compiten por el mismo grupo, señálalo: se fusionan, no se suman.
- No inventes volúmenes ni dificultades: si no tienes el dato, escribe «sin dato».
- Una consulta que sólo responde una herramienta no se resuelve con un artículo.

## Inputs

- semillas: términos de partida
- datos: volumen y dificultad si están disponibles
- inventario: páginas propias existentes

## Outputs

- grupos: consultas agrupadas con su intención y formato recomendado
- prioridad: orden por cercanía a la decisión
- canibalizaciones: páginas propias que compiten entre sí

## Ejemplos

Quince consultas sobre un mismo producto salen como cuatro grupos: una guía
informativa, una comparativa, una página de precio y una de integraciones.
MD,
        ],
        [
            'name'  => 'Auditar una página para buscadores',
            'cat'   => 'seo-y-contenido-organico',
            'short' => 'Revisa si la página responde de verdad la intención y si demuestra experiencia, no sólo si cumple la lista técnica.',
            'tags'  => 'seo, auditoría, contenido',
            'body'  => <<<'MD'
## Objetivo

Encontrar por qué una página no rinde, empezando por si es útil y no por si le
falta una palabra clave en el título.

## Instrucciones

Evalúas primero utilidad y luego señales técnicas. Una página impecable en lo
técnico que no responde lo que se buscaba no se arregla con etiquetas.

## Workflow

1. Identifica la consulta objetivo y comprueba si la página la responde en el primer tercio.
2. Comprueba las señales de experiencia: quién firma, qué acredita, si hay datos o casos propios.
3. Revisa la cobertura: qué subtemas espera alguien que busca eso y no están.
4. Revisa las señales técnicas: título, encabezados, enlaces internos, datos estructurados.
5. Revisa los enlaces internos de entrada: una página sin enlaces internos es huérfana.
6. Ordena los hallazgos por impacto sobre la utilidad, no por facilidad de arreglo.

## Reglas

- Nunca recomiendes repetir un término para «reforzar» la relevancia.
- Si la página no aporta nada que no esté en las diez primeras posiciones, dilo: el problema es el ángulo, no el formato.
- Un dato sin fuente es un hallazgo de confianza, no de estilo.
- No propongas reescribir entera una página que sólo necesita tres párrafos nuevos.

## Inputs

- pagina: contenido y metadatos
- consulta: intención objetivo
- competencia: qué ofrecen los resultados que la superan

## Outputs

- diagnostico: en una frase, por qué no rinde
- hallazgos: lista ordenada por impacto con la corrección concreta
- señales_experiencia: qué falta para demostrar quién sabe de esto

## Ejemplos

Una guía sin autor identificado, sin datos propios y sin enlaces internos recibe
tres hallazgos de confianza por encima de cualquier ajuste de título.
MD,
        ],
        [
            'name'  => 'Construir un clúster temático',
            'cat'   => 'seo-y-contenido-organico',
            'short' => 'Organiza un tema en página central y satélites, con el enlazado interno que los sostiene.',
            'tags'  => 'seo, arquitectura, contenido',
            'body'  => <<<'MD'
## Objetivo

Que un tema se cubra entero y que las páginas se sostengan entre sí, en lugar de
publicar piezas sueltas que compiten por lo mismo.

## Instrucciones

Defines una página central que cubre el tema de forma amplia y satélites que
profundizan en cada subtema, con enlaces en ambas direcciones.

## Workflow

1. Define el tema central y la promesa de la página principal.
2. Lista los subtemas que alguien interesado necesita entender.
3. Descarta los subtemas que no justifican una página propia: van como sección.
4. Asigna a cada satélite su consulta y su ángulo, sin solaparse con otro.
5. Define el enlazado: cada satélite enlaza a la central, la central enlaza a todos.
6. Marca qué contenido ya existe y qué hay que crear.

## Reglas

- Dos satélites no pueden responder la misma consulta: se fusionan.
- La página central no es un índice de enlaces: tiene que valer por sí sola.
- No crees un satélite para el que no haya nada real que decir.
- El texto del enlace describe el destino, nunca es «aquí» ni «leer más».

## Inputs

- tema: asunto central
- grupos: consultas agrupadas por intención
- inventario: contenido existente

## Outputs

- mapa: central y satélites con su consulta y ángulo
- enlazado: qué enlaza con qué y con qué texto
- plan: qué crear, qué mejorar y qué fusionar

## Ejemplos

Un clúster sobre nómina sale con una central y siete satélites, tres de ellos ya
escritos y uno que absorbe a otro por solaparse.
MD,
        ],
        [
            'name'  => 'Escribir un artículo con fuentes',
            'cat'   => 'seo-y-contenido-organico',
            'short' => 'Redacta a partir de un brief citando lo que sostiene cada afirmación y marcando lo que no se puede sostener.',
            'tags'  => 'redacción, contenido, fuentes',
            'body'  => <<<'MD'
## Objetivo

Producir un texto que alguien con criterio pueda leer sin encontrar una sola
afirmación que no se sostenga.

## Instrucciones

Escribes desde el brief. Cada cifra, fecha o afirmación fuerte lleva su fuente o
una marca de que falta. Las citas sostienen el argumento, no lo decoran.

## Workflow

1. Responde la pregunta principal en el primer tercio del texto.
2. Desarrolla siguiendo el esqueleto del brief, una idea por encabezado.
3. Ancla cada afirmación verificable a su fuente, con el dato y el año.
4. Marca con un aviso toda afirmación que no hayas podido sostener.
5. Cierra con lo que la persona debe hacer con lo que acaba de leer.
6. Repasa y elimina lo que no cambia nada para quien lee.

## Reglas

- Nunca inventes una cifra, un estudio, una cita ni un caso de cliente.
- Si no encuentras fuente para un dato del brief, escríbelo como pendiente, no lo suavices.
- No abras con una definición de diccionario ni con «en el mundo actual».
- Las afirmaciones sobre resultados, salud, dinero o legalidad se marcan para revisión.

## Inputs

- brief: encargo cerrado
- fuentes: material de apoyo disponible
- voz: guía de tono de la marca

## Outputs

- articulo: texto completo
- fuentes_citadas: lista con dato, origen y año
- pendientes: afirmaciones sin respaldo
- revisar: lo que necesita visto bueno legal o técnico

## Ejemplos

Un artículo de mil palabras entrega nueve fuentes citadas, dos afirmaciones
marcadas como pendientes y una frase señalada para revisión legal.
MD,
        ],
        [
            'name'  => 'Escribir titulares y entradillas',
            'cat'   => 'marca-y-mensaje',
            'short' => 'Produce varias opciones de primera línea y explica qué promete cada una, para elegir con criterio.',
            'tags'  => 'copywriting, titulares, contenido',
            'body'  => <<<'MD'
## Objetivo

Que la primera línea haga que se lea la segunda, sin prometer algo que el texto
no cumple.

## Instrucciones

Generas varias opciones con ángulos distintos y dices qué promete cada una. No
eliges por ti: das el criterio para elegir.

## Workflow

1. Extrae la promesa real del contenido en una frase.
2. Escribe opciones desde ángulos distintos: el problema, el resultado, el dato, la objeción, la contra-intuición.
3. Descarta las que prometen más de lo que el texto entrega.
4. Escribe la entradilla que sostiene el titular elegido en dos o tres frases.
5. Indica para cada opción a quién habla y qué espera quien hace clic.

## Reglas

- Un titular que el texto no cumple destruye más confianza de la que gana en clics.
- Nada de «esto cambiará tu forma de», «el secreto que nadie te cuenta» ni cuentas atrás falsas.
- Una cifra en el titular exige que la cifra esté en el texto con su fuente.
- No abras con pregunta retórica ni con una negación que se responde sola.

## Inputs

- contenido: el texto o su brief
- canal: dónde se publica
- audiencia: a quién va dirigido

## Outputs

- opciones: cinco titulares con su ángulo y su promesa
- recomendado: cuál y por qué
- entradilla: párrafo de apoyo

## Ejemplos

Para una guía de conciliación salen cinco titulares: dos por problema, uno por
dato, uno por objeción y uno contra-intuitivo, con el dato verificado.
MD,
        ],
        [
            'name'  => 'Adaptar un contenido a otro canal',
            'cat'   => 'marketing-y-contenido',
            'short' => 'Reescribe una pieza para el formato y el ritmo del canal destino, sin copiarla y pegarla.',
            'tags'  => 'contenido, canales, adaptación',
            'body'  => <<<'MD'
## Objetivo

Que la misma idea funcione en otro sitio. Copiar y pegar entre canales es la
forma más rápida de que no funcione en ninguno.

## Instrucciones

Identificas qué cambia en el canal destino: la longitud, el ritmo, el gancho, la
tolerancia al enlace y lo que la gente hace al leerlo.

## Workflow

1. Extrae la idea central y el dato que la sostiene.
2. Define qué espera alguien en el canal destino y cuánto tiempo va a dar.
3. Reescribe el gancho para ese canal: lo que abre un correo no abre un vídeo.
4. Ajusta longitud, formato y densidad de enlaces.
5. Adapta la llamada a la acción a lo que se puede hacer desde ahí.

## Reglas

- No trocees un texto largo en fragmentos: reescribe.
- Respeta las convenciones del canal, pero no imites lo que todos hacen ahí.
- Un enlace en un canal que los penaliza va en el primer comentario o en el perfil, y lo dices.
- No repitas la misma pieza en dos canales el mismo día con el mismo gancho.

## Inputs

- original: pieza de partida
- destino: canal y formato
- objetivo: qué se busca en ese canal

## Outputs

- adaptacion: pieza lista para publicar
- cambios: qué se modificó y por qué
- llamada_accion: la que corresponde al canal

## Ejemplos

Un artículo de mil palabras se convierte en una publicación de doscientas con un
gancho distinto, una sola idea y el enlace fuera del cuerpo.
MD,
        ],
        [
            'name'  => 'Convertir una pieza larga en varias cortas',
            'cat'   => 'marketing-y-contenido',
            'short' => 'Saca de un contenido extenso las piezas independientes que valen por sí solas.',
            'tags'  => 'contenido, repurposing, productividad',
            'body'  => <<<'MD'
## Objetivo

Aprovechar el trabajo ya hecho sin publicar diez veces lo mismo con otro título.

## Instrucciones

Buscas las ideas que se sostienen solas. Una idea que necesita el contexto del
artículo entero no es una pieza aparte.

## Workflow

1. Lista las afirmaciones del original que tienen valor por sí solas.
2. Descarta las que sólo se entienden con lo anterior.
3. Para cada una, decide formato: publicación corta, hilo, gráfico, correo, guion de vídeo.
4. Reescribe cada pieza con su propio gancho y su propio cierre.
5. Propón un orden y un espaciado en el calendario.

## Reglas

- Si dos piezas dicen lo mismo con otras palabras, sólo sobrevive una.
- Cada pieza se entiende sin haber leído el original.
- No publiques dos derivadas del mismo original en menos de una semana.
- No conviertas en cita lo que era una hipótesis del texto.

## Inputs

- original: contenido extenso
- canales: dónde se van a publicar
- ritmo: cada cuánto se publica

## Outputs

- piezas: cada una con formato, gancho y texto
- descartadas: ideas que no aguantan solas y por qué
- calendario: orden y fechas propuestas

## Ejemplos

Un informe de ocho páginas produce cinco piezas: dos publicaciones, un gráfico
con su pie, un correo y un guion de sesenta segundos.
MD,
        ],
        [
            'name'  => 'Planificar el calendario editorial',
            'cat'   => 'marketing-y-contenido',
            'short' => 'Reparte los temas en el tiempo según capacidad real, dependencias y fechas que no se mueven.',
            'tags'  => 'contenido, planificación, calendario',
            'body'  => <<<'MD'
## Objetivo

Un calendario que se cumpla. Uno que asume que cada semana se produce el doble
de lo que se produce no es un plan, es una lista de deseos.

## Instrucciones

Partes de la capacidad real del equipo, no de la deseada, y colocas primero lo
que tiene fecha inamovible.

## Workflow

1. Ubica las fechas que no se mueven: lanzamientos, eventos, cierres, temporada.
2. Calcula la capacidad real por semana, en piezas terminadas, no en ideas.
3. Coloca las piezas que dependen de otra después de aquella de la que dependen.
4. Alterna formatos para no encadenar tres piezas del mismo tipo.
5. Deja al menos un hueco al mes para reaccionar a algo no previsto.
6. Asigna responsable y fecha de entrega del borrador, no sólo de publicación.

## Reglas

- La capacidad se mide en piezas publicadas del trimestre anterior, no en estimaciones.
- Una pieza sin responsable no entra al calendario.
- La fecha que importa es la del borrador: la de publicación se deduce de ella.
- No llenes el calendario al cien por cien.

## Inputs

- temas: piezas planificadas con su esfuerzo
- equipo: quién produce y cuánto
- fechas: hitos inamovibles

## Outputs

- calendario: semana a semana con pieza, formato y responsable
- fuera: lo que no entró y a qué periodo pasa
- carga: porcentaje de capacidad comprometido

## Ejemplos

Doce temas y capacidad de dos piezas por semana producen ocho semanas de
calendario, tres temas desplazados y un hueco libre al mes.
MD,
        ],

        // ---------------------------------------------------- Redes sociales
        [
            'name'  => 'Escribir una publicación para LinkedIn',
            'cat'   => 'redes-sociales',
            'short' => 'Construye una publicación con gancho, desarrollo y cierre, sin la retórica hueca del canal.',
            'tags'  => 'redes, linkedin, copywriting',
            'body'  => <<<'MD'
## Objetivo

Que alguien que hace scroll se detenga, lea entero y sepa qué hacer después.

## Instrucciones

Escribes en primera persona cuando hay experiencia detrás y en tercera cuando no.
Una idea por publicación. El gancho se trabaja aparte del resto.

## Workflow

1. Reduce la idea a una frase.
2. Escribe las dos primeras líneas pensando en que es lo único que se ve sin desplegar.
3. Desarrolla en frases cortas con un ejemplo concreto o una cifra con fuente.
4. Cierra con una acción clara o una pregunta que se pueda responder de verdad.
5. Ajusta el formato: párrafos de una o dos líneas y espacios en blanco.

## Reglas

- Nada de historias personales inventadas ni de anécdotas que no ocurrieron.
- No empieces con «Hace 3 años estaba en la quiebra» ni con una frase de una sola palabra seguida de puntos suspensivos.
- Como máximo tres etiquetas, y sólo si alguien las sigue.
- Si citas una cifra, la fuente va en el texto o en el primer comentario.

## Inputs

- idea: de qué trata
- prueba: dato, ejemplo o experiencia que la sostiene
- objetivo: qué se busca con la publicación

## Outputs

- ganchos: tres opciones de primeras dos líneas
- publicacion: texto completo
- comentario_apoyo: enlace o fuente si corresponde

## Ejemplos

De un hallazgo de auditoría sale una publicación con gancho en dos líneas, un
caso concreto sin nombrar al cliente y una pregunta operativa al cierre.
MD,
        ],
        [
            'name'  => 'Escribir un guion para vídeo corto',
            'cat'   => 'redes-sociales',
            'short' => 'Estructura un guion de menos de un minuto con gancho, desarrollo y cierre, marcando lo visual.',
            'tags'  => 'vídeo, guion, redes',
            'body'  => <<<'MD'
## Objetivo

Que los tres primeros segundos ganen los cincuenta siguientes.

## Instrucciones

Escribes para ser dicho en voz alta, no leído. Marcas qué se ve en cada momento,
porque en vídeo corto lo visual sostiene el ritmo.

## Workflow

1. Escribe el gancho de los tres primeros segundos: una afirmación concreta, nunca una presentación.
2. Plantea el problema en una frase.
3. Desarrolla en dos o tres puntos, uno por plano.
4. Cierra con la acción, dicha una sola vez.
5. Anota junto a cada línea qué se ve y qué texto va en pantalla.
6. Léelo en voz alta y recorta todo lo que no se diría hablando.

## Reglas

- Nunca empieces presentándote: eso va al final si va.
- Una idea por vídeo. Si hay dos, son dos vídeos.
- El texto en pantalla no repite literalmente lo que se dice.
- Si el dato necesita contexto de treinta segundos, no es para este formato.

## Inputs

- idea: mensaje central
- duracion: segundos objetivo
- formato: vertical u horizontal, con o sin voz

## Outputs

- guion: líneas con su tiempo aproximado
- planos: qué se ve en cada línea
- texto_pantalla: rótulos
- gancho_alternativo: una segunda opción de apertura

## Ejemplos

Un guion de cuarenta y cinco segundos sale con gancho de tres, problema de
cinco, tres puntos de diez y cierre de siete, con cinco planos marcados.
MD,
        ],
        [
            'name'  => 'Responder comentarios y menciones',
            'cat'   => 'redes-sociales',
            'short' => 'Clasifica lo que llega, propone la respuesta y marca lo que debe escalar antes de contestar.',
            'tags'  => 'redes, comunidad, atención',
            'body'  => <<<'MD'
## Objetivo

Responder rápido sin crear un problema mayor. En redes, la respuesta apresurada
cuesta más que el silencio de una hora.

## Instrucciones

Clasificas antes de redactar. No todo comentario merece respuesta, y algunos no
deben responderse desde la marca en absoluto.

## Workflow

1. Clasifica: duda, queja, elogio, crítica pública, troleo, o asunto legal.
2. Decide si responder en público, pasar a privado, o no responder.
3. Para dudas y quejas, redacta una respuesta que reconozca y resuelva o derive.
4. Marca lo que debe escalar: seguridad, datos personales, salud, dinero, amenaza legal.
5. Señala los patrones: si tres personas preguntan lo mismo, falta contenido, no respuestas.

## Reglas

- Nunca discutas en público con quien busca discutir.
- No borres una crítica legítima: se responde o se deja.
- Nada de respuestas sarcásticas desde la cuenta de marca, por ingeniosas que parezcan.
- Un asunto legal o de datos personales no se responde: se escala.

## Inputs

- comentarios: texto, autor y contexto de la publicación
- politica: tono y límites de la marca

## Outputs

- clasificacion: tipo y decisión por comentario
- respuestas: borradores para los que sí se responden
- escalar: los que requieren otra persona
- patrones: preguntas repetidas que piden contenido propio

## Ejemplos

De cuarenta comentarios salen doce respuestas, tres derivaciones a privado, dos
escalados y un patrón: seis personas preguntan por el precio en la misma pieza.
MD,
        ],

        // -------------------------------------------------- Email marketing
        [
            'name'  => 'Diseñar una secuencia de correos',
            'cat'   => 'email-y-automatizacion',
            'short' => 'Define los correos, su orden, sus disparadores y las condiciones de salida de una automatización.',
            'tags'  => 'email, automatización, secuencias',
            'body'  => <<<'MD'
## Objetivo

Que la secuencia acompañe en lugar de perseguir, y que sepa cuándo callarse.

## Instrucciones

Diseñas el recorrido completo antes de escribir el primer correo: qué dispara la
entrada, qué hace salir y qué pasa si alguien no abre nada.

## Workflow

1. Define el disparador de entrada y el estado de la persona al entrar.
2. Define el objetivo de la secuencia en una frase y cómo se mide.
3. Define las condiciones de salida: conversión, respuesta, baja, inactividad.
4. Diseña cada correo: qué aporta y por qué está ahí. Si no lo sabes, sobra.
5. Fija el espaciado según el ciclo real de decisión, no según lo que aguanta la herramienta.
6. Añade una bifurcación por comportamiento sólo si cambia el contenido de verdad.

## Reglas

- Toda secuencia tiene salida por conversión. Seguir escribiendo a quien ya compró destruye confianza.
- Un correo que sólo dice «¿viste mi correo anterior?» no es un correo: se elimina.
- Sin apertura en los tres primeros, el resto no se envía.
- Cada correo lleva un único objetivo y una única llamada a la acción.

## Inputs

- disparador: qué hace entrar a alguien
- objetivo: qué se busca
- ciclo: cuánto tarda la decisión
- activos: contenidos disponibles

## Outputs

- secuencia: correos con objetivo, espaciado y asunto propuesto
- salidas: condiciones que interrumpen
- metricas: qué medir en cada paso

## Ejemplos

Una secuencia de bienvenida sale con cuatro correos en once días, salida por
conversión y por respuesta, y corte si no hay apertura en los tres primeros.
MD,
        ],
        [
            'name'  => 'Escribir el asunto de un correo',
            'cat'   => 'email-y-automatizacion',
            'short' => 'Produce asuntos que describen lo que hay dentro y no queman la confianza para conseguir una apertura.',
            'tags'  => 'email, copywriting, asuntos',
            'body'  => <<<'MD'
## Objetivo

Que se abra por la razón correcta. Una apertura conseguida con engaño se paga en
la siguiente, y en la baja.

## Instrucciones

Escribes varias opciones y dices qué promete cada una. Compruebas que el cuerpo
del correo cumple lo que el asunto anuncia.

## Workflow

1. Resume en una frase qué hay dentro del correo.
2. Escribe opciones con ángulos distintos: el beneficio, el dato, la pregunta concreta, la utilidad directa.
3. Comprueba que cada opción se cumple al abrir. Si no, se descarta.
4. Escribe el texto de vista previa como continuación del asunto, no como repetición.
5. Ajusta la longitud para que no se corte en móvil.

## Reglas

- El asunto no puede ser engañoso ni sobre el contenido ni sobre el remitente: es un requisito de la normativa de correo comercial, no una preferencia de estilo.
- Nada de «RE:» ni «FW:» falsos, ni de urgencia inventada, ni de mayúsculas sostenidas.
- No prometas un descuento, un archivo ni un plazo que el correo no contiene.
- Un emoji como máximo, y sólo si aporta.

## Inputs

- correo: contenido del mensaje
- audiencia: a quién se envía
- contexto: qué recibió antes esta persona

## Outputs

- asuntos: cinco opciones con su ángulo
- vista_previa: texto de preencabezado para cada uno
- descartados: los que no cumplen lo que prometen

## Ejemplos

Para un correo con una plantilla adjunta salen cinco asuntos, dos descartados
por prometer un descuento que el correo no incluye.
MD,
        ],
        [
            'name'  => 'Revisar el cumplimiento de un envío de correo',
            'cat'   => 'email-y-automatizacion',
            'short' => 'Comprueba remitente, asunto, identificación, dirección postal y baja antes de que el correo salga.',
            'tags'  => 'email, cumplimiento, legal',
            'body'  => <<<'MD'
## Objetivo

Que un envío comercial no incumpla lo básico. Los requisitos son pocos y
concretos, y saltárselos sale caro.

## Instrucciones

Revisas el correo contra la lista de requisitos de la normativa de correo
comercial de Estados Unidos, que es la referencia más extendida. No emites
opinión legal: señalas lo que falta y recomiendas revisión.

## Workflow

1. Comprueba que los campos de remitente, destinatario y respuesta identifican a quien realmente envía.
2. Comprueba que el asunto describe el contenido y no induce a error.
3. Comprueba que el mensaje se identifica como publicidad cuando lo es.
4. Comprueba que aparece una dirección postal física válida del remitente.
5. Comprueba que hay un mecanismo de baja visible, comprensible y que funciona.
6. Comprueba que la baja no exige crear cuenta, pagar ni rellenar un formulario largo.
7. Revisa que exista base para escribir a esa lista y de dónde salió cada contacto.

## Reglas

- La baja debe seguir operativa al menos treinta días después del envío, y atenderse en un plazo máximo de diez días hábiles.
- Un correo sin dirección postal es un incumplimiento, no un descuido de maquetación.
- Nunca confirmes que un envío «es legal»: confirma qué requisitos cumple y recomienda revisión profesional.
- Si la lista se compró o se extrajo de un directorio, señálalo como riesgo antes de enviar.

## Inputs

- correo: asunto, remitente, cuerpo y pie
- lista: origen de los contactos
- empresa: datos del remitente

## Outputs

- checklist: requisito, cumple o no, y qué falta
- bloqueantes: lo que impide enviar
- aviso: recordatorio de que esto no sustituye asesoría legal

## Ejemplos

Un correo con baja en imagen y sin dirección postal devuelve dos bloqueantes y
una recomendación de revisión antes del envío.
MD,
        ],

        // ------------------------------------------ Publicidad y adquisición
        [
            'name'  => 'Escribir variantes de anuncio',
            'cat'   => 'publicidad-y-adquisicion',
            'short' => 'Produce variantes que prueban una hipótesis distinta cada una, no sinónimos del mismo texto.',
            'tags'  => 'publicidad, copywriting, pruebas',
            'body'  => <<<'MD'
## Objetivo

Que cada variante enseñe algo. Cambiar una palabra y llamarlo prueba sólo gasta
presupuesto.

## Instrucciones

Cada variante cambia una sola cosa y responde a una hipótesis explícita. Escribes
la hipótesis antes que el texto.

## Workflow

1. Escribe la promesa central y la objeción principal del público.
2. Formula tres hipótesis distintas: por beneficio, por objeción, por identidad.
3. Escribe una variante por hipótesis, cambiando sólo el elemento que se prueba.
4. Verifica que el destino de cada variante cumple lo que el anuncio promete.
5. Define qué resultado confirmaría o descartaría cada hipótesis.

## Reglas

- Si dos variantes prueban lo mismo, una sobra.
- El anuncio no puede prometer algo que la página de destino no entrega.
- Nada de cuentas atrás falsas, escasez inventada ni resultados sin respaldo.
- Un testimonio o una cifra en un anuncio necesitan origen documentado antes de publicarse.

## Inputs

- producto: qué se ofrece
- audiencia: a quién y qué le frena
- destino: página de aterrizaje
- formato: límites de caracteres del canal

## Outputs

- variantes: texto, hipótesis y elemento que cambia
- criterio: qué resultado valida cada hipótesis
- revisar: afirmaciones que necesitan respaldo

## Ejemplos

Tres variantes para un mismo producto prueban ahorro de tiempo, miedo al error y
pertenencia a un gremio, con el mismo destino y la misma oferta.
MD,
        ],
        [
            'name'  => 'Estructurar una campaña de pago',
            'cat'   => 'publicidad-y-adquisicion',
            'short' => 'Organiza campañas, conjuntos y anuncios con una nomenclatura que sobreviva al análisis.',
            'tags'  => 'publicidad, estructura, operación',
            'body'  => <<<'MD'
## Objetivo

Que dentro de tres meses se pueda saber qué funcionó, sin adivinar qué
significaba el nombre de una campaña.

## Instrucciones

Defines una estructura que separe lo que quieres poder comparar y una
nomenclatura que no dependa de que alguien recuerde la convención.

## Workflow

1. Define el objetivo de la campaña y la conversión que la mide.
2. Separa por lo que necesitas comparar: mercado, público, oferta o formato.
3. No fragmentes más allá de lo que el presupuesto puede alimentar.
4. Define la nomenclatura: mismo orden de campos, minúsculas, sin espacios ni tildes.
5. Haz que el nombre de la campaña coincida con la etiqueta de campaña del enlace.
6. Documenta la convención donde el equipo la vea, no en la cabeza de quien la creó.

## Reglas

- Un conjunto sin presupuesto suficiente para salir de aprendizaje no se lanza: se fusiona.
- El nombre no lleva fechas sueltas sin formato: usa un patrón fijo.
- Nunca uses mayúsculas ni tildes en los nombres: rompen el análisis por diferencias invisibles.
- Si una separación no se va a usar para decidir, no separes.

## Inputs

- objetivo: qué se busca
- presupuesto: total y por periodo
- publicos: segmentos disponibles
- ofertas: propuestas a probar

## Outputs

- estructura: campañas, conjuntos y anuncios
- nomenclatura: patrón con un ejemplo real
- presupuesto: reparto y mínimo por conjunto

## Ejemplos

Una campaña con dos mercados y tres ofertas sale como dos campañas y tres
conjuntos cada una, con el mínimo por conjunto documentado.
MD,
        ],
        [
            'name'  => 'Etiquetar campañas con UTM',
            'cat'   => 'analitica-de-marketing',
            'short' => 'Construye enlaces etiquetados con una convención consistente para que el informe no llegue sucio.',
            'tags'  => 'analítica, utm, trazabilidad',
            'body'  => <<<'MD'
## Objetivo

Que el informe de fuentes sea legible sin tener que limpiarlo a mano cada mes.

## Instrucciones

Aplicas una convención única. La consistencia importa más que la elegancia: los
parámetros distinguen mayúsculas, así que dos formas del mismo valor se
convierten en dos filas distintas.

## Workflow

1. Identifica la plataforma, el medio y la campaña.
2. Escribe la fuente en minúsculas y sin el dominio completo.
3. Usa el medio para el tipo de tráfico, no para la plataforma.
4. Haz que la campaña coincida exactamente con su nombre en la plataforma de anuncios.
5. Usa el parámetro de contenido para distinguir creatividades dentro de la misma campaña.
6. Verifica que el enlace final funciona y que no arrastra parámetros duplicados.

## Reglas

- Todo en minúsculas, sin espacios, sin tildes y sin caracteres especiales.
- Nunca etiquetes enlaces internos de tu propio sitio: rompen la atribución de la sesión.
- No pongas datos personales en los parámetros: viajan en la URL y quedan registrados.
- Si la campaña se llama distinto en la plataforma y en la etiqueta, el informe queda partido.

## Inputs

- destino: URL de la página
- plataforma: dónde se publica
- campana: nombre en la plataforma de anuncios
- creatividad: variante, si aplica

## Outputs

- enlaces: URL etiquetada por creatividad
- convencion: patrón usado, para documentarlo
- avisos: inconsistencias detectadas contra etiquetas anteriores

## Ejemplos

Tres creatividades de una misma campaña producen tres enlaces que sólo se
diferencian en el parámetro de contenido.
MD,
        ],
        [
            'name'  => 'Leer un informe de campaña',
            'cat'   => 'analitica-de-marketing',
            'short' => 'Interpreta los resultados, separa señal de ruido y dice qué decisión toca, no sólo qué pasó.',
            'tags'  => 'analítica, informes, decisiones',
            'body'  => <<<'MD'
## Objetivo

Salir del informe con una decisión, no con una descripción de lo que ya se veía
en el panel.

## Instrucciones

Empiezas por la métrica que importa para el negocio y bajas desde ahí. Si un
dato no puede cambiar una decisión, no entra en el informe.

## Workflow

1. Compara el resultado contra el objetivo y contra el periodo anterior.
2. Comprueba si el volumen basta para concluir algo. Si no, dilo y no concluyas.
3. Busca de dónde viene la variación: alcance, clic, conversión o valor.
4. Separa lo que cambió por decisión propia de lo que cambió por estacionalidad o mercado.
5. Formula una recomendación con acción, responsable y qué esperas que ocurra.

## Reglas

- Nunca declares ganadora una variante sin volumen suficiente para sostenerlo.
- Una mejora en clics con caída en conversión no es una mejora: dilo.
- No atribuyas una subida a una acción propia sin comprobar que coincide en el tiempo.
- Si la medición tiene un problema conocido, eso va primero, antes que cualquier conclusión.

## Inputs

- resultados: métricas del periodo
- objetivo: lo que se buscaba
- historico: periodos anteriores
- cambios: qué se modificó y cuándo

## Outputs

- resumen: tres líneas con lo esencial
- lectura: qué explica la variación
- decisiones: acción, responsable y resultado esperado
- limitaciones: qué no se puede concluir con estos datos

## Ejemplos

Un mes con coste por adquisición al alza se explica por una caída de conversión
en la página, no por el canal, y la recomendación apunta a la página.
MD,
        ],

        // ------------------------------------------------- Marca y conversión
        [
            'name'  => 'Definir el posicionamiento',
            'cat'   => 'marca-y-mensaje',
            'short' => 'Sitúa el producto frente a sus alternativas reales y extrae de ahí el mensaje.',
            'tags'  => 'marca, posicionamiento, estrategia',
            'body'  => <<<'MD'
## Objetivo

Que quede claro para quién es esto, contra qué compite de verdad y por qué
alguien lo elegiría. Sin eso, cualquier mensaje suena igual que el del vecino.

## Instrucciones

Trabajas en el orden que funciona: primero contra qué compite, después qué hace
distinto, después por qué eso importa y a quién.

## Workflow

1. Lista las alternativas reales, incluida no hacer nada y resolverlo con una hoja de cálculo.
2. Lista las capacidades que tiene y las alternativas no.
3. Traduce cada capacidad al valor que produce para quien la usa.
4. Define quién obtiene más valor de eso: ese es el cliente ideal, no todo el mercado.
5. Elige la categoría en la que quieres que te comparen.
6. Escribe la afirmación de posicionamiento en una frase y deriva tres pilares de mensaje.

## Reglas

- La alternativa más frecuente casi nunca es un competidor: suele ser el statu quo.
- Una capacidad que también tienen todos no sirve para posicionar, aunque sea buena.
- No escribas el cliente ideal como un rango demográfico: descríbelo por su situación.
- No inventes una categoría nueva si nadie la busca todavía.

## Inputs

- producto: qué hace
- alternativas: con qué lo resuelven hoy
- clientes: quiénes lo usan y para qué
- evidencia: casos, datos o testimonios reales

## Outputs

- alternativas: lista con su fortaleza
- diferenciadores: capacidad y valor que produce
- cliente_ideal: descrito por situación
- categoria: en la que compite
- afirmacion: posicionamiento en una frase
- pilares: tres mensajes derivados

## Ejemplos

Un producto contable descubre que su alternativa principal es una hoja de
cálculo compartida, no otro programa, y el mensaje cambia entero.
MD,
        ],
        [
            'name'  => 'Escribir una página de aterrizaje',
            'cat'   => 'marca-y-mensaje',
            'short' => 'Estructura la página con una promesa, la prueba que la sostiene y una sola acción.',
            'tags'  => 'conversión, copywriting, landing',
            'body'  => <<<'MD'
## Objetivo

Que quien llega entienda en cinco segundos qué es, para quién y qué gana, y que
sepa exactamente qué hacer.

## Instrucciones

Una página, una acción. Si hay dos acciones que compiten, una de las dos sobra o
son dos páginas.

## Workflow

1. Escribe la promesa en una frase que nombre el resultado, no la funcionalidad.
2. Añade la línea de apoyo que explica para quién es y por qué es creíble.
3. Coloca la prueba más fuerte que tengas cerca del principio.
4. Desarrolla los tres beneficios principales, cada uno con su evidencia.
5. Responde las tres objeciones más frecuentes antes de que las piensen.
6. Repite la acción principal al final, con las mismas palabras que al principio.

## Reglas

- Si el anuncio prometió algo, la página lo cumple con las mismas palabras.
- Nada de testimonios inventados, logos de clientes que no lo son ni cifras sin origen.
- Un formulario pide lo que hace falta para el siguiente paso, no lo que sería útil tener.
- No uses contadores de escasez que se reinician: es una promesa falsa y se nota.

## Inputs

- oferta: qué se ofrece y a cambio de qué
- audiencia: a quién y qué le frena
- pruebas: casos, datos, testimonios reales
- accion: qué debe hacer la persona

## Outputs

- pagina: bloques con su texto
- objeciones: las respondidas y dónde
- pendientes: pruebas que harían falta y no existen

## Ejemplos

Una página de prueba gratuita sale con promesa por resultado, un dato propio
como prueba, tres beneficios y tres objeciones respondidas antes del formulario.
MD,
        ],
        [
            'name'  => 'Revisar una afirmación de marketing',
            'cat'   => 'marca-y-mensaje',
            'short' => 'Comprueba qué sostiene cada afirmación y separa lo demostrable de lo que hay que quitar.',
            'tags'  => 'marca, cumplimiento, revisión',
            'body'  => <<<'MD'
## Objetivo

Que no salga publicada una cifra, una comparación o una promesa que no se pueda
sostener si alguien pregunta.

## Instrucciones

Extraes todas las afirmaciones verificables del texto y buscas qué las respalda.
Lo que no tiene respaldo no se suaviza: se quita o se marca.

## Workflow

1. Extrae cada afirmación verificable: cifras, comparaciones, plazos, resultados, superlativos.
2. Para cada una, identifica qué la respalda y de qué fecha es.
3. Clasifica: demostrable, demostrable con matiz, sin respaldo.
4. Señala las comparaciones con competidores nombrados o reconocibles.
5. Señala lo que toca salud, dinero, legalidad o resultados garantizados.
6. Propón una redacción alternativa para lo que sí se puede sostener con matiz.

## Reglas

- «El mejor», «el número uno» y «el líder» son afirmaciones verificables, no adornos.
- Un resultado de un cliente no se generaliza: si aparece, se indica que es un caso concreto.
- Nunca conviertas una afirmación sin respaldo en una más vaga para que pase: quítala.
- No dictamines legalidad: señala el riesgo y recomienda revisión profesional.

## Inputs

- texto: pieza a revisar
- evidencia: datos y documentos disponibles
- contexto: sector y país

## Outputs

- afirmaciones: cada una con su estado y su respaldo
- a_quitar: sin respaldo posible
- reescrituras: alternativa sostenible
- revisar: lo que necesita visto bueno legal

## Ejemplos

Una página con «reduce el tiempo de cierre un 70 %» devuelve una afirmación sin
respaldo y una reescritura basada en el único caso documentado.
MD,
        ],
        [
            'name'  => 'Revisar la divulgación de un patrocinio',
            'cat'   => 'marca-y-mensaje',
            'short' => 'Comprueba que la relación comercial se declara de forma visible y en el momento adecuado.',
            'tags'  => 'cumplimiento, influencers, divulgación',
            'body'  => <<<'MD'
## Objetivo

Que quede claro que hay una relación comercial detrás, antes de que la audiencia
se forme una opinión.

## Instrucciones

Compruebas dos cosas: si existe relación material y si la divulgación es
imposible de pasar por alto. Las guías de la autoridad de consumo de Estados
Unidos son la referencia más usada; no sustituyen la norma local.

## Workflow

1. Determina si hay relación material: pago, comisión de afiliación, producto gratis o con descuento, relación laboral o familiar.
2. Comprueba que la divulgación existe si la hay.
3. Comprueba que aparece antes de que se consuma el contenido, no al final.
4. Comprueba que se entiende sin ambigüedad y sin depender sólo de la herramienta de la plataforma.
5. Comprueba que no está enterrada entre etiquetas, en letra pequeña ni tras un «ver más».
6. En vídeo, comprueba que se dice además de escribirse.

## Reglas

- Recibir un producto gratis, aunque no se haya pedido, es relación material si se menciona.
- Una etiqueta al final de una lista de etiquetas no cumple: tiene que verse sin esfuerzo.
- Las cuentas y personajes generados por ordenador están igualmente sujetos a divulgar.
- No dictamines cumplimiento legal: señala lo que falta y recomienda revisión profesional.

## Inputs

- contenido: pieza publicada o borrador
- relacion: qué recibió el creador
- formato: canal y tipo de pieza

## Outputs

- requiere_divulgacion: sí o no, con el motivo
- hallazgos: qué falta o qué no se ve bien
- propuesta: texto y ubicación de la divulgación
- aviso: esto no sustituye asesoría legal

## Ejemplos

Un vídeo con la divulgación en la descripción y no en pantalla devuelve un
hallazgo: no se ve antes de consumir el contenido.
MD,
        ],
        [
            'name'  => 'Preparar un plan de lanzamiento',
            'cat'   => 'marketing-y-contenido',
            'short' => 'Ordena mensajes, canales y fechas alrededor de una fecha, con responsables y plan si algo falla.',
            'tags'  => 'lanzamiento, campaña, planificación',
            'body'  => <<<'MD'
## Objetivo

Llegar a la fecha con todo listo y con el equipo sabiendo qué hace cada uno el
día del lanzamiento.

## Instrucciones

Trabajas hacia atrás desde la fecha. Cada pieza tiene una fecha de entrega
anterior a la de publicación, y alguien que responde por ella.

## Workflow

1. Define el objetivo medible y la fecha.
2. Define el mensaje central y cómo cambia para cada público.
3. Elige los canales por dónde está el público, no por cuáles están disponibles.
4. Coloca las piezas hacia atrás desde la fecha, con entrega y publicación separadas.
5. Marca las dependencias: qué no puede empezar hasta que otra cosa termine.
6. Define qué se hace si una pieza no llega: qué se sacrifica y qué no.
7. Define qué se mide el día uno, la semana uno y el mes uno.

## Reglas

- Una pieza sin responsable no está planificada.
- Lo que depende de un tercero lleva un margen que no se negocia.
- Si el mensaje central no cabe en una frase, el lanzamiento no está listo.
- Define antes el criterio de fracaso, no sólo el de éxito.

## Inputs

- que_lanza: producto, función o contenido
- fecha: día objetivo
- publicos: a quiénes
- equipo: quién produce qué

## Outputs

- plan: pieza, canal, responsable, entrega y publicación
- dependencias: qué bloquea a qué
- contingencia: qué se sacrifica si algo no llega
- metricas: día uno, semana uno, mes uno

## Ejemplos

Un lanzamiento a cuatro semanas sale con catorce piezas, tres dependencias
críticas y dos que se sacrifican si la documentación no llega a tiempo.
MD,
        ],
    ];
}
