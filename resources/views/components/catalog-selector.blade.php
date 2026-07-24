@props([
    'tree' => null,
    'selected' => [],
    'heading' => 'Clasificación del proyecto',
    'description' => 'Catálogo anidado: cada nivel filtra al siguiente. Es opcional y hoy solo se guarda como clasificación.',
])

@php
    $tree ??= app(\App\Services\CatalogService::class)->tree();

    $selection = [];
    foreach (\App\Services\CatalogService::columns() as $catalogColumn) {
        $catalogValue = old($catalogColumn, data_get($selected, $catalogColumn));
        $selection[$catalogColumn] = ($catalogValue === null || $catalogValue === '') ? null : (int) $catalogValue;
    }

    // El árbol viaja en un <script type="application/json"> y no en el atributo x-data:
    // son ~180 nodos y embeberlos escapados haría el HTML ilegible.
    $catalogUid = 'catalog-'.\Illuminate\Support\Str::random(8);
    $catalogJsonFlags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG;

    $catalogSelectClass = 'block w-full rounded-hando border-hando-border-light dark:border-hando-border-dark bg-white dark:bg-hando-card-dark text-hando-text-light dark:text-hando-text-dark focus:border-hando-primary focus:ring-hando-primary sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed';
    $catalogLabelClass = 'block text-sm font-medium text-hando-text-light dark:text-hando-text-dark';
@endphp

<script type="application/json" id="{{ $catalogUid }}-tree">{!! json_encode($tree, $catalogJsonFlags) !!}</script>
<script type="application/json" id="{{ $catalogUid }}-selected">{!! json_encode($selection, $catalogJsonFlags) !!}</script>

<div
    x-data="{
        tree: JSON.parse(document.getElementById('{{ $catalogUid }}-tree').textContent),
        selection: JSON.parse(document.getElementById('{{ $catalogUid }}-selected').textContent),
        sector: '',
        branch: '',
        subbranch: '',
        specialty: '',
        serviceType: '',
        documentType: '',
        init() {
            this.sector = this.selection.catalog_sector_id ?? '';
            this.branch = this.selection.catalog_branch_id ?? '';
            this.subbranch = this.selection.catalog_subbranch_id ?? '';
            this.specialty = this.selection.catalog_specialty_id ?? '';
            this.serviceType = this.selection.catalog_service_type_id ?? '';
            this.documentType = this.selection.catalog_document_type_id ?? '';
        },
        same(a, b) { return String(a) === String(b); },
        get branches() {
            return this.tree.sectors.find(s => this.same(s.id, this.sector))?.branches ?? [];
        },
        get subbranches() {
            return this.branches.find(b => this.same(b.id, this.branch))?.subbranches ?? [];
        },
        get specialties() {
            return this.subbranches.find(s => this.same(s.id, this.subbranch))?.specialties ?? [];
        },
        get documentTypes() {
            return this.tree.service_types.find(s => this.same(s.id, this.serviceType))?.document_types ?? [];
        },
        get path() {
            const nombre = (list, id) => list.find(i => this.same(i.id, id))?.nombre;
            return [
                nombre(this.tree.sectors, this.sector),
                nombre(this.branches, this.branch),
                nombre(this.subbranches, this.subbranch),
                nombre(this.specialties, this.specialty),
            ].filter(Boolean).join(' > ');
        },
        get servicePath() {
            const nombre = (list, id) => list.find(i => this.same(i.id, id))?.nombre;
            return [
                nombre(this.tree.service_types, this.serviceType),
                nombre(this.documentTypes, this.documentType),
            ].filter(Boolean).join(' > ');
        },
        clear() {
            this.sector = this.branch = this.subbranch = this.specialty = '';
            this.serviceType = this.documentType = '';
        },
    }"
    class="space-y-4 rounded-hando border border-hando-border-light dark:border-hando-border-dark p-4"
