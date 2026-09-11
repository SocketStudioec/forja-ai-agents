<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Bitácora de actividad. Nunca almacena contraseñas, tokens ni secretos:
 * los metadatos pasan por una lista de claves prohibidas antes de guardarse.
 */
final class Audit
{
    private const FORBIDDEN = ['password', 'password_hash', 'token', 'secret', 'api_key', 'csrf_token', 'authorization'];

    public static function log(string $action, string $resourceType, ?int $resourceId = null, array $metadata = [], ?string $actorLabel = null): void
    {
        $clean = [];
        foreach ($metadata as $k => $v) {
            $key = strtolower((string) $k);
            foreach (self::FORBIDDEN as $bad) {
                if (strpos($key, $bad) !== false) {
                    continue 2;
                }
            }
            if (is_scalar($v) || $v === null) {
                $clean[$k] = is_string($v) ? mb_substr($v, 0, 200) : $v;
            } elseif (is_array($v)) {
                $clean[$k] = mb_substr(implode(', ', array_map('strval', array_slice($v, 0, 10))), 0, 200);
            }
        }

        $user = Auth::check() ? Auth::user() : null;

        try {
            Database::insert('audit_logs', [
                'user_id'       => $user ? (int) $user['id'] : null,
                'actor_label'   => $actorLabel ?? ($user ? $user['email'] : 'visitante'),
                'action'        => mb_substr($action, 0, 60),
                'resource_type' => mb_substr($resourceType, 0, 40),
                'resource_id'   => $resourceId,
                'metadata'      => $clean ? json_encode($clean, JSON_UNESCAPED_UNICODE) : null,
                'ip_hash'       => Str::ipHash(),
            ]);
        } catch (\Throwable $e) {
            // La auditoría jamás debe tumbar una petición del usuario.
            error_log('[ai-skills] audit failed: ' . $e->getMessage());
        }
    }

    /** Etiqueta legible para la tabla de actividad. */
    public static function label(string $action): string
    {
        $map = [
            'user_registered'     => 'Usuario registrado',
            'user_created'        => 'Usuario creado',
            'user_updated'        => 'Usuario editado',
            'user_deleted'        => 'Usuario eliminado',
            'user_suspended'      => 'Usuario suspendido',
            'user_activated'      => 'Usuario activado',
            'user_role_changed'   => 'Rol modificado',
            'login'               => 'Inicio de sesión',
            'login_failed'        => 'Intento fallido',
            'logout'              => 'Cierre de sesión',
            'password_reset'      => 'Contraseña restablecida',
            'skill_created'       => 'Skill creada',
            'skill_updated'       => 'Skill modificada',
            'skill_deleted'       => 'Skill eliminada',
            'skill_published'     => 'Skill publicada',
            'skill_unpublished'   => 'Skill ocultada',
            'skill_approved'      => 'Skill aprobada',
            'skill_rejected'      => 'Skill rechazada',
            'agent_created'       => 'Agente creado',
            'agent_updated'       => 'Agente modificado',
            'agent_deleted'       => 'Agente eliminado',
            'agent_published'     => 'Agente publicado',
            'submission_received' => 'Skill enviada',
            'submission_approved' => 'Envío aprobado',
            'submission_rejected' => 'Envío rechazado',
            'download'            => 'Descarga',
            'category_created'    => 'Categoría creada',
            'category_updated'    => 'Categoría editada',
            'category_deleted'    => 'Categoría eliminada',
            'account_deletion'    => 'Baja de cuenta',
            'access_denied'       => 'Acceso denegado',
        ];
        return $map[$action] ?? ucfirst(str_replace('_', ' ', $action));
    }
}
