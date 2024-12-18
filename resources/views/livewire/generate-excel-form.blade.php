<div class="grid grid-cols-1 md:grid-cols-1 gap-6 lg:gap-8">

    <!-- Mensaje flash -->
     
    @if (session()->has('message'))
       
        <div class="p-4 mb-2 text-sm text-green-800 rounded-lg bg-green-50 dark:bg-gray-800 dark:text-green-400" role="alert">
        <span class="font-medium">Realizado!</span> {{ session('message') }}.
        </div>
    @endif

    <div class="scale-100 p-6 bg-white dark:bg-gray-800/50 dark:bg-gradient-to-bl from-gray-700/50 via-transparent dark:ring-1 dark:ring-inset dark:ring-white/5 rounded-lg shadow-2xl shadow-gray-500/20 dark:shadow-none flex focus:outline focus:outline-2 focus:outline-red-500">
        <div>
            <div class="h-16 w-16 bg-red-50 dark:bg-red-800/20 flex items-center justify-center rounded-full">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" class="w-7 h-7 stroke-red-500">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 7.5h1.5m-1.5 3h1.5m-7.5 3h7.5m-7.5 3h7.5m3-9h3.375c.621 0 1.125.504 1.125 1.125V18a2.25 2.25 0 01-2.25 2.25M16.5 7.5V18a2.25 2.25 0 002.25 2.25M16.5 7.5V4.875c0-.621-.504-1.125-1.125-1.125H4.125C3.504 3.75 3 4.254 3 4.875V18a2.25 2.25 0 002.25 2.25h13.5M6 7.5h3v3H6v-3z" />
                </svg>
            </div>

            <h2 class="mt-6 text-xl font-semibold text-gray-900 dark:text-white">Generar Excel</h2>

            <p class="mt-4 text-gray-500 dark:text-gray-400 text-sm leading-relaxed">
                Consulta la API de https://developers.syscom.mx/docs y genera un archivo Excel en base a su respuesta.
            </p>

            <button 
                wire:click="handleClick" 
                class="mt-5 bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                wire:target="handleClick"
                wire:loading.attr="disabled" >
                <span wire:loading.remove>Generar Excel <i class="fe fe-credit-card ms-2"></i></span>
                <span wire:loading>
                        Procesando, espere.
                </span>
            </button>
        </div>

        
    </div>


</div>