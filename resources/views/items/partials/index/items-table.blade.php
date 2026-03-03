<div class="border border-base-content/5 overflow-x-auto">
    <table class="table table-auto md:table-fixed w-full table-zebra">
        <thead class="text-xs uppercase bg-base-300">
            <tr>
                <th scope="col" class="w-[30%]">Name</th>
                <th scope="col" class="w-[20%]">Type</th>
                <th scope="col" class="w-[10%] hidden md:table-cell">AC</th>
                <th scope="col" class="w-[10%]">HP</th>
                <th scope="col" class="w-[10%] hidden md:table-cell">DMG</th>
                <th scope="col" class="w-[10%] hidden md:table-cell">Delay</th>
                <th scope="col" class="w-[10%] hidden md:table-cell">Ratio</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($items as $item)
                <tr>
                    <td scope="row">
                        <div class="flex flex-col">
                            <div class="flex items-center gap-2">
                                <x-item-link :item_id="$item->id" :item_name="$item->Name" :item_icon="$item->icon"
                                        item_class="flex" />
                                @if (($item->lootdrop_entries_count ?? 0) > 0)
                                    <span class="badge badge-ghost badge-xs text-gray-400"
                                        title="{{ $item->lootdrop_entries_count }} loot table entr{{ $item->lootdrop_entries_count === 1 ? 'y' : 'ies' }}">
                                        {{ $item->lootdrop_entries_count }} drop{{ $item->lootdrop_entries_count === 1 ? '' : 's' }}
                                    </span>
                                @endif
                                @if (($item->merchants_count ?? 0) > 0)
                                    <span class="badge badge-info badge-xs"
                                        title="Sold by {{ $item->merchants_count }} merchant{{ $item->merchants_count === 1 ? '' : 's' }}">
                                        Sold
                                    </span>
                                @endif
                            </div>
                            <span class="text-xs uppercase text-gray-500 ml-8 truncate">
                                @if ($item->slots > 0)
                                    {{ get_slots_string($item->slots) }}
                                @endif
                                @if ($item->bagslots > 0)
                                    <strong>Slots:</strong> {{ $item->bagslots }}
                                    @if ($item->bagwr > 0)
                                        <strong>WR:</strong> {{ $item->bagwr }}%
                                    @endif
                                @endif
                            </span>
                        </div>
                    </td>
                    <td>
                        <div class="flex flex-col">
                            {{ config('everquest.item_types.' . $item->itemtype) }}
                            {{-- augment --}}
                            @if ($item->itemtype == 54)
                                @php
                                    $augSlots = [];

                                    if (($item->augtype ?? 0) > 0) {
                                        $augType = $item->augtype;

                                        for ($i = 1, $bit = 1; $i <= 24; $i++, $bit *= 2) {
                                            if ($bit <= $augType && ($augType & $bit)) {
                                                $augSlots[] = $i;
                                            }
                                        }
                                        $slotsText = implode(', ', $augSlots);
                                    }
                                @endphp
                                @if (count($augSlots))
                                    <span class="text-xs text-gray-500 truncate">
                                        Type: {{ $slotsText }}
                                    </span>
                                @endif
                            @endif
                        </div>
                    </td>
                    <td class="hidden md:table-cell">{{ $item->ac ?? '-' }}</td>
                    <td>{{ $item->hp }}</td>
                    <td class="hidden md:table-cell">{{ $item->damage }}</td>
                    <td class="hidden md:table-cell">{{ $item->delay }}</td>
                    <td class="hidden md:table-cell">
                        {{ $item->damage > 0 ? number_format($item->delay / $item->damage, 2) : '-' }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

{{ $items->onEachSide(2)->links() }}
