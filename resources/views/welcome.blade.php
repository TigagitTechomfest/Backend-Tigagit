<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>WellNezt API | TigaAgit</title>

    <link rel="icon" href="{{ asset('logoonly.png') }}" type="image/png">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        wellnezt: {
                            50: '#ecfdf5',
                            100: '#d1fae5',
                            500: '#10b981', // Emerald Health
                            600: '#059669',
                            700: '#047857',
                            900: '#064e3b',
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50 text-gray-800 antialiased min-h-screen flex flex-col justify-center items-center p-4">

    <div class="max-w-md w-full bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-100">
        
        <div class="bg-gradient-to-br from-wellnezt-700 to-wellnezt-500 p-10 text-center relative overflow-hidden">
            <div class="absolute top-0 left-0 w-full h-full opacity-10">
                <svg width="100%" height="100%" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <pattern id="grid" width="20" height="20" patternUnits="userSpaceOnUse">
                            <path d="M 20 0 L 0 0 0 20" fill="none" stroke="white" stroke-width="1" />
                        </pattern>
                    </defs>
                    <rect width="100%" height="100%" fill="url(#grid)" />
                </svg>
            </div>

            <div class="mx-auto bg-white/20 backdrop-blur-sm p-4 rounded-full w-20 h-20 flex items-center justify-center mb-4 shadow-inner">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                </svg>
            </div>

            <h1 class="text-3xl font-bold text-white tracking-tight">WellNezt API</h1>
            <p class="text-wellnezt-100 text-sm mt-1 uppercase tracking-widest font-medium">Health & Nutrition Service</p>
        </div>

        <div class="p-8 pb-10">
            
            <div class="flex items-center justify-center space-x-2 bg-wellnezt-50 border border-wellnezt-100 rounded-lg py-3 px-4 mb-6">
                <span class="relative flex h-3 w-3">
                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-wellnezt-500 opacity-75"></span>
                  <span class="relative inline-flex rounded-full h-3 w-3 bg-wellnezt-600"></span>
                </span>
                <span class="text-sm font-semibold text-wellnezt-900">System Operational</span>
            </div>

            <div class="text-center">
                <p class="text-gray-500 text-sm leading-relaxed">
                    Selamat datang di Backend Server <strong>WellNezt</strong>.<br>
                    Layanan API ini dikelola dan dipantau oleh tim <strong>TigaAgit</strong>.
                </p>
                <div class="mt-4 pt-4 border-t border-gray-100">
                    <p class="text-xs text-gray-400 italic">Access restricted to authorized clients only.</p>
                </div>
            </div>

        </div>

        <div class="bg-gray-50 p-4 border-t border-gray-100 text-center">
            <p class="text-xs text-gray-400">
                &copy; {{ date('Y') }} <strong>TigaAgit</strong>.<br>
                Running on Laravel v{{ Illuminate\Foundation\Application::VERSION }}
            </p>
        </div>
    </div>

</body>
</html>