>
    <div class="flex items-start justify-between gap-4">
        <div>
            <h3 class="text-sm font-semibold text-hando-text-light dark:text-hando-text-dark">{{ $heading }}</h3>
            <p class="mt-1 text-xs text-hando-gray-500 dark:text-hando-gray-400">{{ $description }}</p>
        </div>
        <button
            type="button"
            x-show="sector || serviceType"
            x-on:click="clear()"
            class="shrink-0 text-xs text-hando-gray-500 dark:text-hando-gray-400 underline hover:no-underline"
        >
            Limpiar
        </button>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div class="space-y-2">
            <label for="{{ $catalogUid }}-sector" class="{{ $catalogLabelClass }}">Sector</label>
            <select
                name="catalog_sector_id"
                id="{{ $catalogUid }}-sector"
                x-model="sector"
                x-on:change="branch = ''; subbranch = ''; specialty = ''"
                class="{{ $catalogSelectClass }}"
            >
                <option value="">-- Selecciona un sector --</option>
                <template x-for="option in tree.sectors" :key="option.id">
                    <option :value="option.id" x-text="option.nombre"></option>
                </template>
            </select>
            @error('catalog_sector_id')
                <p class="text-sm text-hando-danger">{{ $message }}</p>
            @enderror
        </div>

        <div class="space-y-2">
            <label for="{{ $catalogUid }}-branch" class="{{ $catalogLabelClass }}">Rama</label>
            <select
                name="catalog_branch_id"
                id="{{ $catalogUid }}-branch"
                x-model="branch"
                x-on:change="subbranch = ''; specialty = ''"
                :disabled="!sector"
                class="{{ $catalogSelectClass }}"
            >
                <option value="">-- Selecciona una rama --</option>
                <template x-for="option in branches" :key="option.id">
                    <option :value="option.id" x-text="option.nombre"></option>
                </template>
            </select>
            @error('catalog_branch_id')
                <p class="text-sm text-hando-danger">{{ $message }}</p>
            @enderror
        </div>

        <div class="space-y-2">
            <label for="{{ $catalogUid }}-subbranch" class="{{ $catalogLabelClass }}">Subrama</label>
            <select
                name="catalog_subbranch_id"
                id="{{ $catalogUid }}-subbranch"
                x-model="subbranch"
                x-on:change="specialty = ''"
                :disabled="!branch"
                class="{{ $catalogSelectClass }}"
            >
                <option value="">-- Selecciona una subrama --</option>
                <template x-for="option in subbranches" :key="option.id">
                    <option :value="option.id" x-text="option.nombre"></option>
                </template>
            </select>
            @error('catalog_subbranch_id')
                <p class="text-sm text-hando-danger">{{ $message }}</p>
            @enderror
        </div>

        <div class="space-y-2">
            <label for="{{ $catalogUid }}-specialty" class="{{ $catalogLabelClass }}">Especialidad o actividad</label>
            <select
                name="catalog_specialty_id"
                id="{{ $catalogUid }}-specialty"
                x-model="specialty"
                :disabled="!subbranch"
                class="{{ $catalogSelectClass }}"
            >
                <option value="">-- Selecciona una especialidad --</option>
                <template x-for="option in specialties" :key="option.id">
                    <option :value="option.id" x-text="option.nombre"></option>
                </template>
            </select>
            @error('catalog_specialty_id')
                <p class="text-sm text-hando-danger">{{ $message }}</p>
            @enderror
        </div>

        <div class="space-y-2">
            <label for="{{ $catalogUid }}-service-type" class="{{ $catalogLabelClass }}">Tipo de servicio</label>
            <select
                name="catalog_service_type_id"
                id="{{ $catalogUid }}-service-type"
                x-model="serviceType"
                x-on:change="documentType = ''"
                class="{{ $catalogSelectClass }}"
            >
                <option value="">-- Selecciona un tipo de servicio --</option>
                <template x-for="option in tree.service_types" :key="option.id">
                    <option :value="option.id" x-text="option.nombre"></option>
                </template>
            </select>
            @error('catalog_service_type_id')
                <p class="text-sm text-hando-danger">{{ $message }}</p>
            @enderror
        </div>

        <div class="space-y-2">
            <label for="{{ $catalogUid }}-document-type" class="{{ $catalogLabelClass }}">Tipo de documento</label>
            <select
                name="catalog_document_type_id"
                id="{{ $catalogUid }}-document-type"
                x-model="documentType"
                :disabled="!serviceType"
                class="{{ $catalogSelectClass }}"
            >
                <option value="">-- Selecciona un tipo de documento --</option>
                <template x-for="option in documentTypes" :key="option.id">
                    <option :value="option.id" x-text="option.nombre"></option>
                </template>
            </select>
            @error('catalog_document_type_id')
                <p class="text-sm text-hando-danger">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <p
        x-show="path || servicePath"
        class="text-xs text-hando-gray-500 dark:text-hando-gray-400"
    >
        <span x-text="path"></span>
        <span x-show="path && servicePath"> — </span>
        <span x-text="servicePath"></span>
    </p>
</div>
