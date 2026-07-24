@props([
    'tree' => null,
    'selected' => [],
    'heading' => 'Clasificación del proyecto',
    'description' => 'Catálogo anidado: cada nivel filtra al siguiente. Es opcional y hoy solo se guarda como clasificación.',
    // Dominio declarado contra el que avisar si la selección se aparta. Opcional:
    // sin baseline el componente no muestra ningún aviso.
    'baseline' => null,
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

    // Solo los cuatro niveles de dominio: cambiar de servicio o de documento
    // es elegir otro entregable, no salirse del dominio.
    $catalogBaseline = [];
    foreach (['catalog_sector_id', 'catalog_branch_id', 'catalog_subbranch_id', 'catalog_specialty_id'] as $catalogNivel) {
        $catalogBaselineValue = data_get($baseline, $catalogNivel);
        $catalogBaseline[$catalogNivel] = $catalogBaselineValue === null ? null : (int) $catalogBaselineValue;
    }
    $catalogBaseline = array_filter($catalogBaseline, fn ($v) => $v !== null) ? $catalogBaseline : null;
@endphp

<script type="application/json" id="{{ $catalogUid }}-tree">{!! json_encode($tree, $catalogJsonFlags) !!}</script>
<script type="application/json" id="{{ $catalogUid }}-selected">{!! json_encode($selection, $catalogJsonFlags) !!}</script>
<script type="application/json" id="{{ $catalogUid }}-baseline">{!! json_encode($catalogBaseline, $catalogJsonFlags) !!}</script>

<div
    x-data="{
        tree: JSON.parse(document.getElementById('{{ $catalogUid }}-tree').textContent),
        selection: JSON.parse(document.getElementById('{{ $catalogUid }}-selected').textContent),
        dominioDeclarado: JSON.parse(document.getElementById('{{ $catalogUid }}-baseline').textContent),
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
        /**
         * Primera divergencia de dominio contra lo declarado, de arriba hacia abajo.
         * Espeja las reglas de DomainMismatchService: si falta un lado, no hay aviso.
         */
        get avisoDominio() {
            if (!this.dominioDeclarado) return null;

            const niveles = [
                { campo: 'catalog_sector_id', etiqueta: 'Sector', actual: this.sector, lista: () => this.tree.sectors },
                { campo: 'catalog_branch_id', etiqueta: 'Rama', actual: this.branch, lista: () => this.branches },
                { campo: 'catalog_subbranch_id', etiqueta: 'Subrama', actual: this.subbranch, lista: () => this.subbranches },
                { campo: 'catalog_specialty_id', etiqueta: 'Especialidad', actual: this.specialty, lista: () => this.specialties },
            ];

            for (const nivel of niveles) {
                const declarado = this.dominioDeclarado[nivel.campo];
                if (declarado == null || nivel.actual === '' || nivel.actual == null) return null;
                if (this.same(declarado, nivel.actual)) continue;

                const nombre = id => nivel.lista().find(i => this.same(i.id, id))?.nombre;
                return {
                    etiqueta: nivel.etiqueta,
                    declarado: this.nombreDeclarado(nivel.campo, declarado) ?? '—',
                    usado: nombre(nivel.actual) ?? '—',
                };
            }

            return null;
        },
        /** El valor declarado puede no estar en la lista visible tras cambiar un padre. */
        nombreDeclarado(campo, id) {
            const buscar = (lista, hijos) => {
                for (const item of lista) {
                    if (this.same(item.id, id)) return item.nombre;
                    const encontrado = hijos ? buscar(hijos(item), null) : null;
                    if (encontrado) return encontrado;
                }
                return null;
            };
            if (campo === 'catalog_sector_id') return buscar(this.tree.sectors);
            if (campo === 'catalog_branch_id') return buscar(this.tree.sectors, s => s.branches);
            if (campo === 'catalog_subbranch_id') {
                for (const s of this.tree.sectors) for (const b of s.branches) {
                    const f = buscar(b.subbranches); if (f) return f;
                }
                return null;
            }
            for (const s of this.tree.sectors) for (const b of s.branches) for (const sb of b.subbranches) {
                const f = buscar(sb.specialties); if (f) return f;
            }
            return null;
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

    {{-- Aviso de dominio: informativo, nunca bloquea. --}}
    <div
        x-show="avisoDominio"
        x-cloak
        class="rounded-hando border border-amber-300 dark:border-amber-700 bg-amber-50 dark:bg-amber-900/20 p-3"
    >
        <p class="text-sm font-semibold text-amber-900 dark:text-amber-100">
            Aviso de dominio: estás generando fuera de lo declarado
        </p>
        <p class="mt-1 text-xs text-amber-800 dark:text-amber-200">
            <span x-text="avisoDominio?.etiqueta"></span> declarado:
            <strong x-text="avisoDominio?.declarado"></strong> — estás usando:
            <strong x-text="avisoDominio?.usado"></strong>.
            Este tipo de reporte se entrenó para otro dominio, así que la salida puede
            traer vocabulario o criterios que no corresponden. Podés continuar igual.
        </p>
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
