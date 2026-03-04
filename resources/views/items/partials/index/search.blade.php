@php
    // slots
    $removeSlots = ['65536', '32768', '1024', '512', '16', '2'];

    $stats = config('custom_search_fields.item_stats_select');
@endphp

<form method="get" action="{{ route('items.index') }}" class="mb-6">
    <div class="space-y-4">
        <div>
            <input type="text" id="name" name="name" value="{{ request('name') }}" class="w-full input"
                placeholder="Search item by name" />
        </div>

        <div class="flex flex-wrap gap-4">
            <div class="flex flex-col w-full sm:w-auto">
                <label class="select w-full sm:w-auto">
                    <span class="label">Class</span>
                    <select id="class" name="class" class="select">
                        @foreach (collect(config('everquest.classes_short'))->sort() as $k => $v)
                            <option value="{{ $k }}" {{ request('class') == $k ? 'selected' : '' }}>
                                {{ $v }}</option>
                        @endforeach
                    </select>
                </label>
            </div>

            <div class="flex flex-col w-full sm:w-auto">
                <label class="select w-full sm:w-auto">
                    <span class="label">Item Type</span>
                    <select name="type" class="select">
                        <option value="">-</option>
                        @foreach (config('custom_search_fields.item_types_select') as $group => $types)
                            <optgroup label="{{ $group }}">
                                @foreach ($types as $id => $name)
                                    <option value="{{ $id }}"
                                        {{ request('type') != '' && request('type') == $id ? 'selected' : '' }}>
                                        {{ $name }}
                                    </option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </label>
            </div>

            <div class="flex flex-col w-full sm:w-auto">
                <label class="select w-full sm:w-auto">
                    <span class="label">Slot</span>
                    <select id="slot" name="slot" class="select">
                        <option value="">-</option>
                        @foreach (collect(config('everquest.slots'))->except($removeSlots)->sort() as $id => $islot)
                            <option value="{{ $id }}" {{ request('slot') == $id ? 'selected' : '' }}>
                                {{ $islot }}</option>
                        @endforeach
                    </select>
                </label>
            </div>

            <div class="flex flex-col w-full sm:w-auto">
                <label class="select w-full sm:w-auto">
                    <span class="label">Aug Slot</span>
                    <select id="augslot" name="augslot" class="select">
                        <option value="">-</option>
                        @foreach (collect(config('everquest.aug_slots')) as $id)
                            <option value="{{ $id }}" {{ request('augslot') == $id ? 'selected' : '' }}>
                                {{ $id }}</option>
                        @endforeach
                    </select>
                </label>
            </div>

            <div class="flex flex-row gap-4 w-full sm:w-auto">
                <div class="flex flex-col w-full sm:w-auto">
                    <label class="select w-full">
                        <span class="label">Min Lvl</span>
                        <select id="min_lvl" name="min_lvl" class="select w-full sm:w-auto">
                            <option value="">-</option>
                            @for ($i = 1; $i <= config('everquest.server_max_level'); $i++)
                                <option value="{{ $i }}" {{ request('min_lvl') == $i ? 'selected' : '' }}>
                                    {{ $i }}</option>
                            @endfor
                        </select>
                    </label>
                </div>
                <div class="flex flex-col w-full sm:w-auto">
                    <label class="select">
                        <span class="label">Max Lvl</span>
                        <select id="max_lvl" name="max_lvl" class="select w-full sm:w-auto">
                            <option value="">-</option>
                            @for ($i = 1; $i <= config('everquest.server_max_level'); $i++)
                                <option value="{{ $i }}" {{ request('max_lvl') == $i ? 'selected' : '' }}>
                                    {{ $i }}</option>
                            @endfor
                        </select>
                    </label>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mt-4">
            <x-item-search-stat-filter
                id="stat1"
                :stats="$stats"
                :selected_stat="request('stat1')"
                :selected_stat_comp="request('stat1comp')"
                :stat_value="request('stat1val')"
            />
            <x-item-search-stat-filter
                id="stat2"
                :stats="$stats"
                :selected_stat="request('stat2')"
                :selected_stat_comp="request('stat2comp')"
                :stat_value="request('stat2val')"
            />
            <x-item-search-stat-filter
                id="stat3"
                :stats="$stats"
                :selected_stat="request('stat3')"
                :selected_stat_comp="request('stat3comp')"
                :stat_value="request('stat3val')"
            />
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mt-4">
            <div class="flex flex-col w-full sm:w-auto">
                <label class="input w-full">
                    <span class="label">Has Effect</span>
                    <input type="text" class="input" id="effect" name="effect"
                        value="{{ request('effect') }}" minlength="3" />
                </label>
            </div>
            <div class="flex flex-col w-full sm:w-auto">
                <label class="input w-full">
                    <span class="label">Effect ID</span>
                    <input type="number" class="input" id="effect_id" name="effect_id"
                        value="{{ request('effect_id') }}" min="1" placeholder="Exact spell ID" />
                </label>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mt-4">
            <div class="flex flex-col w-full sm:w-auto">
                <div class="flex flex-col w-full sm:w-auto">
                    <label class="input w-48">
                        <span class="label">Bag Size >=</span>
                        <input type="number" class="input validator" min="0" max="200"
                            title="Must be between 0 and 200" id="bagslots" name="bagslots"
                            value="{{ request('bagslots') }}" maxlength="3" />
                    </label>
                </div>
            </div>
        </div>

        <div class="mt-4">
            <label class="flex items-center gap-2 cursor-pointer">
                <input
                    type="checkbox"
                    name="evo"
                    class="checkbox"
                    value="1"
                    {{ request('evo') ? 'checked' : '' }}
                />
                <span>Evolution Items</span>
            </label>
        </div>
    </div>

    <div class="pt-4">
        <button type="submit" class="btn btn-soft">
            Search
        </button>
        <a href="{{ route('items.index') }}" class="btn btn-soft btn-error">
            Reset
        </a>
    </div>
</form>
