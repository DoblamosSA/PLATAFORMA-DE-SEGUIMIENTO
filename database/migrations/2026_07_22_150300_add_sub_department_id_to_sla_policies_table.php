<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Reemplaza el enum fijo sla_policies.tipo por una relacion real hacia
 * sub_departments, para que las politicas de SLA se definan por
 * subdepartamento en vez de por un tipo de trabajo fijo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sla_policies', function (Blueprint $table) {
            $table->foreignId('sub_department_id')->nullable()->after('tipo')
                ->constrained('sub_departments')->restrictOnDelete();
        });

        $idsPorTipo = $this->asegurarSubDepartamentosDeTipo();

        foreach ($idsPorTipo as $tipo => $subDepartmentId) {
            DB::table('sla_policies')->where('tipo', $tipo)->update(['sub_department_id' => $subDepartmentId]);
        }

        Schema::table('sla_policies', function (Blueprint $table) {
            $table->dropUnique(['tipo', 'prioridad']);
            $table->dropColumn('tipo');
            $table->foreignId('sub_department_id')->nullable(false)->change();
            $table->unique(['sub_department_id', 'prioridad']);
        });
    }

    public function down(): void
    {
        Schema::table('sla_policies', function (Blueprint $table) {
            $table->enum('tipo', ['software', 'soporte', 'infraestructura'])->nullable()->after('id');
        });

        $subDepartamentos = DB::table('sub_departments')->whereIn('slug', ['software', 'soporte', 'infraestructura'])->get(['id', 'slug']);
        foreach ($subDepartamentos as $sd) {
            DB::table('sla_policies')->where('sub_department_id', $sd->id)->update(['tipo' => $sd->slug]);
        }

        Schema::table('sla_policies', function (Blueprint $table) {
            $table->dropUnique(['sub_department_id', 'prioridad']);
            $table->dropConstrainedForeignId('sub_department_id');
            $table->enum('tipo', ['software', 'soporte', 'infraestructura'])->nullable(false)->change();
            $table->unique(['tipo', 'prioridad']);
        });
    }

    /** @return array<string, int> mapa tipo => sub_department_id */
    private function asegurarSubDepartamentosDeTipo(): array
    {
        $departmentId = DB::table('departments')->where('slug', 'tecnologia')->value('id');

        if (! $departmentId) {
            $departmentId = DB::table('departments')->insertGetId([
                'nombre' => 'Tecnología',
                'slug' => 'tecnologia',
                'descripcion' => null,
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $mapaTipos = [
            'software' => ['nombre' => 'Software', 'icono' => 'code', 'color' => 'indigo'],
            'soporte' => ['nombre' => 'Soporte', 'icono' => 'support', 'color' => 'teal'],
            'infraestructura' => ['nombre' => 'Infraestructura', 'icono' => 'server', 'color' => 'cyan'],
        ];

        $ids = [];

        foreach ($mapaTipos as $slug => $meta) {
            $id = DB::table('sub_departments')->where('department_id', $departmentId)->where('slug', $slug)->value('id');

            if (! $id) {
                $id = DB::table('sub_departments')->insertGetId([
                    'department_id' => $departmentId,
                    'nombre' => $meta['nombre'],
                    'slug' => $slug,
                    'descripcion' => null,
                    'icono' => $meta['icono'],
                    'color' => $meta['color'],
                    'activo' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $ids[$slug] = $id;
        }

        return $ids;
    }
};
