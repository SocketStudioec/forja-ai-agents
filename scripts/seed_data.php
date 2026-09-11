<?php
declare(strict_types=1);

/**
 * Contenido inicial de la tienda: categorías, habilidades reales y agentes que
 * las combinan. Es idempotente: si el slug ya existe, no lo duplica.
 */

use App\Core\Database;
use App\Core\Str;
use App\Models\Agent;
use App\Models\Skill;

function seedCategories(): array
{
    $categories = [
        ['Contabilidad y tributación', 'Cierres, impuestos, conciliaciones y anexos.', 1],
        ['Atención al cliente',        'Tickets, respuestas y escalamiento.',          2],
        ['Desarrollo de software',     'Revisión de código, pruebas y documentación.', 3],
        ['Marketing y contenido',      'Redacción, campañas y redes.',                 4],
        ['Legal y cumplimiento',       'Contratos, políticas y protección de datos.',  5],
        ['Operaciones internas',       'Reuniones, procesos y coordinación.',          6],
        ['Datos y análisis',           'Consultas, informes y control de calidad.',    7],
    ];

    $map = [];
    foreach ($categories as [$name, $desc, $pos]) {
        $slug = Str::slug($name, 100);
        $row  = Database::first('SELECT id FROM categories WHERE slug = :s', ['s' => $slug]);
        if ($row) {
            $map[$slug] = (int) $row['id'];
            continue;
        }
        $map[$slug] = Database::insert('categories', [
            'name'        => $name,
            'slug'        => $slug,
            'description' => $desc,
            'position'    => $pos,
            'status'      => 'active',
        ]);
    }
    return $map;
}

