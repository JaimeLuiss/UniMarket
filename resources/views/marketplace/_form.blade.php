@php
    $isEditing = $listing?->exists ?? false;
    $inputClass = 'form-input';
@endphp

<form method="POST" action="{{ $isEditing ? route('marketplace.update', $listing) : route('marketplace.store') }}" enctype="multipart/form-data" class="space-y-7">
    @csrf
    @if ($isEditing)
        @method('PUT')
    @endif

    @if ($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800" role="alert">
            <p class="font-bold">Revisa la información ingresada:</p>
            <ul class="mt-2 list-inside list-disc space-y-1">
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <div class="grid gap-6 sm:grid-cols-2">
        <div class="sm:col-span-2">
            <label for="title" class="mb-2 block text-sm font-semibold text-slate-700">Nombre del producto o servicio</label>
            <input id="title" name="title" type="text" value="{{ old('title', $listing?->title) }}" required maxlength="255" class="{{ $inputClass }}" placeholder="Ej. Brownies artesanales">
            @error('title') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <div class="sm:col-span-2">
            <label for="description" class="mb-2 block text-sm font-semibold text-slate-700">Descripción</label>
            <textarea id="description" name="description" rows="5" required maxlength="10000" class="{{ $inputClass }}" placeholder="Cuenta qué ofreces y qué lo hace especial...">{{ old('description', $listing?->description) }}</textarea>
            @error('description') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="category_id" class="mb-2 block text-sm font-semibold text-slate-700">Categoría</label>
            <select id="category_id" name="category_id" required class="{{ $inputClass }}">
                <option value="">Selecciona una categoría</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected((string) old('category_id', $listing?->category_id) === (string) $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
            @error('category_id') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="entrepreneur_profile_id" class="mb-2 block text-sm font-semibold text-slate-700">Emprendimiento <span class="font-normal text-slate-400">(opcional)</span></label>
            <select id="entrepreneur_profile_id" name="entrepreneur_profile_id" class="{{ $inputClass }}">
                <option value="">Publicación personal</option>
                @foreach ($entrepreneurProfiles as $profile)
                    <option value="{{ $profile->id }}" @selected((string) old('entrepreneur_profile_id', $listing?->entrepreneur_profile_id) === (string) $profile->id)>{{ $profile->business_name }}</option>
                @endforeach
            </select>
            @if ($entrepreneurProfiles->isEmpty())
                <p class="mt-2 text-xs text-slate-500">Crea y activa tu perfil de emprendimiento para asociarlo a tus publicaciones.</p>
            @endif
            @error('entrepreneur_profile_id') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="listing_type" class="mb-2 block text-sm font-semibold text-slate-700">Tipo de publicación</label>
            <select id="listing_type" name="listing_type" required class="{{ $inputClass }}">
                <option value="{{ \App\Models\Listing::TYPE_SALE }}" @selected(old('listing_type', $listing?->listing_type ?? \App\Models\Listing::TYPE_SALE) === \App\Models\Listing::TYPE_SALE)>Venta</option>
                <option value="{{ \App\Models\Listing::TYPE_EXCHANGE }}" @selected(old('listing_type', $listing?->listing_type) === \App\Models\Listing::TYPE_EXCHANGE)>Intercambio</option>
            </select>
            @error('listing_type') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="price" class="mb-2 block text-sm font-semibold text-slate-700">Precio <span class="font-normal text-slate-400">(no requerido para intercambio)</span></label>
            <input id="price" name="price" type="number" value="{{ old('price', $listing?->price) }}" min="0.01" max="9999999999.99" step="0.01" class="{{ $inputClass }}" placeholder="0.00">
            @error('price') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="availability_type" class="mb-2 block text-sm font-semibold text-slate-700">Disponibilidad</label>
            <select id="availability_type" name="availability_type" required class="{{ $inputClass }}">
                <option value="{{ \App\Models\Listing::AVAILABILITY_DAILY }}" @selected(old('availability_type', $listing?->availability_type ?? \App\Models\Listing::AVAILABILITY_DAILY) === \App\Models\Listing::AVAILABILITY_DAILY)>Disponible diariamente</option>
                <option value="{{ \App\Models\Listing::AVAILABILITY_UNIQUE }}" @selected(old('availability_type', $listing?->availability_type) === \App\Models\Listing::AVAILABILITY_UNIQUE)>Artículo único</option>
            </select>
            @error('availability_type') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="condition" class="mb-2 block text-sm font-semibold text-slate-700">Condición <span class="font-normal text-slate-400">(opcional)</span></label>
            <select id="condition" name="condition" class="{{ $inputClass }}">
                <option value="">No especificada</option>
                <option value="{{ \App\Models\Listing::CONDITION_NEW }}" @selected(old('condition', $listing?->condition) === \App\Models\Listing::CONDITION_NEW)>Nuevo</option>
                <option value="{{ \App\Models\Listing::CONDITION_USED }}" @selected(old('condition', $listing?->condition) === \App\Models\Listing::CONDITION_USED)>Usado</option>
            </select>
            @error('condition') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        @if (! $isEditing || $listing->availability_type === \App\Models\Listing::AVAILABILITY_DAILY)
            <div>
                <label for="stock" class="mb-2 block text-sm font-semibold text-slate-700">Cantidad disponible <span class="font-normal text-slate-400">(opcional)</span></label>
                <input id="stock" name="stock" type="number" value="{{ old('stock', $listing?->stock) }}" min="{{ $isEditing ? 0 : 1 }}" step="1" class="{{ $inputClass }}" placeholder="Deja vacío si no controlas inventario">
                @error('stock') <p class="field-error">{{ $message }}</p> @enderror
            </div>
        @endif

        <div>
            <label for="campus_location" class="mb-2 block text-sm font-semibold text-slate-700">Ubicación en el campus <span class="font-normal text-slate-400">(opcional)</span></label>
            <input id="campus_location" name="campus_location" type="text" value="{{ old('campus_location', $listing?->campus_location) }}" maxlength="255" class="{{ $inputClass }}" placeholder="Ej. Biblioteca central">
            @error('campus_location') <p class="field-error">{{ $message }}</p> @enderror
        </div>
    </div>

    @if ($isEditing && $listing->images->isNotEmpty())
        <fieldset>
            <legend class="text-sm font-semibold text-slate-700">Fotografías actuales</legend>
            <div class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-3">
                @foreach ($listing->images as $image)
                    <label class="relative overflow-hidden rounded-xl border border-slate-200">
                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($image->path) }}" alt="{{ $image->alt_text ?: $listing->title }}" class="aspect-[4/3] w-full object-cover">
                        <span class="flex items-center gap-2 p-3 text-xs font-medium text-slate-600">
                            <input type="checkbox" name="delete_images[]" value="{{ $image->id }}" @checked(in_array($image->id, old('delete_images', []))) class="size-4 rounded border-slate-300 text-blue-800">
                            Eliminar fotografía
                        </span>
                    </label>
                @endforeach
            </div>
            @error('delete_images.*') <p class="field-error">{{ $message }}</p> @enderror
        </fieldset>
    @endif

    <div>
        <label for="images" class="mb-2 block text-sm font-semibold text-slate-700">Fotografías <span class="font-normal text-slate-400">(JPEG, PNG o WebP; máximo 5 MB cada una)</span></label>
        <input id="images" name="images[]" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" multiple class="block w-full rounded-xl border border-dashed border-slate-300 bg-slate-50 p-4 text-sm text-slate-600 file:mr-4 file:rounded-lg file:border-0 file:bg-blue-50 file:px-4 file:py-2 file:text-sm file:font-bold file:text-blue-800 hover:file:bg-blue-100">
        <p class="mt-2 text-xs text-slate-500">Hasta 5 fotografías en total por publicación. Las imágenes se pueden gestionar al editar.</p>
        @error('images') <p class="field-error">{{ $message }}</p> @enderror
        @error('images.*') <p class="field-error">{{ $message }}</p> @enderror
    </div>

    <div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-6 sm:flex-row sm:justify-end">
        <a href="{{ $isEditing ? route('my-listings') : route('marketplace.index') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200 px-5 py-3 text-sm font-bold text-slate-700 transition hover:border-blue-200 hover:text-blue-800">Cancelar</a>
        <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-blue-800 px-6 py-3 text-sm font-bold text-white transition hover:bg-blue-900">{{ $isEditing ? 'Guardar cambios' : 'Publicar' }}</button>
    </div>
</form>

<script>
    const listingType = document.getElementById('listing_type');
    const availabilityType = document.getElementById('availability_type');
    const priceInput = document.getElementById('price');
    const stockInput = document.getElementById('stock');

    function syncListingFields() {
        if (priceInput) {
            priceInput.required = listingType?.value === '{{ \App\Models\Listing::TYPE_SALE }}';
        }

        if (stockInput) {
            const isDaily = availabilityType?.value === '{{ \App\Models\Listing::AVAILABILITY_DAILY }}';
            stockInput.disabled = !isDaily;

            if (!isDaily) {
                stockInput.value = '';
            }
        }
    }

    listingType?.addEventListener('change', syncListingFields);
    availabilityType?.addEventListener('change', syncListingFields);
    syncListingFields();
</script>
