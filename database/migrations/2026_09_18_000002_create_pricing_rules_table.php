<?php
use Illuminate\Database\Migrations\Migration; use Illuminate\Database\Schema\Blueprint; use Illuminate\Support\Facades\Schema;
return new class extends Migration { public function up(): void { Schema::create('pricing_rules',function(Blueprint $t){$t->id();$t->string('name');$t->unsignedInteger('priority')->default(0);$t->boolean('active')->default(true);$t->json('conditions');$t->string('action_type');$t->decimal('action_value',12,4);$t->timestamps();}); } public function down(): void { Schema::dropIfExists('pricing_rules'); } };
