<x-app-layout>
    <x-slot name="header">Configurações</x-slot>

    <div class="flex flex-col gap-6 lg:flex-row">
        @include('dashboards.settings.partials.nav')

        <div class="flex-1 rounded-lg bg-white p-6 shadow">
            <h2 class="text-lg font-semibold text-gray-900">Dados da empresa</h2>

            @if (session('status') === 'company-updated')
                <p class="mt-2 text-sm text-green-600">Dados da empresa atualizados com sucesso.</p>
            @endif

            <form method="POST" action="{{ route('dashboards.settings.company.update') }}" class="mt-4 space-y-4">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Razão social</label>
                        <input type="text" name="legal_name" value="{{ old('legal_name', $company->legal_name) }}" class="mt-1 w-full rounded-md border-gray-300">
                        @error('legal_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Nome fantasia</label>
                        <input type="text" name="trade_name" value="{{ old('trade_name', $company->trade_name) }}" class="mt-1 w-full rounded-md border-gray-300">
                        @error('trade_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">CNPJ</label>
                        <input type="text" name="cnpj" value="{{ old('cnpj', $company->cnpj) }}" class="mt-1 w-full rounded-md border-gray-300">
                        @error('cnpj') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Telefone comercial</label>
                        <input type="text" name="phone" value="{{ old('phone', $company->phone) }}" class="mt-1 w-full rounded-md border-gray-300">
                        @error('phone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">E-mail comercial</label>
                        <input type="email" name="email" value="{{ old('email', $company->email) }}" class="mt-1 w-full rounded-md border-gray-300">
                        @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Site</label>
                        <input type="text" name="website" value="{{ old('website', $company->website) }}" class="mt-1 w-full rounded-md border-gray-300">
                        @error('website') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Endereço completo</label>
                    <input type="text" name="address" value="{{ old('address', $company->address) }}" class="mt-1 w-full rounded-md border-gray-300">
                    @error('address') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <button type="submit" class="rounded-md bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700">Salvar alterações</button>
            </form>
        </div>
    </div>
</x-app-layout>
