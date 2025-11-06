<?php

use App\Http\Controllers\ContactController;
use App\Http\Controllers\ContactMergeController;
use App\Http\Controllers\CustomFieldController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ContactController::class, 'index'])->name('contacts.index');
Route::get('/contacts', [ContactController::class, 'index']);
Route::get('/contacts/list', [ContactController::class, 'list'])->name('contacts.list');
Route::post('/contacts', [ContactController::class, 'store'])->name('contacts.store');
Route::put('/contacts/{contact}', [ContactController::class, 'update'])->name('contacts.update');
Route::delete('/contacts/{contact}', [ContactController::class, 'destroy'])->name('contacts.destroy');

Route::post('/contacts/merge/preview', [ContactMergeController::class, 'preview'])->name('contacts.merge.preview');
Route::post('/contacts/merge', [ContactMergeController::class, 'store'])->name('contacts.merge.store');

Route::get('/custom-fields', [CustomFieldController::class, 'index'])->name('custom-fields.index');
Route::post('/custom-fields', [CustomFieldController::class, 'store'])->name('custom-fields.store');
Route::patch('/custom-fields/{customField}', [CustomFieldController::class, 'update'])->name('custom-fields.update');
Route::delete('/custom-fields/{customField}', [CustomFieldController::class, 'destroy'])->name('custom-fields.destroy');
