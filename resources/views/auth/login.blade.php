<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk — {{ config('app.name') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400;1,600&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#f8fafc] text-slate-900 font-sans antialiased min-h-screen flex items-center justify-center p-5 selection:bg-[#0d6d5f] selection:text-white" style="font-family: 'Plus Jakarta Sans', sans-serif;">
    <div class="w-full max-w-md">
        <!-- Brand Header -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-3xl text-white shadow-lg mb-4"
                 style="background: linear-gradient(135deg, #07352d 0%, #0d6d5f 100%);">
                <svg width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
            </div>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Satoria Group</h1>
            <p class="text-xs font-bold uppercase tracking-wider mt-1 text-[#0d6d5f]">Distributor Stock Hub</p>
        </div>

        <!-- Login Card -->
        <div class="bg-white border border-slate-200/80 rounded-3xl shadow-xl p-8">
            <div class="mb-6">
                <h2 class="text-base font-bold text-slate-900">Selamat Datang</h2>
                <p class="text-xs text-slate-500 mt-0.5">Silakan masuk dengan akun internal Satoria Anda</p>
            </div>

            @if ($errors->any())
                <div class="mb-5 rounded-2xl bg-rose-50 border border-rose-200 text-rose-700 text-xs px-4 py-3 font-semibold flex items-center gap-2">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">Alamat Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" required autofocus
                           placeholder="nama@satoriagroup.co.id"
                           class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-[#0d6d5f]/25 focus:border-[#0d6d5f] transition shadow-2xs">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">Password</label>
                    <div class="relative">
                        <input type="password" id="passwordInput" name="password" required
                               placeholder="••••••••"
                               class="w-full rounded-xl border border-slate-200 pl-4 pr-11 py-2.5 text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-[#0d6d5f]/25 focus:border-[#0d6d5f] transition shadow-2xs">
                        <button type="button" id="togglePasswordBtn"
                                class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 transition cursor-pointer"
                                title="Lihat/Sembunyikan Password"
                                aria-label="Lihat Password">
                            <!-- Eye Open -->
                            <svg id="eyeOpenIcon" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            <!-- Eye Closed -->
                            <svg id="eyeClosedIcon" class="w-4 h-4 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/>
                            </svg>
                        </button>
                    </div>
                    <!-- Caps Lock Warning Badge -->
                    <div id="capsLockAlert" class="hidden mt-2 items-center gap-1.5 px-3 py-1.5 bg-amber-50 border border-amber-200/90 rounded-xl text-amber-700 text-[11px] font-semibold">
                        <svg class="w-3.5 h-3.5 shrink-0 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <span>Peringatan: <strong>Caps Lock</strong> Anda sedang aktif</span>
                    </div>
                </div>

                <div class="flex items-center justify-between pt-1">
                    <label class="flex items-center gap-2 text-xs text-slate-600 cursor-pointer font-medium">
                        <input type="checkbox" name="remember" class="rounded text-[#0d6d5f] focus:ring-[#0d6d5f]">
                        <span>Ingat sesi saya</span>
                    </label>
                </div>

                <button type="submit"
                        class="w-full text-white text-xs font-bold rounded-xl py-3 shadow-md hover:shadow-lg transition cursor-pointer"
                        style="background: linear-gradient(135deg, #07352d 0%, #0d6d5f 100%);">
                    Masuk ke Sistem
                </button>
            </form>

            <!-- <div class="mt-6 pt-5 border-t border-slate-100">
                <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-2 text-center">Akun Demo Cepat</div>
                <div class="bg-slate-50/80 rounded-2xl p-3 border border-slate-200/60 text-[11px] text-slate-600 space-y-1 font-mono">
                    <div><b>Admin:</b> admin@satoriagroup.co.id</div>
                    <div><b>Logistik:</b> logistik@satoriagroup.co.id</div>
                    <div class="text-slate-400 pt-0.5">Password: <span class="text-slate-700 font-bold">satoria123</span></div>
                </div>
            </div> -->
        </div>

        <p class="text-center text-[11px] text-slate-400 mt-6 font-medium">
            &copy; {{ date('Y') }} PT Satoria Agro Industri / Satoria Logistics Hub
        </p>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const passwordInput = document.getElementById('passwordInput');
            const toggleBtn = document.getElementById('togglePasswordBtn');
            const eyeOpen = document.getElementById('eyeOpenIcon');
            const eyeClosed = document.getElementById('eyeClosedIcon');
            const capsAlert = document.getElementById('capsLockAlert');

            if (toggleBtn && passwordInput) {
                toggleBtn.addEventListener('click', () => {
                    const isPassword = passwordInput.type === 'password';
                    passwordInput.type = isPassword ? 'text' : 'password';
                    if (isPassword) {
                        eyeOpen.classList.add('hidden');
                        eyeClosed.classList.remove('hidden');
                        toggleBtn.setAttribute('title', 'Sembunyikan Password');
                    } else {
                        eyeOpen.classList.remove('hidden');
                        eyeClosed.classList.add('hidden');
                        toggleBtn.setAttribute('title', 'Lihat Password');
                    }
                });
            }

            if (passwordInput && capsAlert) {
                const checkCapsLock = (e) => {
                    if (e.getModifierState && e.getModifierState('CapsLock')) {
                        capsAlert.classList.remove('hidden');
                        capsAlert.classList.add('flex');
                    } else {
                        capsAlert.classList.add('hidden');
                        capsAlert.classList.remove('flex');
                    }
                };

                passwordInput.addEventListener('keydown', checkCapsLock);
                passwordInput.addEventListener('keyup', checkCapsLock);
                passwordInput.addEventListener('focus', checkCapsLock);
                passwordInput.addEventListener('blur', () => {
                    capsAlert.classList.add('hidden');
                    capsAlert.classList.remove('flex');
                });
            }
        });
    </script>
</body>
</html>