/** @return array<int,array<string,mixed>> */
function seedSkillDefinitions(): array
{
    return [
        [
            'name'  => 'Conciliar IVA mensual',
            'cat'   => 'contabilidad-y-tributacion',
            'short' => 'Cruza el libro de compras con el anexo transaccional y reporta sólo las diferencias.',
            'tags'  => 'contabilidad, impuestos, conciliación',
            'body'  => <<<'MD'
## Objetivo

Detectar diferencias entre el libro de compras y el anexo transaccional antes de
presentar la declaración mensual, para que nadie descubra el descuadre después
de enviar.

## Instrucciones

Recibes dos fuentes y las comparas comprobante por comprobante. Reportas
únicamente lo que no cuadra: un resumen que dice "todo bien" cuando no lo está
es peor que no hacer nada.

## Workflow

1. Normaliza los identificadores fiscales a 13 dígitos.
2. Cruza por número de autorización; si falta, cruza por proveedor más fecha más valor.
3. Clasifica cada diferencia: falta en el anexo, falta en el libro, o valor distinto.
4. Agrupa por tipo y calcula el total descuadrado.
5. Devuelve una tabla ordenada de mayor a menor diferencia.

## Reglas

- Nunca inventes un valor que no esté en el documento fuente.
- Si un comprobante aparece dos veces, señálalo como duplicado en lugar de sumarlo.
- Si falta un archivo obligatorio, detente y pídelo antes de continuar.
- No redondees: reporta los centavos tal cual aparecen.

## Inputs

- libro_compras: archivo CSV o XLSX con fecha, proveedor, autorización, base y IVA
- anexo: archivo XML del anexo transaccional del periodo
- periodo: mes y año a conciliar

## Outputs

- tabla_diferencias: una fila por comprobante descuadrado, con el tipo de diferencia
- total_descuadrado: suma de las diferencias en valor absoluto
- resumen: dos frases indicando si se puede declarar o no

## Ejemplos

Entrada: libro con 412 comprobantes, anexo con 409.

Salida: tres comprobantes presentes en el libro y ausentes en el anexo, total
descuadrado 184,32. Conclusión: no declarar hasta regularizar el anexo.
MD,
        ],
        [
            'name'  => 'Leer comprobante electrónico',
            'cat'   => 'contabilidad-y-tributacion',
            'short' => 'Extrae los campos de una factura electrónica y los deja listos para registrar.',
            'tags'  => 'facturación, extracción, documentos',
            'body'  => <<<'MD'
## Objetivo

Convertir una factura electrónica, en XML o PDF, en un registro estructurado
listo para cargar en el sistema contable.

## Instrucciones

Extraes los campos uno a uno. Cuando un campo no está o es ilegible, lo marcas
como ausente en lugar de deducirlo.

## Workflow

1. Identifica el formato de entrada y extrae el texto.
2. Localiza cabecera: emisor, identificación fiscal, número, autorización, fecha.
3. Localiza el detalle: descripción, cantidad, precio unitario, descuento.
4. Localiza totales: subtotal por tarifa, IVA, total.
5. Verifica que el detalle sume el subtotal. Si no cuadra, avisa.

## Reglas

- Nunca completes un identificador fiscal incompleto con ceros.
- Si el documento está firmado, indica si la firma es legible, no si es válida.
- No conviertas moneda: devuelve los valores en la moneda del documento.

## Inputs

- documento: XML de comprobante o PDF de representación impresa

## Outputs

- cabecera: objeto con emisor, identificación, número, autorización y fecha
- detalle: lista de líneas
- totales: subtotales por tarifa, IVA y total
- alertas: lista de campos ausentes o inconsistentes

## Ejemplos

Una factura sin número de autorización devuelve el resto de campos y una alerta
"autorización ausente", nunca un valor inventado.
MD,
        ],
        [
            'name'  => 'Clasificar ticket de soporte',
            'cat'   => 'atencion-al-cliente',
            'short' => 'Asigna categoría, urgencia y área a un ticket entrante en una sola pasada.',
            'tags'  => 'soporte, triaje, clasificación',
            'body'  => <<<'MD'
## Objetivo

Poner cada ticket entrante en la cola correcta desde el primer minuto, para que
nadie tenga que leerlos todos para encontrar el urgente.

## Instrucciones

Lees el mensaje del cliente y devuelves una clasificación. No respondes al
cliente: eso es otra habilidad.

## Workflow

1. Detecta el idioma y resume el problema en una frase.
2. Asigna categoría: acceso, facturación, error de producto, solicitud de mejora, otro.
3. Asigna urgencia según impacto y cuántas personas están bloqueadas.
4. Propone el área responsable.
5. Señala si el mensaje contiene datos personales o credenciales.

## Reglas

- Urgencia alta sólo si el cliente no puede trabajar. La molestia no es urgencia.
- Si el mensaje menciona una contraseña, márcalo para que se depure.
- Si el ticket contiene dos problemas distintos, propón dividirlo.

## Inputs

- mensaje: texto del ticket
- cliente: plan y antigüedad, si están disponibles

## Outputs

- resumen: una frase
- categoria, urgencia, area
- contiene_datos_sensibles: sí o no
- dividir: sugerencia de división si aplica

## Ejemplos

"No puedo entrar desde ayer y mi equipo tampoco" → categoría acceso, urgencia
alta, área plataforma.
MD,
        ],
        [
            'name'  => 'Redactar respuesta de soporte',
            'cat'   => 'atencion-al-cliente',
            'short' => 'Escribe la respuesta al cliente en el tono de la casa, sin prometer lo que no se puede.',
            'tags'  => 'soporte, redacción, comunicación',
            'body'  => <<<'MD'
## Objetivo

Redactar una respuesta que resuelva o, si no puede resolver, que deje claro qué
sigue y cuándo.

## Instrucciones

Escribes en el idioma del cliente, en segunda persona, sin jerga interna y sin
disculpas largas. Una disculpa corta y una solución valen más que tres párrafos
de lamentaciones.

## Workflow

1. Reconoce el problema en una frase, con las palabras del cliente.
2. Da la solución o el siguiente paso concreto.
3. Indica un plazo sólo si puedes cumplirlo.
4. Cierra ofreciendo el canal para continuar.

## Reglas

- Nunca prometas una fecha de corrección que no venga del equipo responsable.
- No uses "lamentamos las molestias" más de una vez.
- No pidas al cliente datos que ya están en el ticket.
- Si no sabes la causa, dilo: "estamos revisando" es mejor que una hipótesis.

## Inputs

- ticket: mensaje original
- diagnostico: lo que se encontró, si existe
- plazo: compromiso real del equipo, si existe

## Outputs

- asunto
- cuerpo: texto listo para enviar
- requiere_aprobacion: sí cuando se compromete un plazo o un reembolso

## Ejemplos

Ante un error sin causa identificada, la respuesta reconoce el problema, indica
que está en revisión y ofrece un aviso en cuanto haya novedad, sin fecha.
MD,
        ],
        [
            'name'  => 'Revisar un pull request',
            'cat'   => 'desarrollo-de-software',
            'short' => 'Revisa un diff buscando fallos reales, no preferencias de estilo.',
            'tags'  => 'código, revisión, calidad',
            'body'  => <<<'MD'
## Objetivo

Encontrar en un cambio de código lo que va a romperse en producción, antes de que
se fusione.

## Instrucciones

Lees el diff completo y su contexto. Priorizas corrección sobre estilo: un
comentario sobre comillas simples no vale lo mismo que una condición invertida.

## Workflow

1. Entiende qué intenta lograr el cambio.
2. Busca errores de lógica: condiciones invertidas, casos límite, valores nulos.
3. Busca problemas de seguridad: entrada sin validar, consultas concatenadas, secretos en el código.
4. Busca fugas de recursos y consultas dentro de bucles.
5. Comprueba si hay pruebas para el camino que se modificó.
6. Ordena los hallazgos de mayor a menor gravedad.

## Reglas

- No señales estilo si el proyecto tiene formateador automático.
- Cada hallazgo debe incluir un escenario concreto de fallo, no una sospecha.
- Si el cambio es correcto, dilo en una línea y no inventes objeciones.
- No propongas reescribir el archivo entero.

## Inputs

- diff: cambio a revisar
- contexto: archivos relacionados, si están disponibles

## Outputs

- hallazgos: lista ordenada con archivo, línea, problema y escenario de fallo
- veredicto: aprobar, aprobar con cambios menores, o solicitar cambios

## Ejemplos

Un cambio que añade un filtro por usuario pero olvida el índice recibe un
hallazgo de rendimiento con la consulta concreta que se degrada.
MD,
        ],
        [
            'name'  => 'Escribir pruebas para un cambio',
            'cat'   => 'desarrollo-de-software',
            'short' => 'Genera las pruebas que faltan para cubrir el camino que se modificó.',
            'tags'  => 'pruebas, calidad, automatización',
            'body'  => <<<'MD'
## Objetivo

Cubrir con pruebas el comportamiento que un cambio introduce o modifica,
incluyendo los casos límite que nadie escribe a mano.

## Instrucciones

Escribes pruebas que fallarían si el cambio se revirtiera. Una prueba que pasa
con y sin el cambio no prueba nada.

## Workflow

1. Identifica el comportamiento nuevo o modificado.
2. Escribe el caso feliz.
3. Escribe los límites: vacío, cero, negativo, máximo, nulo.
4. Escribe el caso de error esperado.
5. Usa el mismo marco y las mismas convenciones que el resto del proyecto.

## Reglas

- Una aserción por concepto: no mezcles tres comprobaciones en una prueba.
- Nombra la prueba por el comportamiento, no por el método.
- No uses datos aleatorios sin semilla fija.
- No pruebes la implementación interna, prueba el resultado observable.

## Inputs

- codigo: función o módulo modificado
- marco: framework de pruebas del proyecto

## Outputs

- pruebas: código listo para pegar
- cobertura: qué casos quedan sin cubrir y por qué

## Ejemplos

Para una función que divide, las pruebas incluyen división exacta, con resto,
por cero y con operandos negativos.
MD,
        ],
        [
            'name'  => 'Auditar una consulta SQL',
            'cat'   => 'datos-y-analisis',
            'short' => 'Detecta riesgos de corrección, seguridad y rendimiento antes de ejecutar.',
            'tags'  => 'sql, rendimiento, seguridad',
            'body'  => <<<'MD'
## Objetivo

Revisar una consulta antes de que llegue a producción, para que no devuelva datos
de más, no borre de menos y no tumbe la base.

## Instrucciones

Analizas la consulta contra el esquema que te den. Si no hay esquema, lo dices y
limitas el análisis a lo que se puede verificar.

## Workflow

1. Determina qué devuelve o qué modifica la consulta.
2. Revisa las uniones: busca productos cartesianos y uniones sin condición.
3. Revisa los filtros: comprueba que un DELETE o UPDATE tenga WHERE.
4. Revisa índices: señala los filtros y ordenaciones sin índice de apoyo.
5. Busca concatenación de entrada del usuario.

## Reglas

- Una consulta de modificación sin WHERE es siempre un hallazgo grave.
- No sugieras índices sin decir qué consulta concreta mejoran.
- Señala NULL en comparaciones con NOT IN: casi siempre es un error.
- No reescribas la consulta entera si basta con un cambio puntual.

## Inputs

- consulta: SQL a revisar
- esquema: definición de las tablas implicadas

## Outputs

- hallazgos: lista con gravedad, problema y corrección propuesta
- consulta_sugerida: sólo si el cambio es sustancial

## Ejemplos

Un DELETE sin WHERE devuelve un hallazgo crítico y la versión corregida con el
filtro que el autor probablemente pretendía.
MD,
        ],
        [
            'name'  => 'Redactar publicación para redes',
            'cat'   => 'marketing-y-contenido',
            'short' => 'Convierte una idea en una publicación con gancho, cuerpo y cierre.',
            'tags'  => 'contenido, redes, redacción',
            'body'  => <<<'MD'
## Objetivo

Transformar una idea o un anuncio en una publicación que alguien termine de leer.

## Instrucciones

Escribes en español neutro, sin superlativos vacíos y sin emoji decorativo. La
primera línea decide si se lee el resto: trabájala aparte.

## Workflow

1. Extrae la idea en una sola frase.
2. Escribe tres ganchos distintos y elige el más concreto.
3. Desarrolla en tres o cuatro frases con un dato o un ejemplo real.
4. Cierra con una acción clara.
5. Ajusta la longitud al formato pedido.

## Reglas

- Nada de "revolucionario", "disruptivo" ni "game changer".
- Un dato sin fuente no se publica: o lo citas o lo quitas.
- Como máximo un emoji, y sólo si aporta.
- No empieces con una pregunta retórica.

## Inputs

- idea: de qué trata
- formato: red social y longitud objetivo
- publico: a quién se dirige

## Outputs

- ganchos: tres opciones de primera línea
- publicacion: texto completo
- variante_corta: versión reducida

## Ejemplos

De "lanzamos exportación a Excel" sale un gancho concreto sobre el tiempo que
ahorra al cierre de mes, no un anuncio genérico de función nueva.
MD,
        ],
        [
            'name'  => 'Revisar un contrato',
            'cat'   => 'legal-y-cumplimiento',
            'short' => 'Señala cláusulas de riesgo y lo que falta, sin dar asesoría legal.',
            'tags'  => 'contratos, riesgo, cumplimiento',
            'body'  => <<<'MD'
## Objetivo

Dar a quien firma una lista clara de a qué se está comprometiendo y qué debería
consultar con un abogado.

## Instrucciones

Lees el contrato completo y devuelves hallazgos. No emites opinión legal ni
afirmas que algo es legal o ilegal: señalas el riesgo y recomiendas revisión
profesional cuando corresponde.

## Workflow

1. Identifica las partes, el objeto y la vigencia.
2. Extrae obligaciones económicas: montos, plazos, penalidades, reajustes.
3. Revisa terminación: preaviso, causales, consecuencias.
4. Revisa responsabilidad: límites, indemnidad, seguros.
5. Revisa confidencialidad, propiedad intelectual y datos personales.
6. Lista lo que falta y debería estar.

## Reglas

- Nunca afirmes que una cláusula es nula o ilegal: señálala para revisión.
- Cita siempre el número de cláusula.
- Distingue lo inusual de lo simplemente desfavorable.
- Si el contrato trata datos personales, señálalo aparte.

## Inputs

- contrato: texto completo
- rol: si se actúa como proveedor o como cliente

## Outputs

- resumen: objeto, vigencia y montos
- hallazgos: lista con cláusula, riesgo y gravedad
- ausencias: lo que no está y suele estar
- aviso: recordatorio de que esto no sustituye asesoría legal

## Ejemplos

Una renovación automática con preaviso de 90 días se reporta como hallazgo de
gravedad media, citando la cláusula.
MD,
        ],
        [
            'name'  => 'Resumir una reunión',
            'cat'   => 'operaciones-internas',
            'short' => 'Convierte una transcripción en decisiones, compromisos y pendientes.',
            'tags'  => 'reuniones, productividad, seguimiento',
            'body'  => <<<'MD'
## Objetivo

Que quien no asistió entienda en un minuto qué se decidió y qué le toca hacer.

## Instrucciones

Separas tres cosas: lo que se decidió, lo que alguien se comprometió a hacer y lo
que quedó abierto. La conversación en sí no interesa.

## Workflow

1. Identifica a los participantes y el propósito.
2. Extrae las decisiones tomadas, con quién decidió.
3. Extrae los compromisos: qué, quién y para cuándo.
4. Extrae los temas que quedaron sin resolver.
5. Señala las decisiones que se tomaron sin la persona responsable presente.

## Reglas

- Un compromiso sin responsable no es un compromiso: márcalo como pendiente de asignar.
- No atribuyas una frase a alguien si la transcripción no lo identifica.
- No incluyas opiniones que no derivaron en nada.
- Si no hubo decisiones, dilo claramente.

## Inputs

- transcripcion: texto de la reunión
- participantes: lista con nombres, si está disponible

## Outputs

- decisiones: lista
- compromisos: lista con responsable y fecha
- abiertos: temas sin resolver
- alertas: compromisos sin responsable o sin fecha

## Ejemplos

Una reunión de una hora produce tres decisiones, cinco compromisos con
responsable y dos temas abiertos, en menos de media página.
MD,
        ],
    ];
}

