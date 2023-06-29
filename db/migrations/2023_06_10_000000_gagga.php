<?php

declare(strict_types=1);

namespace Engelsystem\Migrations;

use Engelsystem\Database\Migration\Migration;
use Illuminate\Support\Collection;
use Illuminate\Database\Schema\Blueprint;
use stdClass;

class Gagga extends Migration
{
    use ChangesReferences;
    use Reference;

    /**
     * Creates the new table, copies the data and drops the old one
     */
    public function up(): void
    {
        $this->schema->create('gagga_survey', function (Blueprint $table): void {
            $table->increments('id');
            $this->referencesUser($table);
            //$table->('survey_name')->index();
            $table->string('birthday')->nullable();
            $table->string('address')->nullable();
            $table->integer('zip')->nullable();
            $table->string('city')->nullable();
            $table->integer('preferred_type')->nullable();
            $table->string('food')->nullable();
            $table->boolean('driver_license')->nullable()->default(false);
            $table->longText('can_bring')->nullable();
            $table->longText('my_best_experience')->nullable();
            $table->longText('note')->nullable();
            $table->timestamps();
        });

        // if ($this->schema->hasTable('Privileges')) {
        //     $db = $this->schema->getConnection();

        //     $db->table('Privileges')->insert([
        //         ['name' => 'survey.answer', 'desc' => 'Answer a survey'],
        //         ['name' => 'survey.add', 'desc' => 'Create a survey'],
        //         ['name' => 'survey.edit', 'desc' => 'Change a survey'],
        //         ['name' => 'survey.view', 'desc' => 'Show survey data'],
        //     ]);

        //     $userGroup = -20;
        //     $bureaucratGroup = -80;
        //     $bureaucratGroup = -80;
        //     $shiftCoordinatorGroup = -60;
        //     $teamCoordinatorGroup = -65;

        //     $answerId = $db->table('Privileges')->where('name', 'survey.answer')->first()->id;
        //     $addId = $db->table('Privileges')->where('name', 'survey.add')->first()->id;
        //     $editId = $db->table('Privileges')->where('name', 'survey.edit')->first()->id;
        //     $viewId = $db->table('Privileges')->where('name', 'survey.view')->first()->id;

        //     $db->table('GroupPrivileges')->insert([
        //         ['group_id' => $userGroup, 'privilege_id' => $answerId],
        //         ['group_id' => $bureaucratGroup, 'privilege_id' => $editId],
        //         ['group_id' => $bureaucratGroup, 'privilege_id' => $addId],
        //         ['group_id' => $bureaucratGroup, 'privilege_id' => $viewId],
        //         ['group_id' => $shiftCoordinatorGroup, 'privilege_id' => $viewId],
        //         ['group_id' => $teamCoordinatorGroup, 'privilege_id' => $viewId],
        //     ]);

        // }

    }

    /**
     * Recreates the previous table, copies the data and drops the new one
     */
    public function down(): void
    {
        $this->schema->drop('gagga_survey');
    }
}
