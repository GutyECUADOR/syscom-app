<?php

use App\Http\Controllers\SysComController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/generateExcel', [SysComController::class, 'index'])->name('generateExcel');
Route::get('/createExcel', [SysComController::class, 'create'])->name('createExcel');

