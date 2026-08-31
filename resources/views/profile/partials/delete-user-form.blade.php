<section class="space-y-6">
    <header>
        <h2 class="font-display text-lg font-semibold text-navy-950">
            Excluir conta
        </h2>

        <p class="mt-1 text-sm text-slate-600">
            Após a exclusão, todos os dados e recursos da conta serão permanentemente apagados. Antes de excluir, baixe qualquer informação que deseje manter.
        </p>
    </header>

    <x-danger-button
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
    >Excluir conta</x-danger-button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-6">
            @csrf
            @method('delete')

            <h2 class="font-display text-lg font-semibold text-navy-950">
                Tem certeza que deseja excluir sua conta?
            </h2>

            <p class="mt-1 text-sm text-slate-600">
                Após a exclusão, todos os dados e recursos da conta serão permanentemente apagados. Digite sua senha para confirmar.
            </p>

            <div class="mt-6">
                <x-input-label for="password" value="Senha" class="sr-only" />

                <x-text-input
                    id="password"
                    name="password"
                    type="password"
                    class="mt-1 block w-3/4"
                    placeholder="Senha"
                />

                <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button x-on:click="$dispatch('close')">
                    Cancelar
                </x-secondary-button>

                <x-danger-button class="ms-3">
                    Excluir conta
                </x-danger-button>
            </div>
        </form>
    </x-modal>
</section>
