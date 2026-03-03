<?php

use App\Models\Pet;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\NpcController;
use App\Http\Controllers\PetController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\ZoneController;
use App\Http\Controllers\SpellController;
use App\Http\Controllers\RecipeController;
use App\Http\Controllers\FactionController;

Route::get('/', [HomeController::class, 'index'])->name('home');

// global search
Route::get('/search/suggest', [App\Http\Controllers\SearchController::class, 'suggest'])
    ->middleware('throttle:30,1');

// items
Route::get('/items', [ItemController::class, 'index'])->name('items.index');
Route::get('/items/{item}', [ItemController::class, 'show'])->name('items.show');
Route::get('/items/popup/{item}', [ItemController::class, 'popup'])->name('items.popup');
Route::get('/items/drops_by_zone/{item}', [ItemController::class, 'drops_by_zone'])->name('items.drops_by_zone');

// zones
Route::get('/zones', [ZoneController::class, 'index'])->name('zones.index');
Route::get('/zones/{zone}', [ZoneController::class, 'show'])->name('zones.show');

// spells
Route::get('/spells/other', [SpellController::class, 'extra'])->name('spells.extra');
Route::get('/spells', [SpellController::class, 'index'])->name('spells.index');
Route::get('/spells/{spell}', [SpellController::class, 'show'])->name('spells.show');
Route::get('/spells/popup/{spell}', [SpellController::class, 'popup'])->name('spells.popup');

// recipes
Route::get('/recipes', [RecipeController::class, 'index'])->name('recipes.index');
Route::get('/recipes/{recipe}', [RecipeController::class, 'show'])->name('recipes.show');

// npcs
Route::get('/npcs', [NpcController::class, 'index'])->name('npcs.index');
Route::get('/npcs/{npc}', [NpcController::class, 'show'])->name('npcs.show');

// factions
Route::get('/factions', [FactionController::class, 'index'])->name('factions.index');
Route::get('/factions/{faction}', [FactionController::class, 'show'])->name('factions.show');

// tasks
Route::get('/tasks', [TaskController::class, 'index'])->name('tasks.index');
Route::get('/tasks/{task}', [TaskController::class, 'show'])->name('tasks.show');

// pets
Route::get('/pets/{id?}', [PetController::class, 'index'])
    ->name('pets.index')
    ->where('id', '[0-9]+');
Route::get('/pet/{pet}', [PetController::class, 'show'])->name('pets.show');
Route::get('/pet/popup/{pet}', [PetController::class, 'popup'])->name('pets.popup');
