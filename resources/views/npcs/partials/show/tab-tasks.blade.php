<input type="radio" name="npc_details" class="tab" aria-label="Quests ({{ $relatedTasks->count() }})" />
<div class="tab-content bg-base-100 border-base-300">
    <div class="border border-base-content/5 overflow-x-auto">
        <table class="table table-auto md:table-fixed w-full table-zebra">
            <thead class="text-xs uppercase bg-base-300">
                <tr>
                    <th scope="col" class="w-[40%]">Task</th>
                    <th scope="col" class="w-[15%]">Type</th>
                    <th scope="col" class="w-[15%]">Levels</th>
                    <th scope="col" class="w-[30%]">Rewards</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($relatedTasks as $task)
                    <tr>
                        <td scope="row">
                            <a href="{{ route('tasks.show', $task->id) }}" class="link-info link-hover">
                                {{ $task->title }}
                            </a>
                        </td>
                        <td>{{ $task->taskType }}</td>
                        <td>
                            {{ $task->min_level }}{{ $task->max_level > 0 && $task->max_level !== $task->min_level ? '-' . $task->max_level : '' }}
                        </td>
                        <td>
                            @if ($task->rewards->isNotEmpty())
                                <div class="flex flex-wrap gap-1">
                                    @foreach ($task->rewards as $reward)
                                        <x-item-link :item_id="$reward->id" :item_name="$reward->Name" :item_icon="$reward->icon" item_class="flex" />
                                    @endforeach
                                </div>
                            @else
                                <span class="text-gray-500">—</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
