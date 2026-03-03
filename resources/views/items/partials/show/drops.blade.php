<div x-data="itemDrops({{ $item->id }})" x-init="load()" class="w-full flex flex-col mb-7">
    <div class="divider">This item is found on creatures</div>
    <div class="px-1 py-2" x-show="!loading && zoneList.length > 1">
        <label class="select w-full">
            <span class="label">Jump to Zone:</span>
            <select
                x-model="selectedZone"
                @change="scrollToZone($event)"
                class="select w-full">
                <option value="" disabled>Select a zone...</option>
                <option value="zone-top-npcs">Top 10 Drop Chances</option>
                <template x-for="zone in zoneList" :key="zone.key">
                    <option :value="zone.key" x-text="zone.label"></option>
                </template>
            </select>
        </label>
    </div>
    <div class="drops-by-zone max-h-96 overflow-y-auto scrollbar-thin scrollbar-thumb-accent scrollbar-track-base-300">
        <template x-if="loading">
            <div>
                <template x-for="n in 6">
                    <div class="px-3 py-2 animate-pulse bg-base-300 border-b border-base-200 flex justify-between">
                        <div class="h-4 bg-base-100 rounded w-1/3"></div>
                        <div class="h-4 bg-base-100 rounded w-1/5"></div>
                    </div>
                </template>
            </div>
        </template>
        <template x-if="!loading && top_npcs.length > 1">
            <div class="mb-3" id="zone-top-npcs">
                <div class="px-2 py-2 text-sm font-bold text-lime-400 bg-lime-950/60 sticky top-0 z-10">Top 10 Highest Drop Chances</div>
                <ul role="list" class="list bg-base-300 divide-y divide-base-200">
                    <template x-for="npc in top_npcs" :key="`top-${npc.id}-${npc.version}`">
                        <li class="flex justify-between gap-x-6 px-3 py-1">
                            <div class="flex min-w-0 gap-x-4">
                                <div class="min-w-0 flex-auto">
                                    <p class="text-sm/6 font-semibold text-neutral-content">
                                        <a
                                            :href="@js(route('npcs.show', '__ID__')).replace('__ID__', npc.id)"
                                            x-text="npc.clean_name"
                                            class="link-warning link-hover"></a>
                                        <span class="ml-2 text-xs text-sky-300" x-text="'(' + npc.zone_name + ')'"></span>
                                    </p>
                                </div>
                            </div>
                            <div class="shrink-0 sm:flex sm:flex-col sm:items-end">
                                <p class="mt-1 text-xs/5 font-medium text-lime-300"
                                    :title="`~${npc.computed_chance}% effective drop rate`"
                                    x-text="`${npc.chance}%`"></p>
                                <p class="text-xs text-gray-500" x-text="`~${npc.computed_chance}% eff.`"></p>
                            </div>
                        </li>
                    </template>
                </ul>
            </div>
        </template>
        <template x-if="!loading && drops.length > 0">
            <template x-for="drop in drops" :key="`${drop.zone}:${drop.version}`">
                <div :id="`zone-${drop.zone}:${drop.version}`" class="px-1">
                    <span class="block bg-neutral/80 mt-2 p-2 font-bold sticky top-0 flex items-center gap-2">
                        <span :class="drop.quest_spawn ? 'text-amber-400' : 'text-sky-400'" x-text="drop.zone_name"></span>
                        <template x-if="!drop.quest_spawn">
                            <span class="text-xs text-sky-300" x-text="'(' + drop.zone + ')'"></span>
                        </template>
                        <template x-if="drop.quest_spawn">
                            <span class="badge badge-warning badge-sm">Quest/Event</span>
                        </template>
                    </span>
                    <template x-if="drop.npcs.length > 0">
                        <ul role="list" class="list bg-base-300 divide-y divide-base-200">
                            <template x-for="npc in drop.npcs" :key="npc.id">
                                <li class="flex justify-between gap-x-6 px-3 py-1">
                                    <div class="flex min-w-0 gap-x-4 items-center">
                                        <div class="min-w-0 flex-auto">
                                            <p class="text-sm/6 font-semibold text-neutral-content flex items-center gap-2">
                                                <a
                                                    :href="@js(route('npcs.show', '__ID__')).replace('__ID__', npc.id)"
                                                    x-text="npc.clean_name"
                                                    class="link-info link-hover"></a>
                                                <template x-if="npc.no_spawn_point">
                                                    <span class="badge badge-warning badge-xs" title="No fixed spawn point">No Spawn</span>
                                                </template>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="shrink-0 sm:flex sm:flex-col sm:items-end">
                                        <p class="mt-1 text-xs/5 font-medium text-accent"
                                            :title="`Loot table chance: ${npc.chance}% × probability: ${npc.probability}% = ~${npc.computed_chance}% effective`"
                                            x-text="`${npc.chance}%`"></p>
                                        <p class="text-xs text-gray-500" x-text="`~${npc.computed_chance}% eff.`"></p>
                                    </div>
                                </li>
                            </template>
                        </ul>
                    </template>
                </div>
            </template>
        </template>
        <template x-if="!loading && drops.length === 0">
            <div class="text-sm text-gray-400 px-2 py-4 italic">No NPCs found that drop this item.</div>
        </template>
    </div>
</div>
