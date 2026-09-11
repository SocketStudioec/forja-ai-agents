<?php use App\Core\Config; use App\Core\Mailer; ?>
<section class="section-sm">
  <div class="shell shell-narrow">
    <div class="section-head">
      <span class="eyebrow"><span class="dot"></span>Privacidad</span>
      <h1 style="font-size:clamp(1.9rem,4.2vw,2.8rem);margin-top:1.2rem">Tratamiento de datos personales</h1>
      <p class="lede mt-2">
        Esta plataforma pide un correo electrónico incluso a quien envía contenido sin crear cuenta.
        Aquí está exactamente qué se guarda, para qué y cómo pedir que se borre.
      </p>
    </div>

    <div class="prose">
      <h2>Qué datos se recogen</h2>
      <p><strong>Si envías contenido sin cuenta:</strong> tu correo electrónico, el nombre que indiques
      (opcional), el contenido y el archivo que adjuntes, y una huella irreversible de tu dirección IP
      usada sólo para limitar abusos. La IP no se guarda en claro.</p>
      <p><strong>Si creas una cuenta:</strong> nombre, apellido opcional, nombre de usuario, correo,
      una descripción opcional y la contraseña, que se almacena cifrada con un algoritmo de un solo
      sentido. Nadie, ni la administración, puede leerla.</p>
      <p><strong>Al descargar:</strong> se registra qué recurso y en qué formato, junto a la huella de
      IP. Sirve para el contador de descargas y para detectar abuso.</p>

      <h2>Para qué se usa el correo</h2>
      <ul>
        <li>Confirmarte que recibimos tu envío, con un identificador de seguimiento.</li>
        <li>Avisarte si el contenido se aprueba, se publica o requiere cambios, incluyendo el motivo.</li>
        <li>En cuentas registradas: recuperación de contraseña y avisos sobre tus publicaciones.</li>
      </ul>
      <p>
        No se usa para comunicaciones comerciales ni se cede a terceros. Si alguna vez se quisiera usar
        con fines de marketing se pediría un consentimiento aparte y específico.
      </p>

      <h2>Cuánto tiempo se conserva</h2>
      <ul>
        <li><strong>Envíos rechazados:</strong> se conservan mientras sean útiles para resolver dudas sobre la decisión, y se eliminan cuando ya no lo son.</li>
        <li><strong>Contenido publicado:</strong> permanece mientras siga en la biblioteca.</li>
        <li><strong>Cuentas:</strong> mientras la cuenta exista.</li>
        <li><strong>Bitácora de actividad:</strong> registra acciones y recursos, nunca contraseñas ni claves.</li>
      </ul>

      <h2>Tus derechos</h2>
      <p>
        Puedes pedir acceso, rectificación, eliminación u oposición al tratamiento de tus datos.
      </p>
      <ul>
        <li><strong>Con cuenta:</strong> desde <a href="<?= url('/dashboard/account') ?>">Mi cuenta</a> puedes
        editar tus datos o eliminar la cuenta por completo. Al eliminarla, tus datos personales se
        borran y el contenido que hubieras publicado queda sin autor asociado.</li>
        <li><strong>Sin cuenta:</strong> escribe a <a href="mailto:<?= e(Mailer::adminAddress()) ?>"><?= e(Mailer::adminAddress()) ?></a>
        citando el identificador del envío que recibiste por correo. Se elimina el registro y el archivo adjunto.</li>
      </ul>
      <p>
        La eliminación se aplica salvo que exista una obligación legal de conservación que lo impida,
        en cuyo caso se informa del motivo y del plazo.
      </p>

      <h2>Seguridad</h2>
      <ul>
        <li>Todo el tráfico viaja cifrado con HTTPS.</li>
        <li>Las contraseñas se almacenan con hash y sal, nunca en texto legible.</li>
        <li>Los archivos adjuntos se guardan fuera del directorio público: sólo se sirven a administradores.</li>
        <li>Las credenciales de producción viven en variables de entorno, nunca en el código.</li>
        <li>Los formularios están protegidos contra falsificación de peticiones y limitados por frecuencia.</li>
      </ul>

      <h2>Responsable</h2>
      <p>
        <?= e(Config::appName()) ?>. Para cualquier consulta sobre datos personales:
        <a href="mailto:<?= e(Mailer::adminAddress()) ?>"><?= e(Mailer::adminAddress()) ?></a>.
      </p>

      <hr>
      <p class="text-sm faint">Última actualización: <?= e(date('d/m/Y')) ?>.</p>
    </div>
  </div>
</section>
