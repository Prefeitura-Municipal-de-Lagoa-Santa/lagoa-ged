<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Document; // Importe seu model Document
use App\Models\User;     // Importe seu model User
// Use App\Models\Group; // Se você tiver um model Group e quiser buscar IDs reais
use Carbon\Carbon;
use MongoDB\BSON\ObjectId; // Necessário para criar ObjectIds para grupos de exemplo


class DocumentsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::first();
        $exampleUserId = $user->_id;
        // 2. Simular ObjectIds para grupos (em um cenário real, você buscaria de Models Group)
        // Se você tiver um Model Group, você faria algo como:
        // $group1 = Group::firstOrCreate(['name' => 'Grupo Leitura Teste']);
        // $readGroupId1 = $group1->_id;
        $readGroupId1 = (string) new ObjectId();
        $writeGroupId1 = (string) new ObjectId();

        // Document::create([
        //     'title' => 'DECRETO Nº 2415/2013 - Objeto Confirmado',
        //     'filename' => '2415.pdf',
        //     'file_extension' => 'pdf',
        //     'mime_type' => 'application/pdf',
        //     'file_size' => 63488,
        //     'upload_date' => Carbon::now(),
        //     'uploaded_by' => $exampleUserId,

        //     // Estes arrays associativos PHP serão salvos como OBJETOS BSON
        //     'metadata' => [
        //         'document_type' => 'DECRETO',
        //         'document_year' => 2013, // Cast para integer via 'metadata.document_year'
        //     ],
        //     'tags' => ['decreto', 'municipal', 'objeto'], // Este é um Array BSON
        //     'status' => 'active',
        //     'permissions' => [
        //         'read_group_ids' => [$readGroupId1], // Array BSON dentro do objeto permissions
        //         'write_group_ids' => [$writeGroupId1],// Array BSON dentro do objeto permissions
        //         'deny_group_ids' => [],             // Array BSON dentro do objeto permissions
        //     ],
        //     'file_location' => [
        //         'path' => '\PREFEITURA MUNICIPAL LAGOA SANTA MG\PLS - ADLP\2018\2018_12_DEZEMBRO\PDF\REMESSA 000001\2013\DECRETOS\2415.pdf',
        //         'storage_type' => 'file_server',
        //         'bucket_name' => null,
        //     ],
        // ]);

        // Document::create([
        //     'title' => 'DECRETO Nº 2416/2013',
        //     'filename' => '2416.pdf',
        //     'file_extension' => 'pdf',
        //     'mime_type' => 'application/pdf',
        //     'file_size' => 63528,
        //     'upload_date' => Carbon::now(),
        //     'uploaded_by' => $exampleUserId,

        //     // Estes arrays associativos PHP serão salvos como OBJETOS BSON
        //     'metadata' => [
        //         'document_type' => 'DECRETO',
        //         'document_year' => 2013, // Cast para integer via 'metadata.document_year'
        //     ],
        //     'tags' => ['decreto', 'municipal', 'objeto'], // Este é um Array BSON
        //     'status' => 'active',
        //     'permissions' => [
        //         'read_group_ids' => [$readGroupId1], // Array BSON dentro do objeto permissions
        //         'write_group_ids' => [$writeGroupId1],// Array BSON dentro do objeto permissions
        //         'deny_group_ids' => [],             // Array BSON dentro do objeto permissions
        //     ],
        //     'file_location' => [
        //         'path' => '\\10.1.7.88\datafilm\PREFEITURA MUNICIPAL LAGOA SANTA MG\PLS - ADLP\2018\2018_12_DEZEMBRO\PDF\REMESSA 000001\2013\DECRETOS\2416.pdf',
        //         'storage_type' => 'file_server',
        //         'bucket_name' => null,
        //     ],
        // ]);

    }
}