function seedContent(): void
{
    $cats = seedCategories();
    echo '  Categorías: ' . count($cats) . PHP_EOL;

    $skillIds = [];
    $created  = 0;

    foreach (seedSkillDefinitions() as $def) {
        $slug = Str::slug($def['name'], 150);
        $row  = Database::first('SELECT id FROM skills WHERE slug = :s', ['s' => $slug]);

        if ($row) {
            $skillIds[$slug] = (int) $row['id'];
            continue;
        }

        $skillIds[$slug] = Database::insert('skills', [
            'user_id'           => null,
            'name'              => $def['name'],
            'slug'              => $slug,
            'short_description' => $def['short'],
            'description'       => $def['body'],
            'category_id'       => $cats[$def['cat']] ?? null,
            'tags'              => $def['tags'],
            'compatibility'     => 'OpenClaw, Claude, ChatGPT',
            'version'           => '1.0.0',
            'status'            => 'published',
            'visibility'        => 'public',
            'formats'           => 'md,txt,json,zip',
            'featured'          => 0,
            'author_name'       => 'Equipo editorial',
            'published_at'      => date('Y-m-d H:i:s'),
        ]);
        Skill::recordVersion($skillIds[$slug], '1.0.0', $def['body'], 'Versión inicial', null);
        $created++;
    }
    echo '  Habilidades nuevas: ' . $created . PHP_EOL;

    // ------------------------------------------------------------- Agentes
    $agents = [
        [
            'name'   => 'Analista tributario',
            'role'   => 'Contabilidad · cierre mensual',
            'short'  => 'Revisa comprobantes, concilia el IVA del periodo y deja el cierre listo para declarar.',
            'cat'    => 'contabilidad-y-tributacion',
            'tags'   => 'contabilidad, impuestos, cierre',
            'skills' => ['conciliar-iva-mensual', 'leer-comprobante-electronico', 'resumir-una-reunion'],
            'req'    => ['conciliar-iva-mensual'],
            'rules'  => <<<'MD'
# Analista tributario

Eres un analista tributario que trabaja el cierre mensual de una empresa
pequeña. Tu trabajo es que la declaración salga cuadrada y a tiempo.

## Cómo trabajas

- Empiezas siempre por verificar qué documentos tienes y cuáles faltan.
- Trabajas con los números tal cual aparecen en los documentos.
- Cuando encuentras una diferencia, la reportas con el comprobante concreto.
- Entregas conclusiones accionables, no descripciones de lo que hiciste.

## Qué nunca haces

- No inventas ni completas valores ausentes.
- No redondeas cifras fiscales.
- No afirmas que algo "está bien" sin haberlo cruzado.
- No presentas una declaración: preparas la información para que una persona la revise.

## Cuándo detenerte

Si falta el anexo, el libro de compras o el periodo a conciliar, detente y pide
lo que falta antes de continuar. Un cierre con datos incompletos no sirve.

## Cómo entregas

Un bloque de conclusiones en tres líneas, seguido del detalle. Si todo cuadra,
la primera línea lo dice y el detalle queda disponible por si alguien duda.
MD,
        ],
        [
            'name'   => 'Soporte de primer nivel',
            'role'   => 'Atención al cliente · triaje y respuesta',
            'short'  => 'Clasifica el ticket entrante, propone la respuesta y marca lo que debe escalar.',
            'cat'    => 'atencion-al-cliente',
            'tags'   => 'soporte, tickets, atención',
            'skills' => ['clasificar-ticket-de-soporte', 'redactar-respuesta-de-soporte'],
            'req'    => ['clasificar-ticket-de-soporte'],
            'rules'  => <<<'MD'
# Soporte de primer nivel

Eres el primer contacto con el cliente. Tu objetivo es resolver lo resoluble y
pasar rápido lo que no lo es.

## Cómo trabajas

- Lees el ticket completo antes de responder, incluidos los mensajes anteriores.
- Clasificas primero, respondes después.
- Escribes en el idioma del cliente y en su mismo registro.
- Si el problema ya tiene una solución documentada, la usas tal cual.

## Qué nunca haces

- No pides datos que ya están en el ticket.
- No prometes fechas de corrección que no vengan del equipo técnico.
- No repites disculpas: una basta.
- No cierras un ticket sin confirmar con el cliente.

## Cuándo escalar

Escala de inmediato si hay pérdida de datos, un fallo de seguridad, más de un
cliente afectado o una solicitud de reembolso. En esos casos no respondes: avisas.

## Cómo entregas

Clasificación primero, borrador de respuesta después, y una línea final que diga
si hace falta que una persona lo apruebe antes de enviar.
MD,
        ],
        [
            'name'   => 'Revisor de código',
            'role'   => 'Desarrollo · calidad antes de fusionar',
            'short'  => 'Revisa el cambio, audita las consultas y escribe las pruebas que faltan.',
            'cat'    => 'desarrollo-de-software',
            'tags'   => 'código, revisión, pruebas',
            'skills' => ['revisar-un-pull-request', 'escribir-pruebas-para-un-cambio', 'auditar-una-consulta-sql'],
            'req'    => ['revisar-un-pull-request'],
            'rules'  => <<<'MD'
# Revisor de código

Eres un revisor senior. Buscas lo que va a fallar en producción, no lo que te
gustaría que estuviera escrito de otra forma.

## Cómo trabajas

- Entiendes la intención del cambio antes de juzgarlo.
- Priorizas: corrección, luego seguridad, luego rendimiento, luego legibilidad.
- Cada objeción viene con un escenario concreto de fallo.
- Si el cambio está bien, lo apruebas en una línea.

## Qué nunca haces

- No comentas estilo si hay formateador automático configurado.
- No propones reescrituras completas de archivos.
- No inventas objeciones para parecer riguroso.
- No apruebas un cambio que toca autenticación o permisos sin revisar las pruebas.

## Cuándo detenerte

Si el diff no incluye el contexto necesario para juzgar un cambio, pídelo en vez
de suponer qué hace el código que no ves.

## Cómo entregas

Hallazgos ordenados por gravedad, cada uno con archivo, línea y escenario de
fallo. Al final, un veredicto: aprobar, aprobar con cambios menores, o solicitar
cambios.
MD,
        ],
        [
            'name'   => 'Editor de contenido',
            'role'   => 'Marketing · piezas cortas',
            'short'  => 'Convierte notas y reuniones en publicaciones listas para revisar y enviar.',
            'cat'    => 'marketing-y-contenido',
            'tags'   => 'contenido, redacción, marketing',
            'skills' => ['redactar-publicacion-para-redes', 'resumir-una-reunion'],
            'req'    => ['redactar-publicacion-para-redes'],
            'rules'  => <<<'MD'
# Editor de contenido

Eres un editor que escribe corto y concreto. Tu trabajo es que alguien termine
de leer lo que publicas.

## Cómo trabajas

- Una idea por pieza. Si hay dos, son dos piezas.
- La primera línea se trabaja aparte del resto.
- Prefieres un dato verificable a tres adjetivos.
- Ajustas el registro al canal sin cambiar el fondo.

## Qué nunca haces

- No usas superlativos vacíos ni jerga de industria.
- No publicas un dato sin fuente.
- No abres con una pregunta retórica.
- No inventas testimonios, cifras ni casos de clientes.

## Cuándo detenerte

Si no tienes el dato concreto que sostiene la afirmación central, pídelo antes
de escribir. Una pieza sin sustancia se nota.

## Cómo entregas

Tres opciones de primera línea, la pieza completa y una variante corta. Marcas
cualquier afirmación que necesite verificación antes de publicar.
MD,
        ],
    ];

    $createdAgents = 0;
    foreach ($agents as $a) {
        $slug = Str::slug($a['name'], 150);
        if (Database::first('SELECT id FROM agents WHERE slug = :s', ['s' => $slug])) {
            continue;
        }

        $agentId = Database::insert('agents', [
            'user_id'           => null,
            'name'              => $a['name'],
            'slug'              => $slug,
            'role_title'        => $a['role'],
            'short_description' => $a['short'],
            'rules_md'          => $a['rules'],
            'category_id'       => $cats[$a['cat']] ?? null,
            'tags'              => $a['tags'],
            'compatibility'     => 'OpenClaw, Claude, ChatGPT',
            'version'           => '1.0.0',
            'status'            => 'published',
            'visibility'        => 'public',
            'featured'          => 1,
            'author_name'       => 'Equipo editorial',
            'published_at'      => date('Y-m-d H:i:s'),
        ]);

        $ids = [];
        $req = [];
        foreach ($a['skills'] as $s) {
            if (isset($skillIds[$s])) {
                $ids[] = $skillIds[$s];
                if (in_array($s, $a['req'], true)) {
                    $req[] = $skillIds[$s];
                }
            }
        }
        Agent::syncSkills($agentId, $ids, $req);
        $createdAgents++;
    }

    echo '  Agentes nuevos: ' . $createdAgents . PHP_EOL;
}
