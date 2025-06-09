<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Group;
use App\Models\User;
use Carbom\Carbon;
use MongoDB\BSON\ObjectId;

class GroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::take(2)->get();
        if ($users->count() >= 2) {
            // 3. Extrai (plucks) apenas os IDs ('_id' para MongoDB) dos usuários encontrados.
            // O toArray() converte a coleção de IDs em um array simples.
            $memberIds = $users->pluck('_id')->toArray();
            Group::create([
                'name' => 'ADLP',
                'members' => $memberIds,
            ]);
        } else {
            // Se não houver usuários suficientes, exibe uma mensagem no console.
            $this->command->info('Não há usuários suficientes para criar um grupo de teste. Execute o UserSeeder primeiro.');
        }
    }
}
