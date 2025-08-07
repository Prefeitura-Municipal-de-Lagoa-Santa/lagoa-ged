<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\ImportDocumentsJob;
use App\Jobs\OptimizedImportDocumentsJob;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

class ImportDocuments extends Command
{
    protected $signature = 'documents:import 
                            {file : Caminho do arquivo CSV}
                            {user_id : ID do usuário}
                            {--read-groups= : IDs dos grupos de leitura separados por vírgula}
                            {--write-groups= : IDs dos grupos de escrita separados por vírgula}
                            {--optimized : Usar a versão otimizada do job}
                            {--sync : Executar de forma síncrona (não usar fila)}';

    protected $description = 'Importa documentos de um arquivo CSV';

    public function handle()
    {
        $filePath = $this->argument('file');
        $userId = $this->argument('user_id');
        $readGroups = $this->option('read-groups') ? explode(',', $this->option('read-groups')) : [];
        $writeGroups = $this->option('write-groups') ? explode(',', $this->option('write-groups')) : [];
        $optimized = $this->option('optimized');
        $sync = $this->option('sync');

        // Verificar se o arquivo existe
        if (!Storage::exists($filePath)) {
            $this->error("Arquivo não encontrado: {$filePath}");
            return 1;
        }

        // Verificar se o usuário existe
        $user = User::find($userId);
        if (!$user) {
            $this->error("Usuário não encontrado: {$userId}");
            return 1;
        }

        $this->info("Iniciando importação...");
        $this->info("Arquivo: {$filePath}");
        $this->info("Usuário: {$user->name} ({$user->id})");
        $this->info("Grupos de leitura: " . implode(', ', $readGroups));
        $this->info("Grupos de escrita: " . implode(', ', $writeGroups));
        $this->info("Versão otimizada: " . ($optimized ? 'SIM' : 'NÃO'));
        $this->info("Modo síncrono: " . ($sync ? 'SIM' : 'NÃO'));

        if (!$this->confirm('Continuar com a importação?')) {
            $this->info('Importação cancelada.');
            return 0;
        }

        try {
            if ($optimized) {
                $job = new OptimizedImportDocumentsJob($user, $filePath, $readGroups, $writeGroups);
            } else {
                $job = new ImportDocumentsJob($user, $filePath, $readGroups, $writeGroups);
            }

            if ($sync) {
                $this->info('Executando importação de forma síncrona...');
                $job->handle();
                $this->info('Importação concluída!');
            } else {
                $this->info('Adicionando job à fila...');
                dispatch($job);
                $this->info('Job adicionado à fila. Use "php artisan queue:work" para processar.');
            }

        } catch (\Exception $e) {
            $this->error("Erro durante a importação: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
