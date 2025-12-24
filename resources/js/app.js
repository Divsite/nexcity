/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

import "./bootstrap";

// import { createApp } from 'vue';

/**
 * Next, we will create a fresh Vue application instance and attach it to
 * the page. Then, you may begin adding components to this application
 * or customize the JavaScript scaffolding to fit your unique needs.
 */

// createApp({
//     data() {
//         return {
//             count: 0
//         }
//     }
// }).mount('#app');

import {Livewire} from '../../vendor/livewire/livewire/dist/livewire.esm';

Livewire.start()

// jQuery
import $ from "jquery";

window.$ = $;

// Select2
import select2 from 'select2';

select2();

import '../../vendor/rappasoft/laravel-livewire-tables/resources/imports/laravel-livewire-tables.js';

// To include both Core and Third Party (Flatpickr) Libraries (Includes JS & CSS) - Velzon already has this library (Flatpickr)
// import '../../vendor/rappasoft/laravel-livewire-tables/resources/imports/laravel-livewire-tables-all.js';
