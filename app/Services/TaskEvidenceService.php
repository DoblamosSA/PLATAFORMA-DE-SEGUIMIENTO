<?php

namespace App\Services;

use App\Models\Task;
use App\Models\TaskActivity;
use App\Models\TaskEvidence;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Persiste imagenes de evidencia (ya comprimidas en el cliente) en disco public.
 */
class TaskEvidenceService
{
    public const MIMES = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];

    public const MAX_BYTES = 5 * 1024 * 1024; // post-compresion

    public const MAX_POR_LOTE = 10;

    /**
     * @param  array<int, UploadedFile>  $archivos
     * @return list<TaskEvidence>
     */
    public function guardarParaDescripcion(Task $task, User $user, array $archivos): array
    {
        return $this->guardar($task, $user, $archivos, null);
    }

    /**
     * @param  array<int, UploadedFile>  $archivos
     * @return list<TaskEvidence>
     */
    public function guardarParaComentario(Task $task, User $user, TaskActivity $actividad, array $archivos): array
    {
        return $this->guardar($task, $user, $archivos, $actividad);
    }

    /**
     * @param  array<int, UploadedFile>  $archivos
     * @return list<TaskEvidence>
     */
    protected function guardar(Task $task, User $user, array $archivos, ?TaskActivity $actividad): array
    {
        $guardadas = [];

        foreach (array_slice($archivos, 0, self::MAX_POR_LOTE) as $archivo) {
            if (! $archivo instanceof UploadedFile || ! $archivo->isValid()) {
                continue;
            }

            $mime = $archivo->getMimeType() ?: '';
            if (! in_array($mime, self::MIMES, true)) {
                continue;
            }

            if ($archivo->getSize() > self::MAX_BYTES) {
                continue;
            }

            $nombre = Str::uuid()->toString().'.'.$this->extension($mime, $archivo);
            $path = $archivo->storeAs('evidencias/'.$task->id, $nombre, 'public');

            $guardadas[] = TaskEvidence::create([
                'task_id' => $task->id,
                'task_activity_id' => $actividad?->id,
                'user_id' => $user->id,
                'path' => $path,
                'nombre_original' => Str::limit($archivo->getClientOriginalName() ?: $nombre, 180, ''),
                'mime' => $mime,
                'bytes' => (int) $archivo->getSize(),
            ]);
        }

        return $guardadas;
    }

    public function eliminar(TaskEvidence $evidencia): void
    {
        $evidencia->eliminarArchivo();
        $evidencia->delete();
    }

    protected function extension(string $mime, UploadedFile $archivo): string
    {
        return match ($mime) {
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => $archivo->guessExtension() ?: 'jpg',
        };
    }
}
