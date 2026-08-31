<section>
    <h2 class="font-display text-lg font-semibold text-navy-950">Perfil do Usuário</h2>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <div class="mt-4 flex items-center gap-4">
        <span class="flex h-16 w-16 shrink-0 items-center justify-center rounded-full bg-navy-950 text-lg font-semibold text-cyan-400">
            {{ Illuminate\Support\Str::of($user->name)->explode(' ')->map(fn ($part) => mb_substr($part, 0, 1))->take(2)->join('') }}
        </span>
        <div>
            <button type="button" disabled class="rounded-[10px] border border-slate-200 px-3 py-1.5 text-[13px] font-semibold text-slate-400" title="Em breve">
                Alterar foto
            </button>
            <p class="mt-1 text-xs text-slate-400">JPG, PNG. Máx. 2MB</p>
        </div>
    </div>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-4">
        @csrf
        @method('patch')

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <x-input-label for="name" value="Nome completo" />
                <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
                <x-input-error class="mt-2" :messages="$errors->get('name')" />
            </div>

            <div>
                <x-input-label for="email" value="E-mail" />
                <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
                <x-input-error class="mt-2" :messages="$errors->get('email')" />

                @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                    <div>
                        <p class="mt-2 text-sm text-slate-800">
                            Seu e-mail ainda não foi verificado.

                            <button form="send-verification" class="rounded-md text-sm text-slate-600 underline hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-cyan-500">
                                Reenviar e-mail de verificação.
                            </button>
                        </p>

                        @if (session('status') === 'verification-link-sent')
                            <p class="mt-2 text-sm font-medium text-emerald-600">
                                Um novo link de verificação foi enviado para seu e-mail.
                            </p>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <x-input-label for="job_title" value="Cargo" />
                <x-text-input id="job_title" name="job_title" type="text" class="mt-1 block w-full" :value="old('job_title', $user->job_title)" autocomplete="organization-title" />
                <x-input-error class="mt-2" :messages="$errors->get('job_title')" />
            </div>

            <div>
                <x-input-label for="phone" value="Telefone" />
                <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full" :value="old('phone', $user->phone)" autocomplete="tel" />
                <x-input-error class="mt-2" :messages="$errors->get('phone')" />
            </div>
        </div>

        <div class="flex items-center gap-4 pt-2">
            <x-primary-button>Salvar alterações</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-slate-600"
                >Salvo.</p>
            @endif
        </div>
    </form>
</section>
