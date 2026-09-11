<?php
declare(strict_types=1);

/**
 * Habilidades de contabilidad y de vida de oficina.
 * Cada una respeta las secciones que el generador convierte en campos del JSON.
 *
 * @return array<int,array<string,mixed>>
 */
function oficinaSkillDefinitions(): array
{
    return [

        // ---------------------------------------------------- Contabilidad
        [
            'name'  => 'Conciliar la cuenta bancaria',
            'cat'   => 'finanzas-y-tesoreria',
            'short' => 'Cruza el estado de cuenta con el libro banco y deja sólo las partidas que no cuadran.',
            'tags'  => 'bancos, conciliación, cierre',
            'body'  => <<<'MD'
## Objetivo

Cerrar el mes con el saldo del banco y el saldo contable cuadrados, y con una
lista corta de partidas conciliatorias que alguien pueda resolver en una tarde.

## Instrucciones

Cruzas movimiento por movimiento. No ajustas nada: reportas las diferencias y
las clasificas para que la persona decida qué hacer con cada una.

## Workflow

1. Confirma que el saldo inicial del estado de cuenta coincide con el cierre del mes anterior.
2. Empareja por fecha, valor y número de documento.
3. Para lo que no empareje, intenta por valor y fecha aproximada (±3 días).
4. Clasifica cada partida suelta: cheque girado no cobrado, depósito en tránsito,
   nota de débito no registrada, nota de crédito no registrada, error de digitación.
5. Calcula el saldo conciliado y compáralo con el saldo contable.

## Reglas

- Nunca fuerces un emparejamiento por aproximación de valor sin coincidencia de fecha.
- Un movimiento del banco que no está en libros es un registro pendiente, no un error del banco.
- Si el saldo inicial no coincide, detente: la conciliación anterior quedó mal cerrada.
- No propongas asientos: eso lo decide el contador.

## Inputs

- estado_cuenta: extracto bancario del periodo
- libro_banco: mayor auxiliar de la cuenta
- saldo_anterior: saldo conciliado del mes previo

## Outputs

- partidas_conciliatorias: tabla con tipo, fecha, valor y origen
- saldo_conciliado: valor calculado
- diferencia: contra el saldo contable, cero si todo cuadra

## Ejemplos

Un extracto con 218 movimientos y un libro con 214 devuelve cuatro partidas:
dos cheques girados no cobrados y dos notas de débito por comisiones que faltan
registrar. Diferencia final cero.
MD,
        ],
        [
            'name'  => 'Cerrar la caja chica',
            'cat'   => 'finanzas-y-tesoreria',
            'short' => 'Cuadra los comprobantes contra el fondo y arma la reposición con el detalle listo.',
            'tags'  => 'caja, gastos, reposición',
            'body'  => <<<'MD'
## Objetivo

Cerrar el fondo fijo sin que falte ni sobre un centavo, y dejar la solicitud de
reposición lista para firma.

## Instrucciones

Sumas los comprobantes, los clasificas por cuenta y comparas contra el fondo
asignado. Lo que no tenga respaldo válido se reporta aparte, no se diluye en el
total.

## Workflow

1. Verifica que cada comprobante tenga fecha dentro del periodo y esté a nombre de la empresa.
2. Descarta los que superen el tope por transacción del fondo.
3. Agrupa por cuenta contable.
4. Suma comprobantes más efectivo en caja y compara con el fondo asignado.
5. Arma la reposición por el valor gastado.

## Reglas

- Un comprobante sin respaldo fiscal válido no entra al total: se lista aparte.
- Nunca redondees el efectivo contado.
- Si el faltante supera el uno por ciento del fondo, señálalo para revisión antes de reponer.
- No aceptes gastos personales aunque traigan comprobante.

## Inputs

- comprobantes: lista con fecha, proveedor, valor y concepto
- efectivo_contado: valor en caja al cierre
- fondo_asignado: monto del fondo fijo

## Outputs

- resumen_por_cuenta: tabla con cuenta contable y total
- faltante_sobrante: diferencia contra el fondo
- rechazados: comprobantes que no califican y por qué
- valor_reposicion: monto a reponer

## Ejemplos

Un fondo de 500 con 418,60 en comprobantes válidos y 79,40 en efectivo arroja un
faltante de 2,00 y una reposición de 418,60.
MD,
        ],
        [
            'name'  => 'Armar el rol de pagos',
            'cat'   => 'recursos-humanos',
            'short' => 'Calcula sueldos, descuentos y aportes del mes, y señala lo que se sale de lo normal.',
            'tags'  => 'nómina, sueldos, recursos humanos',
            'body'  => <<<'MD'
## Objetivo

Preparar el rol del mes con todos los ingresos y descuentos calculados, y una
alerta por cada cifra que se aparte de lo habitual.

## Instrucciones

Calculas por empleado a partir del contrato y de las novedades del mes. Nunca
inventas una novedad: si no está en el insumo, no existe.

## Workflow

1. Parte del sueldo contractual y aplica los días efectivamente trabajados.
2. Suma ingresos variables: horas extra, comisiones, bonos.
3. Aplica los aportes personales sobre la base correspondiente.
4. Aplica descuentos: anticipos, préstamos, multas autorizadas.
5. Calcula el líquido a recibir y el aporte patronal.
6. Compara cada línea con el mes anterior y marca variaciones mayores al veinte por ciento.

## Reglas

- Un descuento sin autorización escrita no se aplica: se reporta como pendiente.
- Las horas extra se pagan con el recargo que corresponda al tipo de hora, nunca al valor simple.
- Si un empleado sale con líquido negativo, detente y repórtalo antes de continuar.
- No proyectes beneficios que no correspondan al periodo.

## Inputs

- empleados: contrato, sueldo, fecha de ingreso
- novedades: días, horas extra, anticipos, préstamos, ausencias
- mes_anterior: rol previo para comparar

## Outputs

- rol: una fila por empleado con ingresos, descuentos y líquido
- totales: masa salarial, aportes personales y patronales
- alertas: variaciones y casos que requieren decisión humana

## Ejemplos

Un empleado con quince días trabajados y un anticipo de 200 sale con alerta de
variación del cuarenta y ocho por ciento contra el mes anterior, explicada por el
ingreso a mitad de mes.
MD,
        ],
        [
            'name'  => 'Clasificar gastos deducibles',
            'cat'   => 'contabilidad-y-tributacion',
            'short' => 'Separa lo deducible de lo que no lo es y explica el motivo de cada exclusión.',
            'tags'  => 'impuestos, gastos, deducibilidad',
            'body'  => <<<'MD'
## Objetivo

Saber, antes del cierre, cuánto del gasto registrado va a sobrevivir a una
revisión y cuánto hay que reclasificar.

## Instrucciones

Revisas gasto por gasto contra tres criterios: que tenga respaldo válido, que se
relacione con la actividad y que no supere un límite legal. Explicas cada
exclusión con el criterio que falló.

## Workflow

1. Verifica el respaldo: comprobante válido, a nombre de la empresa, dentro del periodo.
2. Verifica la relación con la actividad generadora de ingreso.
3. Verifica límites y topes aplicables al tipo de gasto.
4. Marca cada gasto como deducible, no deducible o dudoso.
5. Totaliza por categoría y calcula el efecto en la base imponible.

## Reglas

- Un gasto dudoso se marca dudoso, nunca deducible por defecto.
- Nunca afirmes que algo es deducible por ley: señala el criterio y recomienda confirmación.
- Un gasto pagado en efectivo por encima del umbral bancarizado se marca automáticamente.
- No reclasifiques nada por tu cuenta: propón.

## Inputs

- gastos: fecha, proveedor, valor, cuenta, medio de pago
- actividad: giro del negocio

## Outputs

- clasificacion: una fila por gasto con estado y motivo
- total_no_deducible: suma
- efecto_base: impacto estimado en la base imponible

## Ejemplos

Una cena de 380 pagada en efectivo y sin detalle del motivo se marca dudosa por
dos criterios: bancarización y relación con la actividad.
MD,
        ],
        [
            'name'  => 'Depurar cuentas por cobrar',
            'cat'   => 'finanzas-y-tesoreria',
            'short' => 'Ordena la cartera por antigüedad y dice a quién llamar primero y por qué.',
            'tags'  => 'cartera, cobranza, clientes',
            'body'  => <<<'MD'
## Objetivo

Convertir un listado de saldos en un plan de cobranza: a quién llamar hoy, a
quién esperar y qué ya hay que provisionar.

## Instrucciones

Clasificas por antigüedad y por comportamiento de pago, no sólo por monto. Un
cliente que siempre paga a sesenta días no es el mismo problema que uno que dejó
de responder.

## Workflow

1. Calcula la antigüedad de cada saldo desde la fecha de vencimiento.
2. Agrupa en tramos: corriente, 1 a 30, 31 a 60, 61 a 90, más de 90.
3. Cruza con el historial: días promedio de pago de ese cliente.
4. Marca los saldos que superan el doble del promedio histórico del cliente.
5. Propone prioridad de gestión y señala los candidatos a provisión.

## Reglas

- Antigüedad se cuenta desde el vencimiento, nunca desde la emisión.
- Un saldo a favor del cliente no se compensa solo: se reporta para revisión.
- No propongas castigar cartera: propón provisionar y deriva la decisión.
- Si faltan fechas de vencimiento, pídelas antes de clasificar.

## Inputs

- cartera: cliente, documento, fecha de emisión, vencimiento, saldo
- historial: días promedio de pago por cliente

## Outputs

- antiguedad: tabla por tramos con totales
- prioridad: lista ordenada de gestión con motivo
- candidatos_provision: saldos con más de 90 días y sin gestión reciente

## Ejemplos

Un cliente que paga históricamente a 45 días con un saldo de 62 días entra en
prioridad media. Otro que paga a 30 con un saldo de 95 entra en prioridad alta.
MD,
        ],
        [
            'name'  => 'Preparar el anexo de retenciones',
            'cat'   => 'contabilidad-y-tributacion',
            'short' => 'Valida los porcentajes retenidos y arma el detalle antes de presentar.',
            'tags'  => 'retenciones, anexos, impuestos',
            'body'  => <<<'MD'
## Objetivo

Presentar el anexo sin que ninguna retención esté aplicada con el porcentaje
equivocado ni falte un comprobante.

## Instrucciones

Revisas cada retención contra el tipo de transacción y la tabla vigente del
periodo. Lo que no encaja se reporta con el valor esperado y la diferencia.

## Workflow

1. Identifica el tipo de bien o servicio de cada comprobante.
2. Busca el porcentaje que corresponde en la tabla del periodo.
3. Compara contra el porcentaje efectivamente aplicado.
4. Verifica que cada retención tenga comprobante emitido y numerado.
5. Cuadra el total retenido contra la cuenta contable.

## Reglas

- Nunca asumas el tipo de transacción: si el detalle es ambiguo, pregunta.
- Si la tabla vigente no cubre un caso, márcalo como no determinado, no como cero.
- Una retención sin comprobante emitido es un hallazgo grave.
- No corrijas comprobantes ya emitidos: reporta y deja la decisión.

## Inputs

- comprobantes: detalle de compras con retenciones aplicadas
- tabla_vigente: porcentajes del periodo
- saldo_contable: total registrado en la cuenta de retenciones

## Outputs

- diferencias: tabla con aplicado, esperado y valor de la diferencia
- sin_comprobante: retenciones sin documento emitido
- cuadre: diferencia contra el saldo contable

## Ejemplos

Una retención del uno por ciento sobre un servicio profesional devuelve una
diferencia con el porcentaje esperado del diez por ciento y el valor dejado de retener.
MD,
        ],
        [
            'name'  => 'Detectar facturas duplicadas',
            'cat'   => 'contabilidad-y-tributacion',
            'short' => 'Encuentra el mismo gasto registrado dos veces antes de que se pague dos veces.',
            'tags'  => 'proveedores, control, pagos',
            'body'  => <<<'MD'
## Objetivo

Evitar el pago doble. Es el error más caro y el más fácil de cometer cuando la
misma factura llega por correo y en físico.

## Instrucciones

Buscas coincidencias exactas y aproximadas. Una coincidencia aproximada no es un
duplicado confirmado: es un caso para que alguien mire.

## Workflow

1. Busca coincidencias exactas de proveedor, número y valor.
2. Busca mismo proveedor y mismo valor con números distintos en un rango de treinta días.
3. Busca mismo número con valores distintos: suele ser un registro mal digitado.
4. Busca notas de crédito que anulen una factura ya registrada dos veces.
5. Clasifica cada hallazgo como confirmado o por revisar.

## Reglas

- Sólo es duplicado confirmado si coinciden proveedor, número y valor.
- Los servicios recurrentes con el mismo valor mensual no son duplicados: compruébalo por fecha.
- Nunca propongas eliminar un registro: propón revisar y anular por la vía contable.
- Si un duplicado ya fue pagado, señálalo como prioridad alta.

## Inputs

- registros: proveedor, identificación, número, fecha, valor, estado de pago

## Outputs

- confirmados: duplicados exactos con el valor en riesgo
- por_revisar: coincidencias aproximadas con el motivo
- valor_en_riesgo: suma de lo que se pagaría dos veces

## Ejemplos

Dos registros del mismo proveedor por 1.240,00 con números 001-001-45 y
001-001-450 se reportan como por revisar: el segundo parece un error de digitación.
MD,
        ],
        [
            'name'  => 'Cotejar inventario con kardex',
            'cat'   => 'datos-y-analisis',
            'short' => 'Compara el conteo físico contra el sistema y explica cada diferencia.',
            'tags'  => 'inventario, kardex, control',
            'body'  => <<<'MD'
## Objetivo

Cerrar la toma física con una explicación por cada ítem descuadrado, no con un
ajuste global que tape el problema.

## Instrucciones

Comparas cantidad contada contra saldo del sistema, ítem por ítem, y valorizas
la diferencia al costo promedio.

## Workflow

1. Empareja por código de ítem, no por descripción.
2. Calcula la diferencia en unidades y en valor.
3. Ordena por valor absoluto de la diferencia.
4. Busca compensaciones entre ítems parecidos: suele ser un error de codificación.
5. Separa los faltantes de los sobrantes y calcula el neto.

## Reglas

- Nunca compenses un faltante con un sobrante de otro ítem sin señalarlo como hipótesis.
- Un ítem que no existe en el sistema pero sí en el conteo es un hallazgo, no un error del conteo.
- Valoriza al costo, nunca al precio de venta.
- Si la diferencia neta supera el dos por ciento del inventario, recomienda un segundo conteo.

## Inputs

- conteo: código, descripción, cantidad contada
- kardex: código, saldo en sistema, costo promedio

## Outputs

- diferencias: tabla por ítem con unidades, valor y tipo
- posibles_compensaciones: pares de ítems que podrían estar cruzados
- neto: faltante o sobrante total valorizado

## Ejemplos

Un faltante de 12 unidades del código A-113 y un sobrante de 12 del A-131 se
reportan como posible compensación por error de digitación del código.
MD,
        ],
        [
            'name'  => 'Calcular provisiones de beneficios sociales',
            'cat'   => 'recursos-humanos',
            'short' => 'Proyecta lo que se debe acumular cada mes para no llevarse un susto en el pago.',
            'tags'  => 'nómina, provisiones, beneficios',
            'body'  => <<<'MD'
## Objetivo

Que la provisión mensual refleje lo que realmente se va a pagar, para que el mes
del desembolso no descuadre el resultado.

## Instrucciones

Calculas por empleado a partir de su remuneración y su tiempo de servicio.
Señalas cuando la provisión acumulada no alcanza para el pago proyectado.

## Workflow

1. Determina la base de cálculo de cada beneficio según su naturaleza.
2. Calcula la porción mensual que corresponde acumular.
3. Suma la provisión acumulada a la fecha.
4. Proyecta el pago en la fecha de vencimiento.
5. Compara acumulado contra proyección y reporta el déficit o el exceso.

## Reglas

- Los ingresos variables entran en la base sólo cuando corresponda a su naturaleza: verifícalo, no lo asumas.
- Un empleado que ingresó a mitad de periodo acumula proporcional, no completo.
- Si falta la fecha de ingreso de alguien, detente: el proporcional no se puede estimar.
- No ajustes la provisión de meses cerrados: reporta la diferencia.

## Inputs

- empleados: sueldo, ingresos variables, fecha de ingreso
- acumulado: provisión registrada a la fecha
- periodo: mes de cálculo

## Outputs

- provision_mensual: valor por empleado y por beneficio
- acumulado_proyectado: al vencimiento
- deficit: diferencia entre lo acumulado y lo que se pagará

## Ejemplos

Un equipo de doce personas con provisión acumulada de 8.400 frente a una
proyección de pago de 9.150 arroja un déficit de 750 a cubrir en los meses restantes.
MD,
        ],
        [
            'name'  => 'Armar la lista de cierre mensual',
            'cat'   => 'contabilidad-y-tributacion',
            'short' => 'Genera la lista de verificación del cierre y marca qué falta para poder cerrar.',
            'tags'  => 'cierre, control, proceso',
            'body'  => <<<'MD'
## Objetivo

Que nadie cierre el mes por olvido de un paso, y que se vea de un vistazo qué
falta y de quién depende.

## Instrucciones

Construyes la lista a partir del proceso de la empresa y del estado real de cada
paso. No das el cierre por posible mientras quede un bloqueante.

## Workflow

1. Lista los pasos del cierre en el orden en que dependen unos de otros.
2. Marca el estado de cada uno: hecho, en curso, pendiente, bloqueado.
3. Identifica el responsable de cada paso pendiente.
4. Señala los bloqueantes: pasos que impiden avanzar a los siguientes.
5. Estima si el cierre llega a la fecha objetivo.

## Reglas

- Un paso sin responsable asignado se reporta como riesgo, no como pendiente normal.
- No marques un paso como hecho sin evidencia: el criterio es el documento, no la palabra.
- Si un bloqueante lleva más de dos días sin movimiento, escálalo.
- No propongas saltarse un paso de control para llegar a la fecha.

## Inputs

- proceso: pasos del cierre de la empresa
- estado: avance reportado por cada área
- fecha_objetivo: día comprometido de cierre

## Outputs

- checklist: paso, responsable, estado, evidencia
- bloqueantes: lista ordenada por impacto
- pronostico: si se llega a la fecha y qué lo impide

## Ejemplos

De dieciocho pasos, quince hechos y tres pendientes, uno de ellos bloqueante por
falta de la conciliación bancaria: el pronóstico es dos días de retraso.
MD,
        ],
        [
            'name'  => 'Revisar una orden de compra',
            'cat'   => 'operaciones-internas',
            'short' => 'Comprueba que la orden tenga presupuesto, autorización y condiciones claras antes de emitirse.',
            'tags'  => 'compras, control, proveedores',
            'body'  => <<<'MD'
## Objetivo

Que no salga una orden de compra que después nadie pueda pagar ni justificar.

## Instrucciones

Revisas contra cuatro cosas: presupuesto disponible, autorización según monto,
condiciones comerciales completas y proveedor habilitado.

## Workflow

1. Verifica que la cuenta presupuestaria tenga saldo disponible.
2. Verifica que el nivel de autorización corresponda al monto.
3. Revisa que estén el plazo de entrega, la forma de pago y el lugar de entrega.
4. Confirma que el proveedor esté habilitado y sin observaciones.
5. Compara el precio contra la última compra del mismo ítem.

## Reglas

- Una orden sin presupuesto disponible no se aprueba: se reporta el faltante.
- Un fraccionamiento que evita el nivel de autorización superior es un hallazgo.
- Si el precio supera en más del quince por ciento la última compra, pide justificación.
- No apruebes nada: emites un dictamen para que una persona apruebe.

## Inputs

- orden: proveedor, ítems, cantidades, precios, cuenta, solicitante
- presupuesto: saldo disponible de la cuenta
- historico: últimas compras de los mismos ítems

## Outputs

- dictamen: procede, procede con observaciones, o no procede
- observaciones: lista con el criterio que falló
- variacion_precio: contra la última compra

## Ejemplos

Una orden de 4.800 dividida en dos de 2.400 el mismo día y al mismo proveedor se
reporta como posible fraccionamiento del nivel de autorización.
MD,
        ],
        [
            'name'  => 'Preparar el flujo de caja semanal',
            'cat'   => 'finanzas-y-tesoreria',
            'short' => 'Proyecta entradas y salidas de las próximas semanas y avisa cuándo falta liquidez.',
            'tags'  => 'tesorería, liquidez, proyección',
            'body'  => <<<'MD'
## Objetivo

Saber con semanas de anticipación en qué momento el saldo se pone en rojo, para
poder mover algo antes de que pase.

## Instrucciones

Proyectas semana a semana con lo que está comprometido, no con lo que se espera.
Separas lo confirmado de lo estimado.

## Workflow

1. Parte del saldo disponible real de hoy.
2. Suma las cobranzas con fecha comprometida.
3. Resta los pagos comprometidos: proveedores, nómina, impuestos, préstamos.
4. Añade las estimaciones en una fila aparte, claramente marcada.
5. Calcula el saldo al cierre de cada semana y marca las que quedan bajo el mínimo.

## Reglas

- Una cobranza sin fecha comprometida es estimación, no ingreso proyectado.
- La nómina y los impuestos nunca se posponen en la proyección.
- Si una semana queda negativa, señálala antes de continuar con las siguientes.
- No propongas qué pagar y qué no: eso lo decide la gerencia.

## Inputs

- saldo_actual: disponible en bancos y caja
- cobranzas: cliente, valor, fecha comprometida
- pagos: proveedor o concepto, valor, fecha
- minimo: saldo mínimo operativo

## Outputs

- proyeccion: tabla por semana con entradas, salidas y saldo
- semanas_criticas: las que caen bajo el mínimo
- brecha: valor faltante en la peor semana

## Ejemplos

Con un saldo de 18.400 y la nómina en la semana tres, la proyección marca la
semana tres bajo el mínimo con una brecha de 6.100.
MD,
        ],

        // ------------------------------------------------- Vida de oficina
        [
            'name'  => 'Redactar un memorando interno',
            'cat'   => 'operaciones-internas',
            'short' => 'Convierte una instrucción en un memorando breve, claro y sin espacio a interpretación.',
            'tags'  => 'comunicación, oficina, documentos',
            'body'  => <<<'MD'
## Objetivo

Que quien lo reciba entienda en treinta segundos qué se le pide, desde cuándo y
qué pasa si no lo hace.

## Instrucciones

Escribes corto y en positivo. Un memorando de dos páginas no se lee: se archiva.

## Workflow

1. Escribe el asunto como una frase que ya diga la instrucción.
2. Primer párrafo: qué cambia y desde cuándo.
3. Segundo párrafo: qué tiene que hacer el destinatario, en pasos si son varios.
4. Tercer párrafo: a quién acudir con dudas.
5. Cierra con fecha de vigencia.

## Reglas

- Nunca cites una norma sin decir en una frase qué significa en la práctica.
- No uses "se recuerda que" para introducir algo que nunca se dijo antes.
- Una instrucción por memorando. Si hay tres temas, son tres memorandos.
- No amenaces con sanciones que no estén en el reglamento.

## Inputs

- instruccion: qué se quiere comunicar
- destinatarios: a quién va dirigido
- vigencia: desde cuándo aplica

## Outputs

- asunto: una línea
- cuerpo: texto listo para firma
- requiere_aprobacion: sí cuando cambia una condición laboral

## Ejemplos

De "hay que registrar las salidas" sale un memorando con asunto "Registro de
salidas desde el 1 de octubre", tres párrafos y un responsable de dudas.
MD,
        ],
        [
            'name'  => 'Ordenar la bandeja de correo',
            'cat'   => 'operaciones-internas',
            'short' => 'Clasifica el correo acumulado en responder hoy, delegar, archivar y ruido.',
            'tags'  => 'productividad, correo, oficina',
            'body'  => <<<'MD'
## Objetivo

Salir de una bandeja con doscientos mensajes sabiendo cuáles son los seis que
importan hoy.

## Instrucciones

Clasificas por lo que exige de ti, no por quién lo envía. Un correo del gerente
que no pide nada va a archivo igual que cualquier otro.

## Workflow

1. Descarta el ruido: notificaciones automáticas, copias informativas, promociones.
2. Separa los que sólo requieren leer y archivar.
3. Separa los que puede resolver otra persona y di quién.
4. De los que quedan, marca los que tienen fecha límite.
5. Ordena los accionables por fecha límite y por costo de no responder.

## Reglas

- Estar en copia no es una tarea: eso es archivo salvo que te mencionen.
- Un correo con más de tres preguntas debe convertirse en una reunión corta o en una llamada.
- No archives nada que contenga un compromiso tuyo sin registrarlo antes como pendiente.
- No propongas respuestas: sólo clasificas.

## Inputs

- correos: remitente, asunto, fecha, extracto, si estás en copia

## Outputs

- responder_hoy: lista ordenada con el motivo
- delegar: correo y persona sugerida
- archivar: lista
- ruido: lista, para filtrar en el futuro

## Ejemplos

De 212 mensajes salen 6 para responder hoy, 11 para delegar, 140 a archivo y 55
clasificados como ruido con tres reglas de filtro sugeridas.
MD,
        ],
        [
            'name'  => 'Preparar la agenda de la semana',
            'cat'   => 'operaciones-internas',
            'short' => 'Arma la semana con los compromisos fijos, los bloques de trabajo y el margen real.',
            'tags'  => 'agenda, planificación, oficina',
            'body'  => <<<'MD'
## Objetivo

Que la semana quepa en la semana. Una agenda llena al cien por ciento es una
agenda que se rompe el martes.

## Instrucciones

Colocas primero lo inamovible, después los bloques de trabajo profundo, y dejas
margen explícito. Si algo no entra, lo dices.

## Workflow

1. Ubica los compromisos fijos: reuniones agendadas, vencimientos, viajes.
2. Reserva bloques para las tareas que exigen concentración, en las horas de mayor rendimiento.
3. Agrupa las tareas cortas en uno o dos bloques, no repartidas.
4. Deja al menos un quince por ciento del tiempo sin asignar.
5. Señala lo que no entró y propón a qué semana pasa.

## Reglas

- Nunca agendes trabajo profundo justo después de una reunión larga.
- Dos bloques de dos horas rinden más que cuatro de una: agrupa.
- Si los compromisos fijos superan el sesenta por ciento de la semana, avisa antes de planificar.
- No muevas un compromiso con terceros sin marcarlo como decisión a confirmar.

## Inputs

- compromisos: reuniones y vencimientos con fecha y duración
- tareas: pendientes con esfuerzo estimado y prioridad
- preferencias: horario de mayor rendimiento

## Outputs

- agenda: día por día con bloques
- no_entro: tareas desplazadas con propuesta de fecha
- carga: porcentaje ocupado de la semana

## Ejemplos

Una semana con catorce horas de reuniones fijas deja dos bloques de trabajo
profundo y desplaza tres tareas a la semana siguiente. Carga del ochenta por ciento.
MD,
        ],
        [
            'name'  => 'Priorizar los pendientes del día',
            'cat'   => 'operaciones-internas',
            'short' => 'Convierte una lista larga en tres cosas que sí se van a hacer hoy.',
            'tags'  => 'productividad, prioridades, oficina',
            'body'  => <<<'MD'
## Objetivo

Terminar el día habiendo movido lo que importaba, no habiendo tachado lo que era
fácil.

## Instrucciones

Ordenas por consecuencia de no hacerlo y por si desbloquea a otra persona. Lo
urgente que no tiene consecuencia no es prioridad.

## Workflow

1. Descarta lo que no es tuyo y di a quién corresponde.
2. Marca lo que bloquea el trabajo de otra persona: eso sube.
3. Marca lo que tiene consecuencia real hoy: vencimiento, compromiso, pago.
4. Estima el esfuerzo de cada uno y compáralo con las horas disponibles.
5. Elige un máximo de tres prioridades y deja el resto explícitamente para después.

## Reglas

- Tres prioridades, no cinco. Si hay cinco, no hay ninguna.
- Una tarea que lleva más de dos días en la lista o se hace hoy o se elimina: dilo.
- No metas en el día más esfuerzo del que caben horas disponibles.
- Lo que se posterga se dice en voz alta, no se esconde al final de la lista.

## Inputs

- pendientes: descripción, vencimiento, quién espera, esfuerzo estimado
- horas_disponibles: tiempo real libre hoy

## Outputs

- prioridades: máximo tres, con el motivo
- delegar: tareas y responsable
- postergar: con fecha propuesta o propuesta de descarte

## Ejemplos

De diecisiete pendientes y cinco horas libres salen tres prioridades, cuatro
delegadas y dos propuestas para descarte por llevar nueve días sin avanzar.
MD,
        ],
        [
            'name'  => 'Hacer seguimiento a un compromiso',
            'cat'   => 'operaciones-internas',
            'short' => 'Redacta el recordatorio justo, en el tono justo, sin sonar a reclamo.',
            'tags'  => 'seguimiento, comunicación, oficina',
            'body'  => <<<'MD'
## Objetivo

Que el compromiso avance sin quemar la relación. La mayoría de los recordatorios
fallan por el tono, no por el contenido.

## Instrucciones

Recuerdas el compromiso con su fecha original, preguntas por el estado y ofreces
ayuda. No supones mala fe ni pides disculpas por escribir.

## Workflow

1. Recuerda qué se acordó y cuándo, citando dónde se acordó.
2. Pregunta por el estado en una sola frase.
3. Ofrece algo concreto que desbloquee.
4. Propón una fecha nueva si la original ya pasó.
5. Ajusta el tono al número de recordatorio: el tercero no puede ser igual que el primero.

## Reglas

- Nunca empieces con "sólo para recordarte": va directo a la papelera.
- No copies al jefe en el primer recordatorio.
- Si es el tercer recordatorio, propón escalar de forma explícita y avisada.
- No pidas disculpas por hacer seguimiento de algo acordado.

## Inputs

- compromiso: qué, quién, fecha acordada, dónde se acordó
- historial: recordatorios previos
- urgencia: qué se bloquea si no avanza

## Outputs

- mensaje: texto listo para enviar
- canal: correo, chat o llamada, con el motivo
- escalar: sí o no, y a quién

## Ejemplos

Un tercer recordatorio sobre un informe vencido hace ocho días propone una
llamada de diez minutos y avisa que se escalará el viernes si no hay avance.
MD,
        ],
        [
            'name'  => 'Liquidar gastos de viaje',
            'cat'   => 'finanzas-y-tesoreria',
            'short' => 'Revisa la liquidación contra la política y deja lista la devolución o el reembolso.',
            'tags'  => 'viáticos, gastos, política',
            'body'  => <<<'MD'
## Objetivo

Cerrar el viaje con la cuenta clara: qué se aprueba, qué se rechaza y quién le
debe a quién.

## Instrucciones

Revisas cada gasto contra la política de viáticos y contra el anticipo
entregado. Lo rechazado se explica con el punto de la política que aplica.

## Workflow

1. Verifica que las fechas de los comprobantes caigan dentro del viaje.
2. Compara cada gasto contra el tope de su categoría.
3. Revisa que el hospedaje y el transporte tengan comprobante a nombre de la empresa.
4. Suma lo aprobado y compáralo con el anticipo.
5. Determina si corresponde reembolso a la persona o devolución a la empresa.

## Reglas

- Un gasto fuera de las fechas del viaje se rechaza aunque sea razonable.
- Lo que supera el tope se aprueba hasta el tope y la diferencia queda a cargo de la persona.
- Las bebidas alcohólicas y los gastos de acompañantes no se aprueban.
- No apruebes un gasto sin comprobante aunque el monto sea pequeño: repórtalo.

## Inputs

- gastos: fecha, categoría, proveedor, valor, comprobante
- viaje: fechas, destino, motivo
- anticipo: valor entregado
- politica: topes por categoría

## Outputs

- aprobados: tabla por categoría
- rechazados: gasto y punto de la política que falló
- saldo: reembolso a la persona o devolución a la empresa

## Ejemplos

Un anticipo de 600 con 528 aprobados y 74 rechazados por exceder el tope de
alimentación deja una devolución de 72 a la empresa.
MD,
        ],
        [
            'name'  => 'Responder una solicitud de permiso',
            'cat'   => 'recursos-humanos',
            'short' => 'Evalúa la solicitud contra el saldo y la cobertura del puesto, y redacta la respuesta.',
            'tags'  => 'recursos humanos, permisos, oficina',
            'body'  => <<<'MD'
## Objetivo

Responder rápido y con criterio, sin dejar el puesto descubierto ni negar algo
que corresponde por derecho.

## Instrucciones

Compruebas saldo disponible, cobertura del puesto y coincidencia con otras
ausencias. Si corresponde por ley, no es una decisión discrecional y lo dices.

## Workflow

1. Identifica el tipo de permiso y si es de los que corresponden por derecho.
2. Verifica el saldo disponible de la persona.
3. Revisa quién cubre sus funciones en esas fechas.
4. Cruza con las ausencias ya aprobadas del mismo equipo.
5. Redacta la respuesta con la decisión y el motivo.

## Reglas

- Un permiso que corresponde por ley no se niega por conveniencia operativa: se organiza la cobertura.
- Si más del treinta por ciento del equipo está ausente en esas fechas, señálalo antes de aprobar.
- No pidas el motivo de un permiso cuando el tipo de permiso no lo exige.
- No apruebes tú: preparas la decisión para quien firma.

## Inputs

- solicitud: persona, tipo, fechas, motivo si aplica
- saldo: días disponibles
- equipo: ausencias aprobadas en esas fechas
- cobertura: quién puede asumir sus funciones

## Outputs

- dictamen: procede, procede con condiciones, o no procede
- motivo: en una frase
- respuesta: texto listo para enviar a la persona

## Ejemplos

Una solicitud de tres días con saldo de dos días devuelve procede con
condiciones: dos días con cargo a saldo y uno sin remuneración, a confirmar.
MD,
        ],
        [
            'name'  => 'Escribir una minuta de decisión',
            'cat'   => 'operaciones-internas',
            'short' => 'Deja por escrito qué se decidió, quién decidió y qué se descartó, en una página.',
            'tags'  => 'decisiones, documentación, oficina',
            'body'  => <<<'MD'
## Objetivo

Que dentro de seis meses se pueda saber por qué se decidió eso y no otra cosa,
sin tener que preguntarle a nadie.

## Instrucciones

Registras la decisión, quién la tomó, qué alternativas se descartaron y con qué
información se contaba. Lo último es lo que casi siempre falta.

## Workflow

1. Enuncia la decisión en una frase, en presente.
2. Anota quién decidió y quién fue consultado.
3. Lista las alternativas evaluadas y el motivo de descarte de cada una.
4. Anota los supuestos con los que se decidió.
5. Define qué haría reconsiderar la decisión.

## Reglas

- Una decisión sin responsable identificado no es una decisión: es una conversación.
- No registres el debate, sólo el resultado y los motivos.
- Si la decisión se tomó con información incompleta, dilo: es el dato más útil a futuro.
- No adornes: una minuta no persuade, documenta.

## Inputs

- contexto: qué había que decidir
- participantes: quiénes estuvieron
- alternativas: opciones sobre la mesa
- informacion: datos disponibles al momento

## Outputs

- decision: una frase
- responsable: quién
- descartadas: alternativa y motivo
- supuestos: lista
- revisar_si: condiciones que obligarían a reconsiderar

## Ejemplos

Una decisión de proveedor registra tres alternativas descartadas, el supuesto de
volumen mensual con el que se negoció el precio y la condición de revisión si el
volumen cae por debajo de ese número.
MD,
        ],
        [
            'name'  => 'Preparar el reporte mensual para gerencia',
            'cat'   => 'operaciones-internas',
            'short' => 'Resume el mes en una página: qué pasó, qué se desvió y qué decisión hace falta.',
            'tags'  => 'reportes, gerencia, oficina',
            'body'  => <<<'MD'
## Objetivo

Que la gerencia lea una página y sepa exactamente qué está pasando y qué tiene
que decidir este mes.

## Instrucciones

Empiezas por lo que se desvió, no por lo que salió bien. Cada desviación lleva
causa y propuesta. Un reporte sin propuestas es un informe de daños.

## Workflow

1. Compara lo real contra lo presupuestado y contra el mes anterior.
2. Quédate con las desviaciones que superen el umbral acordado.
3. Para cada una, escribe la causa en una frase.
4. Propón una acción con responsable y fecha.
5. Cierra con las decisiones que dependen de la gerencia.

## Reglas

- Nunca presentes una desviación sin causa. "Fue mayor al presupuesto" no es una causa.
- No incluyas indicadores que nadie va a usar para decidir.
- Si la causa no se conoce, escribe que no se conoce y qué harás para averiguarlo.
- No maquilles un mal mes con comparaciones convenientes.

## Inputs

- real: cifras del periodo
- presupuesto: cifras previstas
- mes_anterior: para la tendencia
- umbral: porcentaje a partir del cual una desviación es relevante

## Outputs

- resumen: tres líneas con lo esencial
- desviaciones: cifra, causa, acción, responsable
- decisiones: lo que requiere aprobación de gerencia

## Ejemplos

Un mes con gasto de personal ocho por ciento sobre presupuesto reporta la causa,
el efecto en el resultado y una propuesta con responsable y fecha.
MD,
        ],
        [
            'name'  => 'Revisar un comprobante de egreso',
            'cat'   => 'contabilidad-y-tributacion',
            'short' => 'Verifica respaldo, autorización y cuenta antes de que el pago salga.',
            'tags'  => 'pagos, control, comprobantes',
            'body'  => <<<'MD'
## Objetivo

Que ningún pago salga sin respaldo completo ni imputado a la cuenta equivocada.

## Instrucciones

Revisas cuatro cosas en orden: que exista la obligación, que el respaldo esté
completo, que la autorización corresponda al monto y que la cuenta sea la
adecuada.

## Workflow

1. Confirma que existe la factura o el documento que origina la obligación.
2. Verifica que el beneficiario del pago coincida con el emisor del documento.
3. Revisa que la retención aplicada corresponda al tipo de transacción.
4. Comprueba el nivel de autorización según el monto.
5. Revisa la cuenta contable imputada contra la naturaleza del gasto.

## Reglas

- Un pago a un beneficiario distinto del emisor requiere cesión documentada: sin ella, no procede.
- Nunca apruebes un anticipo sin documento que lo sustente.
- Si la cuenta imputada no corresponde, propón la correcta pero no la cambies.
- Un comprobante sin firma de autorización es un hallazgo, no un trámite pendiente.

## Inputs

- comprobante: beneficiario, valor, cuenta, retenciones, autorizaciones
- documento_origen: factura o contrato que sustenta

## Outputs

- dictamen: procede o no procede
- observaciones: lista con el criterio que falló
- cuenta_sugerida: si la imputación es incorrecta

## Ejemplos

Un egreso a nombre de una persona distinta al emisor de la factura devuelve
no procede, con la observación de cesión de derechos no documentada.
MD,
        ],
        [
            'name'  => 'Coordinar una reunión con varias agendas',
            'cat'   => 'operaciones-internas',
            'short' => 'Encuentra el horario que funciona para todos y arma la convocatoria con agenda.',
            'tags'  => 'reuniones, coordinación, oficina',
            'body'  => <<<'MD'
## Objetivo

Cerrar la reunión en un solo intercambio de correos, con agenda y con la gente
que de verdad hace falta.

## Instrucciones

Primero decides quién es imprescindible y quién es opcional. Después buscas el
horario. Convocar a doce personas por si acaso es la forma más cara de reunirse.

## Workflow

1. Define el objetivo de la reunión en una frase. Si no sale, no hace falta reunirse.
2. Separa participantes imprescindibles de opcionales.
3. Cruza las disponibilidades y propón dos o tres opciones.
4. Ajusta la duración al objetivo, no al bloque de una hora por costumbre.
5. Escribe la convocatoria con agenda, objetivo y lo que hay que leer antes.

## Reglas

- Si el objetivo se cumple con un correo, propón el correo y no la reunión.
- Nadie imprescindible se queda fuera por conveniencia de horario: se busca otro día.
- Una reunión sin agenda escrita no se convoca.
- No agendes sobre la hora de almuerzo ni al final del viernes salvo urgencia real.

## Inputs

- objetivo: para qué se reúnen
- participantes: nombres y rol en la decisión
- disponibilidad: agendas de cada uno
- duracion_estimada

## Outputs

- opciones: dos o tres franjas que funcionan
- convocatoria: texto con objetivo, agenda y preparación previa
- alternativa: propuesta de resolverlo sin reunión si aplica

## Ejemplos

Una decisión de presupuesto con cuatro imprescindibles y dos opcionales se
resuelve en cuarenta minutos, con dos opciones de horario y un documento a leer antes.
MD,
        ],
        [
            'name'  => 'Preparar el expediente de un proveedor nuevo',
            'cat'   => 'operaciones-internas',
            'short' => 'Reúne y revisa la documentación mínima antes de habilitar a un proveedor.',
            'tags'  => 'proveedores, cumplimiento, compras',
            'body'  => <<<'MD'
## Objetivo

Que ningún proveedor se habilite sin la documentación que después va a hacer
falta para pagarle y para justificar el gasto.

## Instrucciones

Revisas la lista de documentos obligatorios contra lo entregado y señalas lo que
falta. No habilitas: preparas el expediente para que alguien habilite.

## Workflow

1. Verifica la identificación fiscal y que esté activa.
2. Revisa que el nombre o razón social coincida en todos los documentos.
3. Comprueba la cuenta bancaria y que el titular sea el mismo proveedor.
4. Revisa los documentos específicos según el tipo de servicio.
5. Señala lo faltante y clasifícalo entre bloqueante y subsanable después.

## Reglas

- Una cuenta bancaria a nombre de un tercero es bloqueante, sin excepción.
- Si la identificación fiscal no está activa, el expediente se detiene ahí.
- No habilites con documentación incompleta bajo compromiso de entregarla luego.
- Verifica coincidencia exacta de nombres: una letra distinta traba el pago.

## Inputs

- proveedor: datos declarados
- documentos: los entregados
- tipo_servicio: para saber qué documentos adicionales aplican

## Outputs

- estado: completo, incompleto subsanable, o bloqueado
- faltantes: lista con clasificación
- observaciones: inconsistencias entre documentos

## Ejemplos

Un proveedor con cuenta bancaria a nombre de su representante legal en lugar de
la empresa se reporta como bloqueado, con la observación de titularidad.
MD,
        ],
        [
            'name'  => 'Responder a una observación de auditoría',
            'cat'   => 'legal-y-cumplimiento',
            'short' => 'Redacta la respuesta con evidencia, plan de acción y fecha, sin justificaciones vacías.',
            'tags'  => 'auditoría, cumplimiento, respuesta',
            'body'  => <<<'MD'
## Objetivo

Cerrar la observación con una respuesta que el auditor pueda aceptar: hechos,
evidencia y un plan con responsable y fecha.

## Instrucciones

Aceptas o discrepas, pero siempre con evidencia. Una respuesta que sólo explica
por qué pasó, sin decir qué se va a hacer, no cierra nada.

## Workflow

1. Resume la observación en una frase, con las palabras del auditor.
2. Indica si se acepta, se acepta parcialmente o se discrepa.
3. Si se discrepa, adjunta la evidencia que lo sustenta.
4. Si se acepta, describe la causa raíz en una frase.
5. Define la acción correctiva con responsable y fecha comprometida.

## Reglas

- No discrepes sin evidencia documental: empeora la observación.
- La causa raíz no es "falta de personal": eso es un síntoma. Busca el control que falló.
- Nunca comprometas una fecha que no venga del responsable del área.
- No prometas un cambio de sistema como acción correctiva si no está presupuestado.

## Inputs

- observacion: texto del auditor
- evidencia: documentos disponibles
- responsable: área que debe corregir

## Outputs

- postura: aceptada, parcial o discrepancia
- causa_raiz: una frase
- plan: acción, responsable, fecha
- evidencia_adjunta: lista de documentos

## Ejemplos

Una observación por egresos sin firma de autorización se acepta, con causa raíz
en un flujo sin control de bloqueo y un plan de dos semanas con responsable.
MD,
        ],
        [
            'name'  => 'Dar la bienvenida a alguien que entra',
            'cat'   => 'recursos-humanos',
            'short' => 'Arma el plan de los primeros quince días con accesos, personas y primeros encargos.',
            'tags'  => 'inducción, recursos humanos, oficina',
            'body'  => <<<'MD'
## Objetivo

Que quien entra sea útil en dos semanas y no en dos meses, y que no tenga que
pedir tres veces el mismo acceso.

## Instrucciones

Preparas tres cosas: lo que necesita tener, a quién necesita conocer y qué va a
hacer. En ese orden y con fechas.

## Workflow

1. Lista los accesos y herramientas que necesita según su rol, con responsable de cada uno.
2. Define las personas con las que debe hablar la primera semana y para qué.
3. Elige un primer encargo real, pequeño y terminable en la primera semana.
4. Agenda los puntos de control: día tres, día siete, día quince.
5. Define qué debe saber hacer solo al día quince.

## Reglas

- Los accesos se piden antes del primer día, no el primer día.
- El primer encargo tiene que ser real y terminable: un proyecto grande desorienta.
- No llenes la primera semana de reuniones de presentación.
- Si no hay alguien asignado para responder dudas, el plan no arranca.

## Inputs

- rol: puesto y funciones
- equipo: con quién va a trabajar
- herramientas: sistemas que usa el área

## Outputs

- checklist_accesos: herramienta, responsable, fecha
- agenda_primera_semana: conversaciones con objetivo
- primer_encargo: descripción y criterio de terminado
- hitos: qué debe lograr al día tres, siete y quince

## Ejemplos

Una incorporación a contabilidad recibe seis accesos pedidos con dos días de
anticipación, cuatro conversaciones y la conciliación de una cuenta pequeña como
primer encargo.
MD,
        ],
        [
            'name'  => 'Archivar y nombrar documentos',
            'cat'   => 'operaciones-internas',
            'short' => 'Aplica una convención de nombres y ubicación para que el archivo se pueda buscar.',
            'tags'  => 'archivo, orden, documentos',
            'body'  => <<<'MD'
## Objetivo

Que cualquiera encuentre un documento en menos de un minuto sin preguntarle a
quien lo guardó.

## Instrucciones

Aplicas una convención única y la respetas incluso cuando el nombre queda largo.
La consistencia vale más que la elegancia.

## Workflow

1. Identifica el tipo de documento y a qué proceso pertenece.
2. Extrae la fecha del documento, no la de archivo.
3. Extrae la contraparte: proveedor, cliente, empleado.
4. Arma el nombre con el patrón acordado: fecha, tipo, contraparte, referencia.
5. Propón la carpeta según el proceso y el año.

## Reglas

- La fecha va primero y en formato AAAA-MM-DD, para que el orden alfabético sea cronológico.
- Nada de "final", "definitivo" ni "v2 corregido" en el nombre: para eso está la fecha.
- Sin espacios ni tildes ni caracteres especiales en el nombre del archivo.
- Un documento vive en una sola carpeta: si hace falta en dos sitios, se enlaza.

## Inputs

- documentos: archivo, tipo, contenido o metadatos
- convencion: patrón acordado por la empresa

## Outputs

- renombrados: nombre original y nombre propuesto
- ubicacion: carpeta destino
- duplicados: archivos que son el mismo documento

## Ejemplos

"Factura proveedor FINAL (2).pdf" pasa a
"2026-09-03_factura_distribuidora-andina_001-001-004512.pdf" en la carpeta del
proceso de compras del año.
MD,
        ],
        [
            'name'  => 'Preparar la entrega de un puesto',
            'cat'   => 'recursos-humanos',
            'short' => 'Documenta lo que hace, lo que tiene y lo que queda pendiente antes de que la persona salga.',
            'tags'  => 'entrega, recursos humanos, continuidad',
            'body'  => <<<'MD'
## Objetivo

Que la salida de alguien no se lleve consigo información que nadie más tiene.

## Instrucciones

Documentas tres cosas: procesos que ejecuta, activos y accesos que controla, y
pendientes con su estado real. El estado real, no el declarado.

## Workflow

1. Lista los procesos recurrentes que ejecuta, con su frecuencia y su fecha crítica.
2. Lista los accesos, claves de sistemas y activos bajo su responsabilidad.
3. Lista los pendientes abiertos con estado, contraparte y próximo paso.
4. Identifica lo que sólo esa persona sabe hacer y quién lo asume.
5. Agenda el traspaso con la persona que recibe, proceso por proceso.

## Reglas

- Un proceso que sólo esa persona sabe hacer es un riesgo: márcalo aunque incomode.
- Los accesos se revocan el último día, no la semana siguiente.
- No des por entregado un pendiente sin que quien recibe confirme que lo entendió.
- Las claves compartidas se rotan, no se transfieren.

## Inputs

- puesto: funciones y procesos
- pendientes: trabajos abiertos
- accesos: sistemas y activos asignados
- receptor: quién asume

## Outputs

- manual_minimo: procesos con pasos y fechas críticas
- inventario: accesos y activos con acción a tomar
- pendientes: estado y próximo paso
- riesgos: conocimiento sin respaldo en otra persona

## Ejemplos

Una entrega en tesorería identifica dos procesos sin respaldo, once accesos a
revocar y cuatro pendientes con contraparte externa.
MD,
        ],
    ];
}
